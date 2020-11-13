<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AbsentPermission;
use App\Models\Attende;
use App\Models\AttendeCode;
use App\Transformers\AbsentPermissionTransformer;
use App\Transformers\AllUserTransformers;
use App\Transformers\AttendeTransformers;
use App\Transformers\Serializers\CustomSerializer;
use App\Transformers\UserTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        $imageName = $request->user()->name . "-" . $request->file_name;

        Storage::disk('public')->put("presensi/" . now()->format('d_m_Y') . "/" . $code->tipe->name . "/$imageName",  $realImage);

        $update = $attende->update([
            'attend_time' => now(),
            'attende_status_id' => $status,
            'photo' => "presensi/" . now()->format('d_m_Y') . "/" . $code->tipe->name . "/$imageName",
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address
        ]);

        if ($update) {
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
            'is_approved' => false
        ]);

        if ($permission) {
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

        $nextPresence = null;

        for ($i = 0; $i < $attendeCode->count(); $i++) {
            if (Carbon::parse($attendeCode[$i]->start_time) <= now() && Carbon::parse($attendeCode[$i]->end_time) >= now()) {

                $nextPresence = Attende::where(
                    [
                        ['attende_code_id', $attendeCode[$i]->id],
                        ['user_id', $userId]
                    ]
                )->first();
                break;
            }
            if ($i + 1 < $attendeCode->count()) {
                if (Carbon::parse($attendeCode[$i]->end_time) <= now() && Carbon::parse($attendeCode[$i + 1]->start_time) >= now()) {
                    $nextPresence = Attende::where(
                        [
                            ['attende_code_id', $attendeCode[$i]->id],
                            ['user_id', $userId]
                        ]
                    )->first();
                    break;
                } else if (now()->format('H') < 9) {
                    $nextPresence = Attende::where(
                        [
                            ['attende_code_id', $attendeCode[0]->id],
                            ['user_id', $userId]
                        ]
                    )->first();
                    break;
                } else {
                    $nextPresence = Attende::where(
                        [
                            ['attende_code_id', $attendeCode[3]->id],
                            ['user_id', $userId]
                        ]
                    )->first();
                    break;
                }
            }
        }
        return $nextPresence;
    }
}
