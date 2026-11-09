<?php

namespace App\Support;

use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MediaStorage
{
    /**
     * Yuklangan rasm/faylni diskka va (agar bulutli S3 bo'lmasa) ma'lumotlar bazasiga (stored_media)
     * siqilgan holda zaxiralab saqlaydi.
     */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $rawContents = @file_get_contents($file->getRealPath());
        $mimeType = $file->getMimeType() ?: ($file->getClientMimeType() ?: 'image/jpeg');

        if ($rawContents !== false && $rawContents !== '') {
            $rawContents = self::optimizeImageBinary($rawContents, $mimeType);
        }

        $path = $file->store($directory, $disk);
        $normalizedPath = self::normalizeRelativePath($path);

        if ($rawContents !== false && $rawContents !== '' && self::shouldBackupToDatabase($disk, strlen($rawContents))) {
            self::persistToDatabase($normalizedPath, $disk, $mimeType, strlen($rawContents), $rawContents);
        }

        return $normalizedPath;
    }

    /**
     * Rasm/faylni diskdan va ma'lumotlar bazasi zaxirasidan o'chiradi.
     */
    public static function delete(?string $path, string $disk = 'public'): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return;
        }

        $clean = self::normalizeRelativePath($path);

        try {
            Storage::disk($disk)->delete($clean);
        } catch (Throwable) {
        }

        try {
            if (Schema::hasTable('stored_media')) {
                DB::table('stored_media')->where('path', $clean)->delete();
            }
        } catch (Throwable) {
        }
    }

    /**
     * Istalgan saqlangan rasm yo'lini domen/port/HTTPS muammosiz ochiladigan root-relative URL ga aylantiradi.
     */
    public static function url(?string $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $trimmed = trim($path);
        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, 'data:image/')) {
            return $trimmed;
        }

        // Agar eski bazada http://localhost.../storage/... bo'lib qolgan bo'lsa, uni toza /media/... ga o'giramiz
        if (preg_match('~^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?/(?:storage|media)/(.+)$~i', $trimmed, $m)) {
            return '/media/'.ltrim($m[1], '/');
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }

        $clean = self::normalizeRelativePath($trimmed);

        return '/media/'.$clean;
    }

    /**
     * Faylni diskdan yoki (disk bo'shab qolgan bo'lsa) bazadagi stored_media jadvalidan qayta tiklab brauzerga uzatadi.
     */
    public static function response(?string $path, string $disk = 'public'): Response|StreamedResponse
    {
        if (! is_string($path) || trim($path) === '') {
            abort(404);
        }

        $clean = self::normalizeRelativePath($path);

        if ($clean === '' || str_contains($clean, '..')) {
            abort(404);
        }

        $storage = Storage::disk($disk);

        if ($storage->exists($clean)) {
            $binary = $storage->get($clean);
            $mime = $storage->mimeType($clean) ?: self::guessMimeType($clean);

            // Agar diskda bor bo'lib, bazada hali zaxiralanmagan bo'lsa, avtomatik zaxiraga olamiz
            if (self::shouldBackupToDatabase($disk, strlen($binary))) {
                self::ensureBackedUp($clean, $disk, $mime, $binary);
            }

            return self::buildHttpResponse($clean, $binary, $mime);
        }

        // Diskda topilmasa (masalan Render redeploy/restart bo'lganda), bazadan olib diskka ham qayta yozamiz
        try {
            if (Schema::hasTable('stored_media')) {
                $record = DB::table('stored_media')->where('path', $clean)->first();

                if ($record && ! empty($record->contents)) {
                    $binary = base64_decode((string) $record->contents, true);

                    if ($binary !== false && $binary !== '') {
                        try {
                            $storage->put($clean, $binary);
                        } catch (Throwable) {
                        }

                        $mime = $record->mime_type ?: self::guessMimeType($clean);

                        return self::buildHttpResponse($clean, $binary, $mime);
                    }
                }
            }
        } catch (Throwable) {
        }

        abort(404);
    }

    private static function buildHttpResponse(string $path, string $binary, string $mime): Response
    {
        $etag = '"'.md5($path.':'.strlen($binary)).'"';
        $ifNoneMatch = request()->header('If-None-Match');

        if ($ifNoneMatch !== null && trim($ifNoneMatch) === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        return response($binary, 200, [
            'Content-Type' => $mime,
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * S3 / Cloudflare R2 kabi doimiy bulutli disklarda yoki hajmi haddan tashqari katta fayllarda
     * SQL bazani shishirmaslik uchun zaxiralash kerak-kerakmasligini aniqlaydi.
     */
    private static function shouldBackupToDatabase(string $disk, int $byteSize): bool
    {
        if (! config('filesystems.media_db_backup', true)) {
            return false;
        }

        $driver = (string) config("filesystems.disks.{$disk}.driver", 'local');
        if ($driver === 's3') {
            return false;
        }

        $maxBytes = (int) config('filesystems.media_db_backup_max_bytes', 5 * 1024 * 1024);

        return $byteSize > 0 && $byteSize <= $maxBytes;
    }

    /**
     * Katta hajmli JPEG/WEBP rasmlarni (300 KB dan katta bo'lsa va GD mavjud bo'lsa)
     * sifatini yo'qotmagan holda (max 1920px, 82% sifat) avtomatik ixchamlashtiradi.
     */
    private static function optimizeImageBinary(string $rawContents, string $mimeType): string
    {
        if (strlen($rawContents) < 300 * 1024 || ! function_exists('imagecreatefromstring')) {
            return $rawContents;
        }

        if (! in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/webp'], true)) {
            return $rawContents;
        }

        try {
            $image = @imagecreatefromstring($rawContents);
            if ($image === false) {
                return $rawContents;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $maxDimension = 1920;

            if ($width > $maxDimension || $height > $maxDimension) {
                $ratio = min($maxDimension / $width, $maxDimension / $height);
                $newWidth = max(1, (int) round($width * $ratio));
                $newHeight = max(1, (int) round($height * $ratio));

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                if ($resized !== false) {
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resized;
                }
            }

            ob_start();
            if ($mimeType === 'image/webp' && function_exists('imagewebp')) {
                imagewebp($image, null, 82);
            } else {
                imagejpeg($image, null, 82);
            }
            $compressed = ob_get_clean();
            imagedestroy($image);

            if (is_string($compressed) && $compressed !== '' && strlen($compressed) < strlen($rawContents)) {
                return $compressed;
            }
        } catch (Throwable) {
        }

        return $rawContents;
    }

    private static function normalizeRelativePath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $normalized = ltrim($normalized, '/');

        foreach (['storage/app/public/', 'storage/app/private/', 'storage/', 'public/', 'media/'] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                $normalized = substr($normalized, strlen($prefix));
            }
        }

        return ltrim($normalized, '/');
    }

    private static function persistToDatabase(string $path, string $disk, string $mimeType, int $size, string $rawContents): void
    {
        try {
            if (! Schema::hasTable('stored_media')) {
                return;
            }

            DB::table('stored_media')->updateOrInsert(
                ['path' => $path],
                [
                    'disk' => $disk,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'contents' => base64_encode($rawContents),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (Throwable) {
        }
    }

    private static function ensureBackedUp(string $path, string $disk, string $mimeType, string $rawContents): void
    {
        try {
            if (! Schema::hasTable('stored_media')) {
                return;
            }

            if (! DB::table('stored_media')->where('path', $path)->exists()) {
                self::persistToDatabase($path, $disk, $mimeType, strlen($rawContents), $rawContents);
            }
        } catch (Throwable) {
        }
    }

    private static function guessMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            'bmp' => 'image/bmp',
            default => 'image/jpeg',
        };
    }
}
