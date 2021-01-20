<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Transformers\AttendeTransformers;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AttendeCodeRepository;
use App\Transformers\Serializers\CustomSerializer;
use App\Notifications\AttendeStatusUpdatedNotification;
use App\Repositories\Interfaces\AttendeRepositoryInterface;
use App\Repositories\Interfaces\HolidayRepositoryInterface;

class AttendeController extends Controller
{

    private $attendeRepository;
    private $holidayRepository;
    private $attendeCodeRepository;

    public function __construct(
        AttendeRepositoryInterface $attendeRepository,
        HolidayRepositoryInterface $holidayRepository,
        AttendeCodeRepository $attendeCodeRepository
    ) {
        $this->attendeRepository = $attendeRepository;
        $this->holidayRepository = $holidayRepository;
        $this->attendeCodeRepository = $attendeCodeRepository;
    }

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
            Log::notice("user: {$request->user()->name}\njarak: $distance\nlokasi:{$request->address}");
            $distance = number_format($distance, 2, ',', '.');
            sendNotification("Percobaan absen diluar kantor:\nPegawai : {$request->user()->name}\nJarak : $distance km\nLokasi :\n{$request->address}", 'Pelanggaran terdeteksi!', 2);
            return setJson(false, "Lokasi tidak sesuai", [], 400, ['message' => "Sistem mendeteksi anda berada $distance km dari kantor!"]);
        }

        $code = $this->attendeCodeRepository->getByCode($request->code);

        if (!$code) {
            return setJson(false, 'Gagal!', [], 404, ['message' => 'Kode absen tidak valid!']);
        }

        if (Carbon::parse($code->end_time) <= now()) {
            Log::notice("user: {$request->user()->name}\nPaksa masuk absen yang sudah selesai");
            sendNotification("Percobaan absen yang sudah selesai:\nPegawai : {$request->user()->name}\nAbsen : {$code->tipe->name}", 'Pelanggaran terdeteksi!', 2);
            return setJson(false, 'Gagal!', [], 400, ['message' => 'Kode absen sudah tidak dapat digunakan!']);
        }

        if (Carbon::parse($code->start_time) >= now()) {
            Log::notice("user: {$request->user()->name}\nAbsen diluar waktu");
            sendNotification("Percobaan absen yang belum mulai:\nPegawai : {$request->user()->name}\nAbsen : {$code->tipe->name}", 'Pelanggaran terdeteksi!', 2);
            return setJson(false, 'Pelanggaran!', [], 400, ['message' => 'Tidak boleh melakukan presensi diluar jadwal!']);
        }

        $attende = $this->attendeRepository->getByUserAndCode($request->user()->id, $code->id);

        if (!$attende) {
            return setJson(false, 'Data presensi tidak ditemukan!', [], 404, ['message' => 'Data presensi tidak ditemukan!']);
        }

        if (!is_null($attende->attend_time)) {
            return setJson(false, 'Anda sudah melakukan presensi!', [], 400, ['message' => 'Anda sudah melakukan presensi!']);
        }

        $update = $this->attendeRepository->presence($request, $code, $attende);

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
        $date = $request->has('date') ? Carbon::parse($request->date) : today();
        $attendes = $this->attendeRepository->getByDate($date);
        $users = $this->attendeRepository->formatUserAttendes($attendes);

        $holidays = $this->holidayRepository->getByYear($date);

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

        $update = $this->attendeRepository->cancel($request->presence_id, $request->reason);

        if ($update) {
            return setJson(
                true,
                'Sukses membatalkan presensi!',
                'Berhasil',
                200,
                []
            );
        }

        return setJson(false, 'Kesalahan tidak diketahui!', [], 400, ['message' => 'Kesalahan tidak diketahui!']);
    }
}
