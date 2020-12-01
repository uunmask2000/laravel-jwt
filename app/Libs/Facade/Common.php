<?php
namespace App\Libs\Facade;

class Common extends \Illuminate\Support\Facades\Facade
{
    public static function jsonResponse(array $payload = null, $statusCode = 200, $header = [])
    {
        $payload = $payload ?: [];
        return response()->json($payload, $statusCode, $header, JSON_UNESCAPED_UNICODE);
    }

    public static function jsonErrorResponse($message = null, $code = null, $statusCode = null, $headers = [])
    {
        $payload = [
            "error" => [
                "message" => $message,
                "code" => $code,
            ],
        ];

        return response()->json($payload, $statusCode, $headers, JSON_UNESCAPED_UNICODE);
    }
}
