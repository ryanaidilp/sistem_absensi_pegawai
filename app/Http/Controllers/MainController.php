<?php

namespace App\Http\Controllers;

use App\Exports\AttendeAnnualExport;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Exports\AttendeDailyExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendeMonthlyExport;
use Illuminate\Support\Facades\Storage;
use App\Transformers\Web\AttendeCodeTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Repositories\Interfaces\AttendeRepositoryInterface;
use App\Repositories\Interfaces\HolidayRepositoryInterface;
use App\Repositories\Interfaces\PaidLeaveRepositoryInterface;
use App\Repositories\Interfaces\OutstationRepositoryInterface;
use App\Repositories\Interfaces\AttendeCodeRepositoryInterface;
use App\Repositories\Interfaces\AbsentPermissionRepositoryInterface;

class MainController extends Controller
{

    private $holidayRepository;
    private $attendeRepository;
    private $paidLeaveRepository;
    private $outstationRepository;
    private $attendeCodeRepository;
    private $absentPermissionRepository;

    public function __construct(
        HolidayRepositoryInterface $holidayRepository,
        AttendeRepositoryInterface $attendeRepository,
        PaidLeaveRepositoryInterface $paidLeaveRepository,
        OutstationRepositoryInterface $outstationRepository,
        AttendeCodeRepositoryInterface $attendeCodeRepository,
        AbsentPermissionRepositoryInterface $absentPermissionRepository
    ) {
        $this->holidayRepository = $holidayRepository;
        $this->attendeRepository = $attendeRepository;
        $this->paidLeaveRepository = $paidLeaveRepository;
        $this->outstationRepository = $outstationRepository;
        $this->attendeCodeRepository = $attendeCodeRepository;
        $this->absentPermissionRepository = $absentPermissionRepository;
    }

    public function index()
    {
        $attendeCode = $this->attendeCodeRepository->getToday();
        $absent = null;
        $weekend = today()->isWeekend();
        $holiday = $this->holidayRepository->getToday();

        $holiday = (object) [
            'is_holiday' => !is_null($holiday),
            'name' =>  optional($holiday)->name ?? '',
            'date' => optional($holiday)->date ?? ''
        ];

        if ($holiday->is_holiday) {
            $deadline = null;
        } else if (!$weekend) {
            $day = today()->isFriday() ? 3 : 1;
            $deadline = $this->setTimer($day, $attendeCode);
        } else {
            $days = [
                'Sabtu' => 2,
                'Minggu' => 1,
            ][today()->dayName];
            $deadline = Carbon::parse('07:30')->addDays($days);
        }

        foreach ($attendeCode as $code) {

            if (
                Carbon::parse($code->start_time) <= now()
                &&
                Carbon::parse($code->end_time) >= now()
            ) {
                $deadline = Carbon::parse($code->end_time);
                $absent = fractal()->item($code)
                    ->transformWith(new AttendeCodeTransformer)
                    ->serializeWith(new CustomSerializer)->toArray();
                break;
            }
        }

        return Inertia::render('Home/Index', [
            'code' => $absent,
            'weekend' => $weekend,
            'deadline' => $deadline,
            'holiday' => $holiday
        ]);
    }

    public function export(Request $request)
    {
        $date = $request->has('date') ? Carbon::parse($request->date) : today();
        $attendes = $this->attendeRepository->getByDate($date);
        $attendes = $this->attendeRepository->formatUserAttendes($attendes, true);

        $pns = $attendes->filter(function ($user) {
            return $user['status'] === 'PNS';
        });
        $honorer = $attendes->filter(function ($user) {
            return $user['status'] === 'Honorer';
        });

        $izin = $this->absentPermissionRepository->getBetweenDate($date);
        $cuti = $this->paidLeaveRepository->getBetweenDate($date);
        $dinas = $this->outstationRepository->getBetweenDate($date);

        $leaves = array();

        $leaves = $this->formatLeave($leaves, $izin);
        $leaves = $this->formatLeave($leaves, $cuti);
        $leaves = $this->formatLeave($leaves, $dinas);


        return Inertia::render('Table/Index', [
            'pns' => $pns->values(),
            'honorer' => $honorer->values(),
            'leaves' => $leaves,
            'date' => $date->translatedFormat("l, d F Y"),
            'str_date' => $date->format('Y-m-d')
        ]);
    }

    public function download(Request $request)
    {
        $date = $request->has('date') ? Carbon::parse($request->date) : today();
        $type = $request->has('type') ? $request->type : 'daily';
        $employee = $request->has('employee') ? $request->employee : "PNS";
        $fileName = "Daftar Hadir {TIPE} Kantor Camat Balaesang. {TIPE}";
        $fileName = [
            'daily' => Str::replaceArray("{TIPE}",  ["Pegawai", $date->translatedFormat('l, d F Y')], $fileName),
            'monthly' => Str::replaceArray("{TIPE}",  ["Pegawai", "Bulan " . $date->translatedFormat('F Y')], $fileName),
            'annual'  => Str::replaceArray("{TIPE}",  ["Pegawai $employee", "Tahun " . $date->translatedFormat('Y')], $fileName),
        ][$type];

        $users = [
            'daily' => $this->attendeRepository->getByDate($date),
            'monthly' => $this->attendeRepository->getByMonth($date),
            'annual' => $this->attendeRepository->getByYear($date)
        ][$type];

        $forExport = [
            'daily' => false,
            'monthly' => true,
            'annual' => true
        ][$type];

        $users = $this->attendeRepository->formatUserAttendes($users, true, $forExport);

        $export = [
            'daily' => new AttendeDailyExport($date, $users),
            'monthly' => new AttendeMonthlyExport($date, $users),
            'annual' => new AttendeAnnualExport($date, $users, $type)
        ][$type];

        return Excel::download($export, "$fileName.xlsx");
    }

    private function setTimer($days, $attendeCode)
    {

        if (now()->hour >= 8 && now()->hour < 12) {
            $deadline = Carbon::parse($attendeCode[1]->start_time);
        } else if (now()->hour >= 12 && now()->hour < 13) {
            $deadline = Carbon::parse($attendeCode[2]->start_time);
        } else if (now()->hour >= 13 && now()->hour < 18) {
            $deadline = Carbon::parse($attendeCode[3]->start_time);
        } else if (now()->hour >= 0 && now()->hour < 8) {
            $deadline = Carbon::parse("07:00");
        } else {
            $deadline = Carbon::parse("07:30")->addDays($days);
        }
        return $deadline;
    }

    private function formatLeave($leaves, $items)
    {

        foreach ($items as $item) {
            $type = '';
            switch (class_basename($item)) {
                case 'AbsentPermission':
                    $type = 'Izin';
                    break;
                case 'Outstation':
                    $type = 'Dinas Luar';
                    break;
                case 'PaidLeave':
                    $type = 'Cuti';
                    break;
            }
            $position = $item->user->position;
            if (
                $position !== 'Camat' &&
                $position !== 'Driver Camat' &&
                $position !== 'Sekcam' &&
                $position !== 'Bendahara'
            ) {
                $position = $item->user->position . ' - ' . $item->user->departemen->name;
            }
            array_push($leaves, [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'type' => $type,
                'user' => $item->user->name,
                'position' => $position,
                'is_approved' => $item->is_approved ? true : false,
                'start_date' => Carbon::parse($item->start_date)->translatedFormat('l, d F Y'),
                'due_date' => Carbon::parse($item->due_date)->translatedFormat('l, d F Y'),
                'photo' => env('MEDIA_URL') . Storage::url($item->photo)
            ]);
        }
        return $leaves;
    }
}
