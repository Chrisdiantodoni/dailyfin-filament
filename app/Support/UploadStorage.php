<?php

namespace App\Support;

use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadStorage
{
    public static function temporaryDisk(): string
    {
        return config('filesystems.uploads.temporary_disk', 'public');
    }

    public static function finalDisk(): string
    {
        return config('filesystems.uploads.final_disk', 'public');
    }

    public static function reference(string $directory, string $filename): string
    {
        return '/' . trim($directory, '/') . '/' . $filename;
    }

    public static function isExistingReference(?string $path): bool
    {
        return is_string($path) && str_starts_with($path, '/upload/');
    }

    public static function url(string $path): string
    {
        $path = self::normalizePath($path);

        if (app('router')->has('uploads.show')) {
            return url(route('uploads.show', ['path' => $path], false));
        }

        return Storage::disk(self::finalDisk())->url($path);
    }

    public static function deleteFinal(string $path): void
    {
        Storage::disk(self::finalDisk())->delete(self::normalizePath($path));
    }

    public static function normalizePath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    public static function storeCompressedWebp(
        ImageCompressionService $imageService,
        string $temporaryPath,
        string $targetDirectory,
        ?string $filename = null,
    ): ?string {
        $temporaryDisk = self::temporaryDisk();

        if (! Storage::disk($temporaryDisk)->exists($temporaryPath)) {
            return null;
        }

        [$temporaryFilePath, $shouldDeleteLocalCopy] = self::localPathFor($temporaryDisk, $temporaryPath);

        try {
            $uploadedFile = new UploadedFile(
                $temporaryFilePath,
                basename($temporaryPath),
                mime_content_type($temporaryFilePath) ?: null,
                null,
                true
            );

            $filename ??= Str::ulid()->toBase32() . '.webp';
            $compressed = $imageService->convertToWebP($uploadedFile);
            $targetPath = trim($targetDirectory, '/') . '/' . $filename;

            Storage::disk(self::finalDisk())->put($targetPath, (string) $compressed);
        } finally {
            if ($shouldDeleteLocalCopy) {
                @unlink($temporaryFilePath);
            }
        }

        Storage::disk($temporaryDisk)->delete($temporaryPath);

        return $filename;
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private static function localPathFor(string $disk, string $path): array
    {
        $storage = Storage::disk($disk);

        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            return [$storage->path($path), false];
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'dailyfin-upload-');
        file_put_contents($temporaryFile, $storage->get($path));

        return [$temporaryFile, true];
    }
}
