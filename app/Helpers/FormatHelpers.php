<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

if (! function_exists('formatNumber')) {
    function formatNumber($number): string
    {
        return 'Rp.' . number_format($number, 0, ',', '.');
    }
}
if (! function_exists('formatDate')) {
    function formatDate($date): string
    {
        return date('d M Y', strtotime($date));
    }
}

if (! function_exists('upload_url')) {
    function upload_url(string $folder, string $filename): string
    {
        return \App\Support\UploadStorage::url('upload/' . trim($folder, '/') . '/' . $filename);
    }
}

if (!function_exists('cannot')) {
    function cannot(string $permission): bool
    {
        // dd(!Auth::user()->hasPermissionTo($permission));
        return !Auth::user()->hasPermissionTo($permission);
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        // dd(!Auth::user()->hasPermissionTo($permission));
        return Auth::user()->hasPermissionTo($permission);
    }
}

if (!function_exists('cannotAny')) {
    function cannotAny(array $permissions): bool
    {
        return !Auth::user()->hasAnyPermission($permissions);
    }
}


if (!function_exists('canAny')) {
    function canAny(array $permissions): bool
    {
        return Auth::user()->hasAnyPermission($permissions);
    }
}


if (!function_exists('isCoordinator')) {
    function isCoordinator()
    {
        return Auth::user()->hasPermissionTo('Coordinator Resources');
    }
}


if (! function_exists('isLateValidateDeposit')) {
    /**
     * Cek apakah setoran dianggap telat
     *
     * @param string|\DateTimeInterface $datePublished  Tanggal seharusnya setor (YYYY-mm-dd)
     * @param string|\DateTimeInterface $createdAt      Waktu input sebenarnya
     * @return bool
     */
    function isLateValidateDeposit($created_at, $datePublished): bool
    {
        $publishedDate = Carbon::parse($datePublished);
        $createdAt = Carbon::parse($created_at);

        // Deadline = H+1 jam 14:00
        $deadline = $publishedDate->copy()->addDay()->setTime(12, 0);
        // dd($deadline);

        return $createdAt->greaterThan($deadline);
    }
}
