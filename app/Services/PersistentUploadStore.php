<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersistentUploadStore
{
    public function store(UploadedFile $file, string $directory, string $disk): string
    {
        if (config('deployment.upload_storage') !== 'database') {
            return $file->store($directory, $disk);
        }

        $path = trim($directory, '/').'/'.$file->hashName();
        DB::table('persistent_uploads')->insert([
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'content_base64' => base64_encode($file->getContent()),
        ]);

        return $path;
    }

    public function exists(string $path, string $disk): bool
    {
        return config('deployment.upload_storage') === 'database'
            ? DB::table('persistent_uploads')->where('disk', $disk)->where('path', $path)->exists()
            : Storage::disk($disk)->exists($path);
    }

    public function delete(string $path, string $disk): void
    {
        if (config('deployment.upload_storage') === 'database') {
            DB::table('persistent_uploads')->where('disk', $disk)->where('path', $path)->delete();
        } else {
            Storage::disk($disk)->delete($path);
        }
    }

    public function download(string $path, string $disk, ?string $name = null, array $headers = [], bool $inline = false): StreamedResponse
    {
        if (config('deployment.upload_storage') !== 'database') {
            abort_unless(Storage::disk($disk)->exists($path), 404);
            return Storage::disk($disk)->download($path, $name, $headers);
        }

        $file = DB::table('persistent_uploads')->where('disk', $disk)->where('path', $path)->first();
        abort_unless($file, 404);

        return response()->streamDownload(
            fn () => print(base64_decode($file->content_base64, true)),
            $name ?: basename($path),
            $headers + ['Content-Type' => $file->mime_type, 'X-Content-Type-Options' => 'nosniff'],
            $inline ? 'inline' : 'attachment',
        );
    }
}
