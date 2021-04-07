<?php

use Carbon\Carbon;
use App\Models\Attende;
use Illuminate\Support\Facades\Http;

function setJson($success, $message, $data, $statusCode, $errors = [])
{
    return response()->json([
        'success' => $success,
        'message' => $message,
        'errors' => $errors,
        'data' => $data
    ], $statusCode);
}

function sendNotification($body, $heading, $userId = null)
{
    $headings = [
        'en' => $heading,
    ];

    $content = [
        'en' => $body,
    ];


    if (!is_null($userId)) {
        $fields = [
            'app_id' => env('ONESIGNAL_APP_ID'),
            'contents' => $content,
            'headings' => $headings,
            'include_external_user_ids' => ["$userId"],
            "channel_for_external_user_ids" => "push",
        ];
    } else {
        $fields = [
            'app_id' => env('ONESIGNAL_APP_ID'),
            'contents' => $content,
            'headings' => $headings,
            'included_segments' => ['All']
        ];
    }


    Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => 'Basic ' . env('ONESIGNAL_API_KEY')
    ])->retry(3, 1000)->post(env('ONESIGNAL_API_URL'), $fields);
}

function getDistance($latitude1, $longitude1, $latitude2 = null, $longitude2 = null)
{
    if (is_null($latitude2)) {
        $latitude2 = env('LATITUDE_OFFSET');
    }

    if (is_null($longitude2)) {
        $longitude2 = env('LONGITUDE_OFFSET');
    }

    $degrees = rad2deg(acos((sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($longitude1 - $longitude2)))));

    $distance = $degrees * 111.13384;

    return (round($distance, 2));
}

function checkAttendancePercentage($status)
{
    switch ($status) {
        case Attende::ON_TIME:
        case Attende::OUTSTATION:
        case Attende::ANNUAL_LEAVE:
            return 100;
        case Attende::MATERNITY_LEAVE:
        case Attende::SICK_LEAVE:
        case Attende::IMPORTANT_REASON_LEAVE:
            return 97.5;
        case Attende::PERMISSION:
            return 50;
        case Attende::LATE:
            return 25;
        default:
            return 0;
    }
}

function calculateLateTime($start_time, $attend_time, $date)
{
    $date = Carbon::parse($date);
    $start_time = Carbon::parse("{$date->format('Y-m-d')} {$start_time}")->addMinutes(30);
    $attend_time = Carbon::parse($attend_time);
    $duration = $start_time->diffInMinutes($attend_time);
    if ($duration > 59) {
        $duration = $start_time->diffInHours($attend_time);
        return " $duration jam";
    }

    if ($duration === 0) {
        $duration = $start_time->diffInSeconds($attend_time);
        return " $duration detik";
    }

    return " $duration menit";
}


function updateStatus($from, $to, $data)
{


    if (Carbon::parse($data->due_date)->isBefore(today()) || Carbon::parse($data->start_date)->isBefore(today())) {
        $presences = $data->user->presensi()
            ->whereDate('created_at', '>=', Carbon::parse($data->start_date))
            ->whereDate('created_at', '<=', Carbon::parse($data->due_date))
            ->where('attende_status_id', $from)->get();
    } else if (Carbon::parse($data->start_date)->isToday()) {
        $presences = $data->user->presensi()->today()->where('attende_status_id', $from)->get();
    } else {
        return;
    }

    foreach ($presences as $presence) {
        $presence->update([
            'attende_status_id' => $to
        ]);
    }
}

/**
 * Converts an integer into the alphabet base (A-Z).
 *
 * @param int $n This is the number to convert.
 * @return string The converted number.
 * @author Theriault
 * 
 */
function num2alpha($n)
{
    $r = '';
    for ($i = 1; $n >= 0 && $i < 10; $i++) {
        $r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))) . $r;
        $n -= pow(26, $i);
    }
    return $r;
}
