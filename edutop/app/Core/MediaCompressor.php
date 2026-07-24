<?php

namespace App\Core;

/**
 * GD-based image resize/re-encode, run once after Uploader::store() saves a
 * validated image. No external image library needed (GD ships with PHP).
 * Failures are logged and swallowed — an uncompressed-but-valid upload is
 * always better than a failed one.
 */
class MediaCompressor
{
    private const MAX_WIDTH = 1920;
    private const JPEG_QUALITY = 82;
    private const PNG_COMPRESSION = 6; // 0 (none) - 9 (max), PNG is lossless either way
    private const WEBP_QUALITY = 82;

    public static function compress(string $absolutePath, string $mime): void
    {
        if (!extension_loaded('gd')) {
            Logger::warning('GD extension not available; skipping image compression', ['path' => $absolutePath]);
            return;
        }

        try {
            $image = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($absolutePath),
                'image/png' => imagecreatefrompng($absolutePath),
                'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($absolutePath) : null,
                'image/gif' => imagecreatefromgif($absolutePath), // not resized: could break animation
                default => null,
            };

            if (!$image) {
                return;
            }

            if ($mime !== 'image/gif') {
                $image = self::resizeIfNeeded($image);
            }

            match ($mime) {
                'image/jpeg' => imagejpeg($image, $absolutePath, self::JPEG_QUALITY),
                'image/png' => imagepng($image, $absolutePath, self::PNG_COMPRESSION),
                'image/webp' => function_exists('imagewebp') ? imagewebp($image, $absolutePath, self::WEBP_QUALITY) : null,
                default => null,
            };

            imagedestroy($image);
        } catch (\Throwable $e) {
            Logger::warning('Image compression failed', ['path' => $absolutePath, 'error' => $e->getMessage()]);
        }
    }

    private static function resizeIfNeeded(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= self::MAX_WIDTH) {
            return $image;
        }

        $newWidth = self::MAX_WIDTH;
        $newHeight = (int) round($height * ($newWidth / $width));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }
}
