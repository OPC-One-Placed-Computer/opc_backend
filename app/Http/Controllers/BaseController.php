<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function sendResponse($message, $result = [], $opt = [])
    {
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $result,
        ];

        $response += $opt;

        return response()->json($response, 200);
    }
}
