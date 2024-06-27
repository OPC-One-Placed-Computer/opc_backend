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

}
