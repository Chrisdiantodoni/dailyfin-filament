<?php
// app/Services/ImageCompressionService.php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class ImageCompressionService
{

    // public function makeUniqueFileName(UploadedFile $uploadedFile)
    // {
    //     $originalFileName = $uploadedFile->getClientOriginalName();
    //     $info = pathinfo($originalFileName);
    //     return Str::slug($info['filename']) . "-" . time() . '.' . $info['extension'];
    // }
    public function makeUniqueFileName(UploadedFile $file, ?string $extension = null): string
    {
        // Kalau extension nggak dikasih, ambil dari file asli
        $ext = $extension ?? $file->getClientOriginalExtension();
        return time() . '_' . uniqid() . '.' . $ext;
    }

    private function getImageDriver()
    {
        return extension_loaded('imagick') ? 'imagick' : 'gd';
    }

    public function resizeImage(UploadedFile $uploadedFile)
    {
        $driver = $this->getImageDriver();
        $image = ImageManager::$driver()->read($uploadedFile->getRealPath());
        $image->resize(width: 900);

        return $image;
    }
    public function convertToWebP(UploadedFile $uploadedFile, int $quality = 80)
    {
        $driver = $this->getImageDriver();
        $image = ImageManager::$driver()->read($uploadedFile->getRealPath());

        // Optional: resize jika terlalu besar
        // $image->scale(width: 1200); // atau bisa pakai resize()

        // Convert ke WebP dengan quality setting
        return $image->toWebp($quality);
    }

    public function convertToJpg(string $filepath, int $quality = 80)
    {
        // 1. Cek file exists (This part is correct)
        if (!Storage::disk('public')->exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        // 2. Get the absolute file path on the server
        // ImageManager::read() needs the full physical path, not the relative path.
        $absolutePath = Storage::disk('public')->path($filepath);

        $driver = $this->getImageDriver();

        // 3. Use the absolute path to read the image
        // Note: The $filepath variable name here is slightly misleading as it's the absolute path
        $image = ImageManager::$driver()->read($absolutePath);

        // Note: You are using 90 here, not the $quality parameter (80)
        $encoded = $image->toJpeg(90);

        // Since $encoded is binary data, you should return it with the correct content type.
        // However, if you are just debugging, returning the data might be fine. 
        // If you intend to serve this image, the return type should be a Response.

        return $encoded;
    }
}
