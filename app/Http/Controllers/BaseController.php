<?php

namespace App\Http\Controllers;

class BaseController extends Controller
{
    public function sendResponse($message, $result = [], $opt = [])
    {
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $result,
        ];

        if (!empty($opt)) {
            $response = array_merge($response, $opt);
        }

        return response()->json($response, 200);
    }

    public function sendError($message, $data = [], $code = 404, $opt = [])
    {
        $response = [
            'status' => false,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($opt)) {
            $response = array_merge($response, $opt);
        }

        return response()->json($response, $code);
    }

    public function normalizeFileName($fileName)
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
    }
}
