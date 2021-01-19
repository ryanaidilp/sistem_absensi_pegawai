<?php

namespace App\Http\Controllers;

use DateInterval;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Holiday;
use App\Models\Attende;
use App\Models\PaidLeave;
use App\Models\Outstation;
use App\Models\AttendeCode;
use Illuminate\Http\Request;
use App\Models\AbsentPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MainController extends Controller
{
    public function index()
    {
        $attendeCode = AttendeCode::with(['tipe'])->whereDate('created_at', today())->get();
        $absent = null;
        $weekend = today()->isWeekend();
        $holiday = Holiday::whereDate('date', today())->first();

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
                $absent = [
                    'code' => "data:image/svg+xml;base64," . base64_encode(QrCode::size(200)->style('round')->generate($code->code)),
                    'start_time' => Carbon::parse($code->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($code->end_time)->format('H:i'),
                    'type' => $code->tipe->name,
                    'date' => Carbon::parse($code->end_time)->translatedFormat('l, d F Y')
                ];
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
        $attendes = Attende::with(['pegawai', 'status_kehadiran', 'kode_absen', 'pegawai.departemen'])->whereDate('created_at', $date)->get();
        $attendes = $attendes->groupBy('user_id');
        $attendes = $attendes->map(function ($attende) use ($date) {
            $user = $attende->first()->pegawai;
            $presence = $attende->map(function ($data) use ($date) {
                $status = $data->status_kehadiran->name;
                if ($status === 'Terlambat') {
                    $status = $data->status_kehadiran->name;
                    $status .= calculateLateTime($data->kode_absen->start_time, $data->attend_time, $date);
                }
                return [
                    'status' => $status,
                    'attend_time' => $data->attend_time == null ? "-" : Carbon::parse($data->attend_time)->format('H:i')
                ];
            });
            return [
                'name' => $user->name,
                'status' => $user->status,
                'nip' => $user->nip,
                'department' => $user->departemen->name,
                'position' => $user->position,
                'presensi' => $presence
            ];
        });
        $attendes = $attendes->values();
        $pns = $attendes->filter(function ($user) {
            return $user['status'] === 'PNS';
        });
        $honorer = $attendes->filter(function ($user) {
            return $user['status'] === 'Honorer';
        });
        $izin = AbsentPermission::with(['user'])->whereDate('start_date', '<=', $date)
            ->whereDate('due_date', '>=', $date)->get();
        $cuti = PaidLeave::with(['user'])->whereDate('start_date', '<=', $date)
            ->whereDate('due_date', '>=', $date)->get();
        $dinas = Outstation::with(['user'])->whereDate('start_date', '<=', $date)
            ->whereDate('due_date', '>=', $date)->get();

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
