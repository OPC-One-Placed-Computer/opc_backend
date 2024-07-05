<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadFileController extends BaseController
{
    /**
     * @operationId Download publicly stored file
     *
     * @response
     */
    public function downloadImage(Request $request)
    {
        $filePath = Storage::disk('local')->path($request->path);

        if (!file_exists($filePath)) {
            return $this->sendError('File not found!.');
        }

        return response()->file($filePath);
    }
}
