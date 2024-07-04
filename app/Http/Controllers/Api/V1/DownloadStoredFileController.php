<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadStoredFileController extends Controller
{
    /**
     * @operationId Download publicly stored file
     *
     * @response
     */
    public function __invoke($imageName)
    {
        $filePath = 'user_images/' . $imageName;

        if (!Storage::exists($filePath)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        return response()->file(Storage::path($filePath));
    }
}
