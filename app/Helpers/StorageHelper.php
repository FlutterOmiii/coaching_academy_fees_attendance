<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageHelper
{
    /**
     * Use the e2e object store when it is configured, otherwise fall back to
     * the local public disk so uploads still work in development.
     */
    public static function disk(): string
    {
        return filled(config('filesystems.disks.e2e.key')) && filled(config('filesystems.disks.e2e.secret'))
            ? 'e2e'
            : 'public';
    }

    public static function upload(UploadedFile $file, string $folder = 'uploads'): string
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $folder.'/'.$filename;

        Storage::disk(self::disk())->put($path, file_get_contents($file), 'public');

        return $path;
    }

    public static function url(string $path): string
    {
        if (empty($path)) {
            return '';
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (self::disk() === 'public') {
            return Storage::disk('public')->url($path);
        }

        $endpoint = rtrim((string) config('filesystems.disks.e2e.endpoint', ''), '/');
        $bucket = config('filesystems.disks.e2e.bucket', '');

        return "{$endpoint}/{$bucket}/{$path}";
    }

    public static function delete(?string $path): void
    {
        if (! empty($path) && ! str_starts_with($path, 'http')) {
            Storage::disk(self::disk())->delete($path);
        }
    }
}
