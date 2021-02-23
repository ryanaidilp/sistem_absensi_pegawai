<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Attende;
use App\Models\AttendeCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Transformers\AllUserTransformers;
use App\Transformers\AttendeTransformers;
use App\Transformers\Web\AllAttendeTransformer;
use App\Transformers\Web\AttendeUserTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Notifications\AttendanceCanceledNotification;
use App\Repositories\Interfaces\AttendeRepositoryInterface;

class AttendeRepository implements AttendeRepositoryInterface
{
    public function getByDate($date, $exludeUser = null)
    {
        if (is_null($exludeUser)) {
            return Attende::with(['pegawai', 'status_kehadiran', 'kode_absen', 'kode_absen.tipe', 'pegawai.departemen', 'pegawai.golongan', 'pegawai.gender'])->whereDate('created_at', $date)->get();
        }
        return Attende::with(['pegawai', 'status_kehadiran', 'kode_absen', 'kode_absen.tipe', 'pegawai.departemen', 'pegawai.golongan', 'pegawai.gender'])
            ->whereDate('created_at', $date)
            ->where('user_id', '!=', $exludeUser)
            ->get();
    }

    public function getByYear($date)
    {
        return Attende::with([
            'pegawai', 'pegawai.departemen', 'pegawai.golongan',
            'pegawai.gender', 'status_kehadiran', 'kode_absen'
        ])->whereYear('created_at', $date->year)
            ->get();
    }

    public function getByMonth($date)
    {
        return Attende::with([
            'pegawai', 'pegawai.departemen', 'pegawai.golongan',
            'pegawai.gender', 'status_kehadiran', 'kode_absen'
        ])->whereMonth('created_at', $date->month)
            ->get();
    }

    public function formatUserAttendes($attendes, $forWeb = false, $forExport = false)
    {

        $attendes = $attendes->groupBy('user_id');
        return $attendes->map(function ($attende) use ($forWeb, $forExport) {
            $user = $attende->first()->pegawai;
            $presenceTransformer = $forWeb ?  new AllAttendeTransformer : new AttendeTransformers;
            if (!$forExport) {
                $presence = $attende->map(function ($data) use ($presenceTransformer) {
                    return fractal()->item($data)
                        ->transformWith($presenceTransformer)
                        ->serializeWith(new CustomSerializer)->toArray();
                })->toArray();
            } else {
                $attende = $attende->groupBy(function ($item) {
                    return $item->created_at->format('d-m-Y');
                })->values();
                $data = array();
                foreach ($attende as $key => $presence) {
                    $percentage = 0;
                    foreach ($presence as $attende) {
                        $percentage += checkAttendancePercentage($attende->attende_status_id);
                    }
                    $data[$key] = [
                        'date' => $attende->created_at->format('Y-m-d'),
                        'percentage' => round($percentage / 4, 2)
                    ];
                }
                $presence = $data;
            }
            $userTransformer = $forWeb ? new AttendeUserTransformer($presence) : new AllUserTransformers($presence);
            return fractal()->item($user)
                ->transformWith($userTransformer)
                ->serializeWith(new CustomSerializer)->toArray();
        })->values();
    }

    public function getByUserAndYear($userId, $year)
    {
        return Attende::where([
            ['user_id', $userId]
        ])
            ->with(['status_kehadiran', 'kode_absen.tipe'])
            ->whereYear('created_at', $year)
            ->orderBy('attende_code_id')
            ->get();
    }

    public function getByUserAndCode($userId, $codeId)
    {
        return Attende::with(['pegawai'])->where([['user_id', $userId], ['attende_code_id', $codeId]])->first();
    }

    public function cancel($attendanceId, $reason)
    {
        $presence = Attende::where('id', $attendanceId)->first();
        if (Storage::disk('public')->exists($presence->photo)) {
            Storage::disk('public')->delete($presence->photo);
        }
        $update = $presence->update([
            'attend_time' => null,
            'attende_status_id' => Attende::ABSENT,
            'photo' => null,
            'latitude' => 0,
            'longitude' => 0,
            'address' => null
        ]);

        if ($update) {
            $presence->pegawai->notify(new AttendanceCanceledNotification($presence, $reason));
        }

        return $update;
    }

    public function presence(Request $request, $code, $attende)
    {
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

        Storage::disk('public')->put("presensi/" . $attende->pegawai->name . "/" . $code->tipe->name . "/$imageName",  $realImage);

        $update = $attende->update([
            'attend_time' => now(),
            'attende_status_id' => $status,
            'photo' => "presensi/" . $attende->pegawai->name  . "/" . $code->tipe->name . "/$imageName",
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address
        ]);
        return $update;
    }
}
