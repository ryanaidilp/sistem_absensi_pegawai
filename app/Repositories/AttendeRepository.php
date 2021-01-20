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
            return Attende::with(['pegawai', 'status_kehadiran', 'kode_absen', 'pegawai.departemen', 'pegawai.golongan'])->whereDate('created_at', $date)->get();
        }
        return Attende::with(['pegawai', 'status_kehadiran', 'kode_absen', 'pegawai.departemen'])
            ->whereDate('created_at', $date)
            ->where('user_id', '!=', $exludeUser)
            ->get();
    }

    public function formatUserAttendes($attendes, $forWeb = false)
    {

        $attendes = $attendes->groupBy('user_id');
        return $attendes->map(function ($attende) use ($forWeb) {
            $user = $attende->first()->pegawai;
            $presenceTransformer = $forWeb ? new AllAttendeTransformer : new AttendeTransformers;
            $presence = $attende->map(function ($data) use ($presenceTransformer) {
                return fractal()->item($data)
                    ->transformWith($presenceTransformer)
                    ->serializeWith(new CustomSerializer)->toArray();
            })->toArray();
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
        if (Storage::exists($presence->photo)) {
            Storage::delete($presence->photo);
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
