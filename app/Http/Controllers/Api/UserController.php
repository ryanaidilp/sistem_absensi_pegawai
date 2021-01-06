<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Attende;
use App\Models\Holiday;
use App\Models\Outstation;
use App\Models\AttendeCode;
use Illuminate\Http\Request;
use App\Models\AbsentPermission;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;
use Illuminate\Support\Facades\Validator;
use App\Transformers\AllUserTransformers;
use App\Transformers\Serializers\CustomSerializer;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $users = User::with(['gender', 'departemen'])
            ->where('id', '!=', $request->user()->id)->where(function ($query) {
                return $query->pns()
                    ->orWhere
                    ->honorer();
            })
            ->get();
        $users = fractal()->collection($users)
            ->transformWith(new AllUserTransformers)
            ->serializeWith(new CustomSerializer)
            ->toArray();
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


        $user = User::where('phone', wordwrap($request->phone, 4, " ", true))->with(['presensi', 'dinas_luar', 'izin', 'departemen', 'gender'])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return setJson(false, 'No handphone/password salah!', [], 400, ['message' => ['Data not found']]);
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

        // Get holiday data
        $holidays = Holiday::whereYear('date', now()->year)->get();
        $holidays = $holidays->map(function ($holiday) {
            return [
                'date' => $holiday->date,
                'name' => $holiday->name,
                'description' => $holiday->description
            ];
        })->toArray();

        // Get User's Absent Permission
        $absent_permission = AbsentPermission::select(['id', 'user_id', 'start_date', 'due_date'])->where([
            ['is_approved', 1],
            ['user_id', $request->user()->id]
        ])
            ->whereYear('created_at', $year)
            ->get();
        $total_permission_day = 0;
        foreach ($absent_permission as $permission) {
            $startDate = Carbon::parse($permission->start_date);
            $dueDate = Carbon::parse($permission->due_date);
            $diff = $startDate->diffInDays($dueDate) + 1;
            $total_permission_day += $diff;
        }

        // Get User's Outstation
        $outstations = Outstation::select(['id', 'user_id', 'start_date', 'due_date'])
            ->where([
                ['is_approved', true],
                ['user_id', $request->user()->id]
            ])
            ->whereYear('created_at', $year)
            ->get();
        $total_outstation_day = 0;
        foreach ($outstations as $outstation) {
            $startDate = Carbon::parse($outstation->start_date);
            $dueDate = Carbon::parse($outstation->due_date);
            $diff = $startDate->diffInDays($dueDate) + 1;
            $total_outstation_day += $diff;
        }

        // Get User's Attendance Data
        $dates = Attende::where([
            ['user_id', $request->user()->id]
        ])
            ->with(['status_kehadiran', 'kode_absen.tipe'])
            ->whereYear('created_at', $year)
            ->orderBy('attende_code_id')
            ->get();

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
                    return [
                        'absent_type' => $attende->kode_absen->tipe->name,
                        'attend_time' => !is_null($attende->attend_time) ? Carbon::parse($attende->attend_time)->format('H:i') : "-",
                        'attend_status' => $attende->status_kehadiran->name
                    ];
                })
            ];
        })->toArray();

        $daily_formated = [];
        foreach ($daily as $value) {
            array_push($daily_formated, $value);
        }
        $daily_formated = collect($daily_formated);

        $yearly_absent_count = $daily_formated->filter(function ($item) {
            return $item['attendance_percentage'] === 0;
        })->count();

        $yearly_late_count = 0;
        foreach ($daily_formated as $daily) {
            foreach ($daily['attendances'] as $attende) {
                if ($attende['attend_status'] === 'Terlambat') {
                    $yearly_late_count++;
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


        $data = [
            'yearly' => [
                'attendance_percentage' => round($daily_formated->average('attendance_percentage'), 2),
                'absent_permission' => [
                    'day' => $total_permission_day,
                    'percentage' => round($total_permission_day / 12 * 100, 2)
                ],
                'outstation' => [
                    'day' => $total_outstation_day,
                    'percentage' => $daily_formated->count() > 0 ?  round($total_outstation_day / $daily_formated->count() * 100, 2) : 0
                ],
                'absent' => [
                    'day' => $yearly_absent_count,
                    'percentage' => $daily_formated->count() > 0 ?  round($yearly_absent_count / $daily_formated->count() * 100, 2) : 0
                ],
                'late_count' => $yearly_late_count
            ],
            'monthly' => [
                'attendance_percentage' => round($monthly->average('attendance_percentage'), 2),
                'late_count' => $monthly_late_count,
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
        $holiday = Holiday::whereDate('date', today())->first();

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
                $nextPresence = Attende::where([['user_id', $userId], ['attende_code_id', $attendeCode[1]->id]])->first();
            } else if (now()->hour >= 12 && now()->hour < 13) {
                $nextPresence = Attende::where([['user_id', $userId], ['attende_code_id', $attendeCode[2]->id]])->first();
            } else if (now()->hour >= 13 && now()->hour < 18) {
                $nextPresence = Attende::where([['user_id', $userId], ['attende_code_id', $attendeCode[3]->id]])->first();
            } else if (now()->hour >= 0 && now()->hour < 8) {
                $nextPresence = Attende::where([['user_id', $userId], ['attende_code_id', $attendeCode[0]->id]])->first();
            }
        }

        foreach ($attendeCode as $code) {

            if (
                Carbon::parse($code->start_time) <= now()
                &&
                Carbon::parse($code->end_time) >= now()
            ) {
                $nextPresence = $nextPresence = Attende::where([['user_id', $userId], ['attende_code_id', $code->id]])->first();;
                break;
            }
        }



        return $nextPresence;
    }
}
