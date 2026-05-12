<?php

namespace App\Http\Controllers;

use App\Support\UploadStorage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class UploadedFileController extends Controller
{
    public function show(string $path): Response
    {
        $path = UploadStorage::normalizePath($path);

        abort_unless(str_starts_with($path, 'upload/'), 404);

        $disk = Storage::disk(UploadStorage::finalDisk());

        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
        ]);
    }
}
