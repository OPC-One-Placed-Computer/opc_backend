<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadFileController extends BaseController
{
    public function productImage(Request $request)
    {
        $request->validate([
            'file_name' => 'required|string'
        ]);

        $fileName = $request->input('file_name');
        $filePath = Storage::disk('local')->path('product_images/' . $fileName);

        if (!file_exists($filePath)) {
            return $this->sendError('File not found!');
        }

        return response()->file($filePath);
    }

    public function userImage(Request $request)
    {
        $request->validate([
            'file_name' => 'required|string'
        ]);

        $fileName = $request->input('file_name');
        $filePath = Storage::disk('local')->path('user_images/' . $fileName);

        if (!file_exists($filePath)) {
            return $this->sendError('File not found!');
        }

        return response()->file($filePath);
    }
}
