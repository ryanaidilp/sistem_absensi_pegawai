<?php

function setJson($success, $message, $data, $statusCode, $errors = [])
{
    return response()->json([
        'success' => $success,
        'message' => $message,
        'errors' => $errors,
        'data' => $data
    ], $statusCode);
}
