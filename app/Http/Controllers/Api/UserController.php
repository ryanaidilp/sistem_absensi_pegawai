<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Attende;
use App\Models\Holiday;
use App\Models\AttendeCode;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AbsentPermission;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Transformers\UserTransformer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Transformers\AttendeTransformers;
use App\Transformers\AllUserTransformers;
use App\Transformers\AbsentPermissionTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Transformers\EmployeePermissionTransformer;

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
            'required' => ':attribute tidak boleh kosong!'
        ]);

        if ($validator->fails()) {
            return setJson(false, 'Gagal masuk ke aplikasi!', [], 400, $validator->errors()->toArray());
        }


        $user = User::where('phone', wordwrap($request->phone, 4, " ", true))->with(['presensi', 'izin', 'departemen', 'gender'])->first();

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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function presence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'photo' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'required',
            'file_name' => 'required',
        ]);

        if ($validator->fails()) {
            return setJson(false, 'Terjadi kesalahan!', [], 400, $validator->errors());
        }

        $code = AttendeCode::where('code', $request->code)->first();

        if (!$code) {
            return setJson(false, 'Kode absen tidak valid!', [], 404, ['message' => 'Kode absen tidak valid!']);
        }

        if (Carbon::parse($code->end_time) <= now()) {
            return setJson(false, 'Kode absen sudah tidak dapat digunakan!', [], 400, ['message' => 'Kode absen sudah tidak dapat digunakan!']);
        }

        if (Carbon::parse($code->start_time) >= now()) {
            return setJson(false, 'Tidak boleh melakukan presensi diluar jadwal!', [], 400, ['message' => 'Tidak boleh melakukan presensi diluar jadwal!']);
        }

        $attende = Attende::where([
            ['user_id', '=', $request->user()->id],
            ['attende_code_id', '=', $code->id],
        ])->first();

        if (!$attende) {
            return setJson(false, 'Data presensi tidak ditemukan!', [], 404, ['message' => 'Data presensi tidak ditemukan!']);
        }

        if (!is_null($attende->attend_time)) {
            return setJson(false, 'Anda sudah mengisi presensi!', [], 400, ['message' => 'Anda sudah mengisi presensi!']);
        }


        $checkForLate = [
            AttendeCode::MORNING => true,
            AttendeCode::LUNCH_BREAK => false,
            AttendeCode::AFTERNOON => true,
            AttendeCode::EVENING => false
        ][$code->code_type_id];

        $status = Attende::ON_TIME;

        if ($checkForLate) {
            $timeTolerance = [
                AttendeCode::MORNING => 15,
                AttendeCode::AFTERNOON => 30,
            ][$code->code_type_id];
            if (now() <= Carbon::parse($code->end_time)->subMinutes($timeTolerance)) {
                $status = Attende::ON_TIME;
            } else {
                $status = Attende::LATE;
            }
        }


        $realImage = base64_decode($request->photo);
        $imageName = now()->format('d_m_Y') . "-" . $request->file_name;

        Storage::disk('public')->put("presensi/" . $request->user()->name . "/" . $code->tipe->name . "/$imageName",  $realImage);

        $update = $attende->update([
            'attend_time' => now(),
            'attende_status_id' => $status,
            'photo' => "presensi/" . $request->user()->name  . "/" . $code->tipe->name . "/$imageName",
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address
        ]);

        if ($update) {
            sendNotification("Presensi berhasil : 
                \nJenis Presensi : {$attende->kode_absen->tipe->name}
                \nStatus Kehadiran: {$attende->status_kehadiran->name} 
                \nJam Presensi : {$attende->attend_time->translatedFormat('H:i:s')}", 'Presensi berhasil!', $request->user()->id);
            return setJson(
                true,
                'Sukses melakukan presensi!',
                fractal()->item($attende)->transformWith(new AttendeTransformers)->serializeWith(new CustomSerializer),
                200,
                []
            );
        }

        return setJson(false, 'Kesalahan tidak diketahui!', [], 400, ['message' => 'Kesalahan tidak diketahui!']);
    }

    public function createPermission(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'description' => 'required',
                'photo' => 'required',
                'due_date' => 'required',
                'start_date' => 'required',
                'file_name' => 'required',
            ],
            [
                'required' => ':attribute tidak boleh kosong!'
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        if (Carbon::parse($request->due_date) < today()) {
            return setJson(false, 'Gagal', [], 400, ['tanggal_kadaluarsa' => ['Hanya boleh menambahkan surat izin tertanggal hari ini.']]);
        }

        if (!Str::contains($request->title, 'Lapangan')) {
            $permissions = AbsentPermission::whereYear('start_date', now()->year)
                ->where([
                    ['user_id', request()->user()->id],
                    ['title', 'not like', '%Lapangan%']
                ])->get();


            $startDate = Carbon::parse($request->start_date);
            $dueDate = Carbon::parse($request->due_date);
            $totalDay = $startDate->diffInDays($dueDate);

            if ($permissions->count() > 0) {
                foreach ($permissions as $permission) {
                    $startDate = Carbon::parse($permission->start_date);
                    $dueDate = Carbon::parse($permission->due_date);
                    $diff = $startDate->diffInDays($dueDate);
                    $diff = $diff == 0 ? 1 : $diff;
                    $totalDay += $diff;
                }
            }

            if ($totalDay > 12) {
                return setJson(false, 'Pelanggaran', [], 400, ['tanggal_kadaluarsa' => ['Izin tidak boleh lebih dari 12 hari dalam satu tahun.']]);
            }
        }

        $realImage = base64_decode($request->photo);
        $imageName = $request->title . "-" . now()->translatedFormat('l, d F Y') . "-" . $request->file_name;

        Storage::disk('public')->put("izin/" . $request->user()->name . "/"   . $imageName,  $realImage);

        $permission = AbsentPermission::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'photo' => "izin/" . $request->user()->name . "/"   . $imageName,
            'due_date' => Carbon::parse($request->due_date),
            'start_date' => Carbon::parse($request->start_date),
            'is_approved' => $request->user()->position === 'Camat' ? true : false
        ]);

        if ($permission) {
            sendNotification('Izin diajukan dan menunggu persetujuan!', 'Izin diajukan!', $request->user()->id);
            sendNotification("Izin baru diajukan oleh  $request->user()->name : \n$request->title", 'Pengajuan izin!', 2);
            return setJson(
                true,
                'Berhasil',
                fractal()->item($permission)
                    ->transformWith(new AbsentPermissionTransformer)
                    ->serializeWith(new CustomSerializer)->toArray(),
                201,
                []
            );
        }

        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }

    public function permission(Request $request)
    {
        $permissions = AbsentPermission::where('user_id', $request->user()->id)->get();
        $permissions = fractal()
            ->collection($permissions, new AbsentPermissionTransformer)
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Berhasil', $permissions, 200, []);
    }

    public function allPermissions(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $permissions = AbsentPermission::orderBy('created_at', 'desc')->get();
        $permissions = fractal()->collection($permissions, new EmployeePermissionTransformer)
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Berhasil', $permissions, 200, []);
    }

    public function approvePermission(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $permission = AbsentPermission::where([
            ['id', $request->permission_id],
            ['user_id', $request->user_id]
        ])->first();
        $update = $permission->update([
            'is_approved' => $request->is_approved
        ]);

        $prefix = $permission->is_approved ? 'disetujui' : 'ditolak!';

        if ($update) {
            sendNotification("Izin $permission->title anda telah $prefix pada : " . now()->translatedFormat('d F Y H:i:s'), "Izin $prefix!", $permission->user_id);
            return setJson(
                true,
                'Sukses mengubah status izin!',
                fractal()->item($permission)->transformWith(new EmployeePermissionTransformer)->serializeWith(new CustomSerializer),
                200,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
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
            'new_pass_conf.same' => 'Password konfirmasi tidak cocok!'
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
        } else if (!$weekend) {
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
