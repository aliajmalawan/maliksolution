<?php

namespace App\Core;

/**
 * GD-based "cover crop" resize to an exact width x height, for making
 * inconsistently-sized uploads look uniform/professional (e.g. forcing every
 * hero image to 1920x800). Unlike MediaCompressor (automatic, best-effort,
 * swallows failures), this is a deliberate admin action — failures are
 * thrown so the admin sees a clear error instead of a silent no-op.
 */
class MediaResizer
{
    private const JPEG_QUALITY = 85;
    private const PNG_COMPRESSION = 6;
    private const WEBP_QUALITY = 85;
    private const MIN_DIMENSION = 16;
    private const MAX_DIMENSION = 4000;

    public static function resizeToExact(string $absolutePath, string $mime, int $targetWidth, int $targetHeight): void
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('The GD extension is not available on this server — resizing is unsupported.');
        }

        if ($targetWidth < self::MIN_DIMENSION || $targetHeight < self::MIN_DIMENSION
            || $targetWidth > self::MAX_DIMENSION || $targetHeight > self::MAX_DIMENSION) {
            throw new \RuntimeException('Width and height must be between ' . self::MIN_DIMENSION . ' and ' . self::MAX_DIMENSION . ' pixels.');
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            'image/gif' => @imagecreatefromgif($absolutePath),
            default => null,
        };

        if (!$source) {
            throw new \RuntimeException('Could not read this file as an image (unsupported or corrupt).');
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        // Cover-crop: crop the source to the target aspect ratio (centered),
        // then scale to the exact target size — avoids distorting the image.
        $srcRatio = $srcWidth / $srcHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($srcRatio > $targetRatio) {
            $cropHeight = $srcHeight;
            $cropWidth = (int) round($srcHeight * $targetRatio);
            $cropX = (int) round(($srcWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $srcWidth;
            $cropHeight = (int) round($srcWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcHeight - $cropHeight) / 2);
        }

        $result = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($result, false);
        imagesavealpha($result, true);
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        imagecopyresampled(
            $result, $source,
            0, 0, $cropX, $cropY,
            $targetWidth, $targetHeight, $cropWidth, $cropHeight
        );
        imagedestroy($source);

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($result, $absolutePath, self::JPEG_QUALITY),
            'image/png' => imagepng($result, $absolutePath, self::PNG_COMPRESSION),
            'image/webp' => function_exists('imagewebp') ? imagewebp($result, $absolutePath, self::WEBP_QUALITY) : false,
            'image/gif' => imagegif($result, $absolutePath),
            default => false,
        };

        imagedestroy($result);

        if (!$saved) {
            throw new \RuntimeException('Could not save the resized image.');
        }
    }
}
