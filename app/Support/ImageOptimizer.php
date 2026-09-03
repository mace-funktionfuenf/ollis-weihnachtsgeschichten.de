<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Downloaded post/product images arrive at their original camera or
 * WordPress-media-library resolution (routinely several megapixels, 1-3MB)
 * even though the largest place any of them is ever displayed is a
 * ~700px-wide hero banner or a small card thumbnail. This resizes them down
 * to a sane display size and re-compresses in place, using GD (ships with
 * PHP) rather than adding an image-processing package.
 */
class ImageOptimizer
{
    private const MAX_WIDTH = 1200;

    private const JPEG_QUALITY = 82;

    /**
     * Optimizes the image at $absolutePath in place. Returns the new
     * basename if the file's extension changed (a photographic PNG with no
     * real transparency was converted to JPEG, since PNG compresses photos
     * far worse than the same content as JPEG) so the caller can update
     * whatever DB column references the old filename. Returns null if the
     * path is unchanged (including when nothing needed doing, or the file
     * isn't a type GD can handle).
     */
    /**
     * Stores a Filament FileUpload's temporary file and optimizes it in the
     * same step, so an image uploaded straight through the admin panel gets
     * the same size cap as one pulled in by the WordPress importer - the
     * cap applies regardless of how large the original upload was.
     */
    public static function storeAndOptimize(TemporaryUploadedFile $file, string $directory, string $disk = 'public'): ?string
    {
        $path = $file->store($directory, $disk);

        if (! $path) {
            return $path;
        }

        $newBasename = self::optimize(Storage::disk($disk)->path($path));

        return $newBasename ? dirname($path).'/'.$newBasename : $path;
    }

    public static function optimize(string $absolutePath): ?string
    {
        if (! extension_loaded('gd') || ! is_file($absolutePath)) {
            return null;
        }

        set_error_handler(fn () => true);

        try {
            $info = getimagesize($absolutePath);

            if (! $info) {
                return null;
            }

            [$width, $height, $type] = $info;

            $image = match ($type) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($absolutePath),
                IMAGETYPE_PNG => imagecreatefrompng($absolutePath),
                default => null,
            };
        } finally {
            restore_error_handler();
        }

        if (! $image) {
            return null;
        }

        if ($width > self::MAX_WIDTH) {
            $newWidth = self::MAX_WIDTH;
            $newHeight = max(1, (int) round($height * ($newWidth / $width)));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }

        $newBasename = null;

        if ($type === IMAGETYPE_PNG && ! self::hasTransparency($image, $width, $height)) {
            $newPath = dirname($absolutePath).'/'.pathinfo($absolutePath, PATHINFO_FILENAME).'.jpg';
            imagejpeg($image, $newPath, self::JPEG_QUALITY);
            unlink($absolutePath);
            $newBasename = basename($newPath);
        } elseif ($type === IMAGETYPE_JPEG) {
            imagejpeg($image, $absolutePath, self::JPEG_QUALITY);
        } else {
            imagepng($image, $absolutePath, 6);
        }

        return $newBasename;
    }

    /**
     * Samples a grid of points rather than every pixel - fast even on a
     * large source image, and good enough to tell "a real photo" (safe to
     * convert to JPEG) from "a graphic that actually needs alpha".
     */
    private static function hasTransparency(\GdImage $image, int $width, int $height): bool
    {
        $stepX = max(1, intdiv($width, 40));
        $stepY = max(1, intdiv($height, 40));

        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $alpha = (imagecolorat($image, $x, $y) & 0x7F000000) >> 24;

                if ($alpha > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
