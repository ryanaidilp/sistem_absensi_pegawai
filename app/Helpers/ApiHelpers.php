<?php

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
            'include_external_user_ids' => [$userId],
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

function getDistance($latitude1, $longitude1, $latitude2 = -0.0497952, $longitude2 = 119.8804039)
{
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
