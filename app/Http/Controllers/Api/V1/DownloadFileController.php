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
            'image_name' => 'required|string'
        ]);

        $imageName = $request->input('image_name');
        $filePath = Storage::disk('local')->path('product_images/' . $imageName);

        if (!file_exists($filePath)) {
            return $this->sendError('File not found!');
        }

        return response()->file($filePath);
    }

    public function userImage(Request $request)
    {
        $request->validate([
            'image_name' => 'required|string'
        ]);

        $imageName = $request->input('image_name');
        $filePath = Storage::disk('local')->path('user_images/' . $imageName);

        if (!file_exists($filePath)) {
            return $this->sendError('File not found!');
        }

        return response()->file($filePath);
    }
}
