<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\PaidLeave;
use App\Models\AttendeCode;
use Illuminate\Http\Request;
use App\Models\LeaveCategory;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Transformers\AllUserTransformers;
use App\Transformers\Serializers\CustomSerializer;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\AttendeRepositoryInterface;
use App\Repositories\Interfaces\HolidayRepositoryInterface;
use App\Repositories\Interfaces\AbsentPermissionRepositoryInterface;
use App\Repositories\Interfaces\OutstationRepositoryInterface;
use App\Repositories\Interfaces\PaidLeaveRepositoryInterface;

class UserController extends Controller
{
    private $userRepository;
    private $attendeRepository;
    private $holidayRepository;
    private $paidLeaveRepository;
    private $outstationRepository;
    private $absentPermissionRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        AttendeRepositoryInterface $attendeRepository,
        HolidayRepositoryInterface $holidayRepository,
        PaidLeaveRepositoryInterface $paidLeaveRepository,
        OutstationRepositoryInterface $outstationRepository,
        AbsentPermissionRepositoryInterface $absentPermissionRepository
    ) {
        $this->userRepository = $userRepository;
        $this->attendeRepository = $attendeRepository;
        $this->holidayRepository = $holidayRepository;
        $this->paidLeaveRepository = $paidLeaveRepository;
        $this->outstationRepository = $outstationRepository;
        $this->absentPermissionRepository = $absentPermissionRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $date = today();
        $attendes = $this->attendeRepository->getByDate($date, $request->user()->id);
        $users = $this->attendeRepository->formatUserAttendes($attendes);
        if ($users->count() <= 0) {
            $users = $this->userRepository->allExcept($request->user()->id);
            $users = fractal()->collection($users)
                ->transformWith(new AllUserTransformers)
                ->serializeWith(new CustomSerializer)->toArray();
        }
        return setJson(true, 'Berhasil mengambil seluruh data pegawai!', $users, 200, []);
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required',
            'device_name' => 'required'
        ], [
            'phone.required' => 'No handphone tidak boleh kosong!',
            'password.required' => 'Kata sandi/password tidak boleh kosong!'
        ]);

        if ($validator->fails()) {
            return setJson(false, 'Gagal masuk ke aplikasi!', [], 400, $validator->errors()->toArray());
        }


        $user = $this->userRepository->getByPhone($request->phone);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return setJson(false, 'Data tidak ditemukan!', [], 400, ['message' => ['No handphone/password salah']]);
        }
        $nextPresence  = $this->checkNextPresence($user->id);
        $token = $user->createToken($request->device_name)->plainTextToken;
        $user = fractal()->item($user)
            ->transformWith(new UserTransformer($token, $nextPresence))
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Login berhasil', $user, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $nextPresence  = $this->checkNextPresence($request->user()->id);
        $user = fractal()->item($request->user())
            ->transformWith(new UserTransformer($request->bearerToken(), $nextPresence))
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Data berhasil dimuat!', $user, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update_password(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'old_pass' => 'required',
            'new_pass' => 'required|different:old_pass',
            'new_pass_conf' => 'required|same:new_pass'
        ], [
            'new_pass.different' => 'Password baru tidak boleh sama dengan password lama!',
            'new_pass_conf.same' => 'Password konfirmasi tidak cocok!',
            'old_pass.required' => 'Password lama harus diisi!',
            'new_pass.required' => 'Password baru harus diisi!',
            'new_pass_conf.required' => 'Konfirmasi password harus diisi!!'
        ]);

        if ($validator->fails()) {
            return setJson(false, 'Gagal mengubah password!', [], 400, $validator->errors()->toArray());
        }

        if (!Hash::check($request->old_pass, $request->user()->password)) {
            return setJson(false, 'Password salah!', [], 400, ['old_pass' => ['Password lama salah!']]);
        }

        $nextPresence  = $this->checkNextPresence($request->user()->id);

        if ($request->user()->update(['password' => Hash::make($request->new_pass)])) {
            $user = fractal()->item($request->user())
                ->transformWith(new UserTransformer($request->bearerToken(), $nextPresence))
                ->serializeWith(new CustomSerializer)->toArray();
            return setJson(true, "Password diubah!", $user, 200, []);
        }

        return setJson(false, 'Gagal mengubah password!', [], 400, ['Kesalahan tidak diketahui']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->where('id',  $request->user()->currentAccessToken()->id)->delete();

        return setJson(true, 'Berhasil keluar dari aplikasi!', [], 200);
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'notifiable_id' => $notification->notifiable_id,
                'notifiable_type' => $notification->notifiable_type,
                'data' => $notification->data,
                'is_read' => !is_null($notification->read_at)
            ];
        });
        return setJson(true, 'Berhasil', $notifications, 200, []);
    }

    public function readNotification(Request $request)
    {
        $request->user()
            ->notifications
            ->where('id', $request->notification_id)
            ->first()->markAsRead();


        return setJson(true, 'Berhasil', 'Sukses mengubah status pemberitahuan!', 200, []);
    }

    public function readAllNotifications(Request $request)
    {
        $request->user()
            ->unreadNotifications
            ->markAsRead();


        return setJson(true, 'Berhasil', 'Sukses mengubah status pemberitahuan!', 200, []);
    }

    public function deleteAllNotifications(Request $request)
    {
        $request->user()
            ->notifications()
            ->delete();


        return setJson(true, 'Berhasil', 'Sukses menghapus semua pemberitahuan!', 200, []);
    }

    public function send(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'content' => 'required|string'
        ], [
            'title.required' => 'Judul tidak boleh kosong!',
            'content.required' => 'Isi pemberitahuan tidak boleh kosong!'
        ]);

        if ($validator->fails()) {
            return setJson(false, 'Terjadi kesalahan!', [], 400, $validator->errors());
        }

        sendNotification($request->content, $request->title);

        return setJson(true, 'Berhasil', 'Sukses mengirimkan pengumumam!', 200, []);
    }

    public function myStatistic(Request $request)
    {
        $year = $request->has('year') ? $request->year : now()->year;
        $month = $request->has('month') ? $request->month : now()->month;
        $userId = $request->has('user_id') ? $request->user_id : $request->user()->id;

        // Get holiday data
        $holidays = $this->holidayRepository->getByYear($year);

        // Get User's Absent Permission
        $absent_permission = $this->absentPermissionRepository->getByUserAndYear($userId, $year);
        $total_permission_day = $this->checkDifference($absent_permission);

        // Get User's Outstation
        $outstations = $this->outstationRepository->getByUserAndYear($userId, $year);
        $total_outstation_day = $this->checkDifference($outstations);

        // Get User's Paid Leave data
        $paid_leave = $this->paidLeaveRepository->getByUserAndYear($userId, $year);

        $annual_leave = $this->filterPaidLeave($paid_leave, PaidLeave::ANNUAL);
        $important_reason_leave = $this->filterPaidLeave($paid_leave, PaidLeave::IMPORTANT_REASON);
        $sick_leave = $this->filterPaidLeave($paid_leave, PaidLeave::SICK);
        $maternity_leave = $this->filterPaidLeave($paid_leave, PaidLeave::MATERNITY);
        $out_of_liability_leave = $this->filterPaidLeave($paid_leave, PaidLeave::OUT_OF_LIABILITY);
        $leave_categories = LeaveCategory::select('id', 'limit')->get();
        $annual_limit = $leave_categories->where('id', PaidLeave::ANNUAL)->first()->limit;
        $important_reason_limit = $leave_categories->where('id', PaidLeave::IMPORTANT_REASON)->first()->limit;
        $sick_limit = $leave_categories->where('id', PaidLeave::SICK)->first()->limit;
        $maternity_limit = $leave_categories->where('id', PaidLeave::MATERNITY)->first()->limit;
        $out_of_liability_limit = $leave_categories->where('id', PaidLeave::OUT_OF_LIABILITY)->first()->limit;


        $total_annual_leave = $this->checkDifference($annual_leave);
        $total_important_reason_leave = $this->checkDifference($important_reason_leave);
        $total_sick_leave = $this->checkDifference($sick_leave);
        $total_maternity_leave = $this->checkDifference($maternity_leave);
        $total_out_of_liability_leave = $this->checkDifference($out_of_liability_leave);

        // Get User's Attendance Data
        $dates = $this->attendeRepository->getByUserAndYear($userId, $year);

        // Map user attendance data to daily attendance
        $daily = $dates->groupBy(function ($item) {
            return $item->created_at->format('d-m-Y');
        })->map(function ($attendes) {
            $percentage = 0;
            foreach ($attendes as $attende) {
                $percentage += checkAttendancePercentage($attende->attende_status_id);
            }
            return [
                'date' => $attende->created_at->format('Y-m-d'),
                'attendance_percentage' => round($percentage / 4, 2),
                'attendances' => $attendes->map(function ($attende) {
                    $attend_date = Carbon::parse($attende->created_at);
                    $start_time = explode(':', "{$attende->kode_absen->start_time}");
                    $end_time = explode(':', "{$attende->kode_absen->end_time}");
                    $start_time = Carbon::create(
                        $attend_date->year,
                        $attend_date->month,
                        $attend_date->day,
                        $start_time[0],
                        $start_time[1]
                    );
                    $end_time = Carbon::create(
                        $attend_date->year,
                        $attend_date->month,
                        $attend_date->day,
                        $end_time[0],
                        $end_time[1]
                    );
                    return [
                        'id' => $attende->id,
                        'date' => $attende->created_at->format('Y-m-d'),
                        'absent_type' => $attende->kode_absen->tipe->name,
                        'attend_time' => !is_null($attende->attend_time) ? Carbon::parse($attende->attend_time)->format('H:i:s') : "-",
                        'attend_status' => $attende->status_kehadiran->name,
                        'start_time' => Carbon::parse($start_time)->translatedFormat('Y-m-d H:i:s'),
                        'end_time' => Carbon::parse($end_time)->translatedFormat('Y-m-d H:i:s'),
                        'photo' => is_null($attende->photo) ? "" : env('MEDIA_URL') . Storage::url($attende->photo),
                        'address' => $attende->address ?? "",
                        'location' => [
                            'latitude' => (float) $attende->latitude,
                            'longitude' => (float) $attende->longitude,
                            'address' => $attende->address ?? ""
                        ],
                    ];
                })
            ];
        });

        $daily_formated = $daily->values();

        $yearly_absent_count = $daily_formated->filter(function ($item) {
            return $item['attendance_percentage'] == 0;
        })->count();

        $yearly_late_count = 0;
        foreach ($daily_formated as $daily) {
            foreach ($daily['attendances'] as $attende) {
                if ($attende['attend_status'] === 'Terlambat') {
                    $yearly_late_count++;
                }
            }
        }

        $yearly_not_absent = $daily_formated->filter(function ($item) {
            return $item['attendance_percentage'] > 0;
        });

        $yearly_not_morning_parade = 0;
        $yearly_leave_early = 0;
        $yearly_early_lunch_break = 0;
        $yearly_not_come_after_lunch_break = 0;
        foreach ($yearly_not_absent as $daily) {
            foreach ($daily['attendances'] as $attende) {
                if ($attende['absent_type'] === 'Absen Pagi') {
                    if ($attende['attend_status'] == 'Tidak Hadir') {
                        $yearly_not_morning_parade++;
                    }
                }
                if ($attende['absent_type'] === 'Absen Pulang') {
                    if ($attende['attend_status'] == 'Tidak Hadir') {
                        $yearly_leave_early++;
                    }
                }
                if ($attende['absent_type'] === 'Absen Istrahat') {
                    if ($attende['attend_status'] == 'Tidak Hadir') {
                        $yearly_early_lunch_break++;
                    }
                }
                if ($attende['absent_type'] === 'Absen Siang') {
                    if ($attende['attend_status'] == 'Tidak Hadir') {
                        $yearly_not_come_after_lunch_break++;
                    }
                }
            }
        }

        $monthly = $daily_formated->filter(function ($item) use ($month, $year) {
            return Carbon::parse($item['date'])->format('F') === Carbon::create($year, $month, 1)->format('F');
        });
        $monthly_late_count = $monthly->sum(function ($item) {
            return collect($item['attendances'])->filter(function ($attende) {
                return $attende['attend_status'] === 'Terlambat';
            })->count();
        });
        $monthly_not_absent = $monthly->filter(function ($item) {
            return $item['attendance_percentage'] > 0;
        });
        $monthly_not_morning_parade = 0;
        $monthly_leave_early = 0;
        $monthly_early_lunch_break = 0;
        $monthly_not_come_after_lunch_break = 0;
        foreach ($monthly_not_absent as $daily) {
            foreach ($daily['attendances'] as $attende) {
                if ($attende['absent_type'] === 'Absen Pagi') {
                    if ($attende['attend_status'] == 'Tidak Hadir') {
                        $monthly_not_morning_parade++;
                    }
                }
                if ($attende['absent_type'] === 'Absen Pulang') {
                    if ($attende['attend_status'] == 'Tidak Hadir') {
                        $monthly_leave_early++;
                    }
                }
                if ($attende['absent_type'] === 'Absen Istrahat') {
                    if ($attende['attend_status'] == 'Tidak Hadir') {
                        $monthly_early_lunch_break++;
                    }
                }
                if ($attende['absent_type'] === 'Absen Siang') {
                    if ($attende['attend_status'] == 'Tidak Hadir') {
                        $monthly_not_come_after_lunch_break++;
                    }
                }
            }
        }

        $total_work_day = $daily_formated->count() > 0 ? $daily_formated->count() : 0;

        $data = [
            'total_work_day' => $total_work_day,
            'yearly' => [
                'attendance_percentage' => round($daily_formated->average('attendance_percentage'), 2),
                'absent_permission' => [
                    'day' => $total_permission_day,
                    'percentage' => $total_work_day > 0 ? round($total_permission_day / $total_work_day * 100, 2) : 0
                ],
                'outstation' => [
                    'day' => $total_outstation_day,
                    'percentage' => $total_work_day > 0 ?  round($total_outstation_day / $total_work_day * 100, 2) : 0
                ],
                'absent' => [
                    'day' => $yearly_absent_count,
                    'percentage' => $total_work_day > 0 ?  (round($yearly_absent_count / 46 * 100, 2) > 100 ? 100 : round($yearly_absent_count / 46 * 100, 2)) : 0,
                    'limit' => 46
                ],
                'annual_leave' => [
                    'day' => $total_annual_leave,
                    'percentage' => round($total_annual_leave / $annual_limit * 100, 2),
                    'limit' => $annual_limit
                ],
                'important_reason_leave' => [
                    'day' => $total_important_reason_leave,
                    'percentage' => round($total_important_reason_leave / $important_reason_limit * 100, 2),
                    'limit' => $important_reason_limit
                ],
                'maternity_leave' => [
                    'day' => $total_maternity_leave,
                    'percentage' => round($total_maternity_leave / $maternity_limit * 100, 2),
                    'limit' => $maternity_limit
                ],
                'sick_leave' => [
                    'day' => $total_sick_leave,
                    'percentage' => round($total_sick_leave / $sick_limit * 100, 2),
                    'limit' => $sick_limit
                ],
                'out_of_liability_leave' => [
                    'day' => $total_out_of_liability_leave,
                    'percentage' => round($total_out_of_liability_leave / $out_of_liability_limit * 100, 2),
                    'limit' => $out_of_liability_limit
                ],
                'late_count' => $yearly_late_count,
                'leave_early_count' => $yearly_leave_early,
                'not_morning_parade_count' => $yearly_not_morning_parade,
                'early_lunch_break_count' => $yearly_early_lunch_break,
                'not_come_after_lunch_break_count' => $yearly_not_come_after_lunch_break
            ],
            'monthly' => [
                'attendance_percentage' => round($monthly->average('attendance_percentage'), 2),
                'late_count' => $monthly_late_count,
                'leave_early_count' => $monthly_leave_early,
                'not_morning_parade_count' => $monthly_not_morning_parade,
                'early_lunch_break_count' => $monthly_early_lunch_break,
                'not_come_after_lunch_break_count' => $monthly_not_come_after_lunch_break
            ],
            'daily' => $daily_formated,
            'holidays' => $holidays
        ];

        return setJson(true, 'Sukses', $data, 200, []);
    }

    private function checkNextPresence($userId)
    {
        $attendeCode = AttendeCode::with(['tipe'])->whereDate('created_at', today())->get();
        $weekend = today()->isWeekend();
        $holiday = $this->holidayRepository->getToday();

        $nextPresence = null;

        $holiday = (object) [
            'is_holiday' => !is_null($holiday),
            'name' =>  optional($holiday)->name ?? '',
            'date' => optional($holiday)->date ?? ''
        ];

        if ($holiday->is_holiday) {
            $nextPresence = null;
        } else if (!$weekend && $attendeCode->count() > 0) {
            if (now()->hour >= 8 && now()->hour < 12) {
                $nextPresence = $this->attendeRepository->getByUserAndCode($userId, $attendeCode[1]->id);
            } else if (now()->hour >= 12 && now()->hour < 13) {
                $nextPresence = $this->attendeRepository->getByUserAndCode($userId, $attendeCode[2]->id);
            } else if (now()->hour >= 13 && now()->hour < 18) {
                $nextPresence = $this->attendeRepository->getByUserAndCode($userId, $attendeCode[3]->id);
            } else if (now()->hour >= 0 && now()->hour < 8) {
                $nextPresence = $this->attendeRepository->getByUserAndCode($userId, $attendeCode[0]->id);
            }
        }

        foreach ($attendeCode as $code) {

            if (
                Carbon::parse($code->start_time) <= now()
                &&
                Carbon::parse($code->end_time) >= now()
            ) {
                $nextPresence = $this->attendeRepository->getByUserAndCode($userId, $code->id);
                break;
            }
        }



        return $nextPresence;
    }

    private function checkDifference($data)
    {
        $sum = 0;
        foreach ($data as $item) {
            $startDate = Carbon::parse($item->start_date);
            $dueDate = Carbon::parse($item->due_date);

            $sum += $startDate->diffInDays($dueDate) + 1;
        }
        return $sum;
    }

    private function filterPaidLeave($paidLeave, $category)
    {
        return $paidLeave->filter(function ($leave) use ($category) {
            return $leave->leave_category_id == $category;
        });
    }
}
