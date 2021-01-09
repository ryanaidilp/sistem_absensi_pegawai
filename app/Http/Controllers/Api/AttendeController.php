<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Attende;
use App\Models\AttendeCode;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Notifications\AttendanceCanceledNotification;
use Illuminate\Support\Facades\Storage;
use App\Transformers\AttendeTransformers;
use Illuminate\Support\Facades\Validator;
use App\Transformers\Serializers\CustomSerializer;
use App\Notifications\AttendeStatusUpdatedNotification;
use App\Transformers\AllUserTransformers;

class AttendeController extends Controller
{
    public function presence(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'photo' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'required',
            'file_name' => 'required',
        ], [
            'code.required' => 'Kode absen tidak boleh kosong!',
            'address.required' => 'Alamat tidak boleh kosong!',
            'photo.required' => 'Foto tidak boleh kosong!',
            'latitude.required' => 'Latitude tidak boleh kosong!',
            'longitude.required' => 'Longitude tidak boleh kosong!',
        ]);

        if ($validator->fails()) {
            return setJson(false, 'Terjadi kesalahan!', [], 400, $validator->errors());
        }

        $distance = getDistance($request->latitude, $request->longitude);
        if ($distance > 0.5) {
            $distance = number_format($distance, 2, ',', '.');
            return setJson(false, "Sistem mendeteksi anda berada $distance km dari kantor!", [], 400, ['message' => "Lokasi tidak sesuai"]);
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
            return setJson(false, 'Anda sudah melakukan presensi!', [], 400, ['message' => 'Anda sudah melakukan presensi!']);
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
                AttendeCode::MORNING => 30,
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
            $request->user()->notify(new AttendeStatusUpdatedNotification($attende));
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

    public function index(Request $request)
    {
        $date = today();
        if ($request->has('date')) {
            $date = Carbon::parse($request->date);
        }

        $users =  User::where(function ($query) {
            return $query->pns()
                ->orWhere
                ->honorer();
        })
            ->get();

        $users = fractal()->collection($users)
            ->transformWith(new AllUserTransformers($date))
            ->serializeWith(new CustomSerializer)
            ->toArray();

        $holidays = Holiday::whereYear('date', $date)->get()->map(function ($holiday) {
            return [
                'date' => $holiday->date,
                'name' => $holiday->name,
                'description' => $holiday->description
            ];
        })->toArray();

        return setJson(true, 'Berhasil mengambil seluruh data pegawai!', [
            'employees' => $users,
            'holidays' => $holidays
        ], 200, []);
    }

    public function cancel(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'reason' => 'required',
                'presence_id' => 'required'
            ],
            [
                'reason.required' => 'Alasan pembatalan harus diisi!'
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        $presence = Attende::where('id', $request->presence_id)->first();


        if (Storage::exists($presence->photo)) {
            Storage::delete($presence->photo);
        }

        $update  = $presence->update([
            'attend_time' => null,
            'attende_status_id' => Attende::ABSENT,
            'photo' => null,
            'latitude' => 0,
            'longitude' => 0,
            'address' => null
        ]);

        if ($update) {
            $presence->pegawai->notify(new AttendanceCanceledNotification($presence, $request->reason));
            return setJson(
                true,
                'Sukses membatalkan presensi!',
                fractal()->item($presence)->transformWith(new AttendeTransformers)->serializeWith(new CustomSerializer),
                200,
                []
            );
        }

        return setJson(false, 'Kesalahan tidak diketahui!', [], 400, ['message' => 'Kesalahan tidak diketahui!']);
    }
}
