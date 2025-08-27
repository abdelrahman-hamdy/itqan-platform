<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomFileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'disk' => 'required|string',
            'directory' => 'nullable|string',
        ]);

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $disk = $request->input('disk', 'public');
                $directory = $request->input('directory', '');

                // Store the file
                $path = $file->store($directory, $disk);

                return response()->json([
                    'success' => true,
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                    'url' => Storage::disk($disk)->url($path),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file provided',
        ], 400);
    }
}
