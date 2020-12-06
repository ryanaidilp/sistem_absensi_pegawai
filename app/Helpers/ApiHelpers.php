<?php

use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isNull;

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


    if (!isNull($userId)) {
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
