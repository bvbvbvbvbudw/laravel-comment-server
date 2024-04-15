<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function getFile($filename)
    {
        $filePath = 'public/files/' . $filename;

        if (Storage::exists($filePath)) {
            return response()->file(storage_path('app/' . $filePath));
        } else {
            return response()->json('d');
        }
    }
}
