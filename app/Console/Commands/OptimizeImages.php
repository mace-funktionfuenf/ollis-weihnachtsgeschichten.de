<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Product;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-time cleanup for images downloaded before ImageOptimizer existed -
 * new downloads are optimized automatically (see ImportWordPress::downloadTo()),
 * but everything already sitting in storage needs this run once. Safe to
 * re-run; already-optimized files are simply re-processed (a no-op in
 * practice since they're already within the size cap).
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize';

    protected $description = 'Resize and re-compress already-downloaded post/product images in place';

    public function handle(): int
    {
        $this->optimizeDirectory('posts', Post::class, 'featured_image');
        $this->optimizeDirectory('products', Product::class, 'image_path');

        return self::SUCCESS;
    }

    /** @param class-string<Post|Product> $model */
    private function optimizeDirectory(string $directory, string $model, string $column): void
    {
        $files = Storage::disk('public')->files($directory);
        $before = 0;
        $after = 0;
        $converted = 0;

        foreach ($files as $relativePath) {
            $absolutePath = Storage::disk('public')->path($relativePath);
            $before += filesize($absolutePath);

            $newBasename = ImageOptimizer::optimize($absolutePath);

            if ($newBasename) {
                $newRelativePath = $directory.'/'.$newBasename;
                $model::where($column, $relativePath)->update([$column => $newRelativePath]);
                $after += filesize(Storage::disk('public')->path($newRelativePath));
                $converted++;
            } else {
                $after += filesize($absolutePath);
            }
        }

        $savedMb = round(($before - $after) / 1_048_576, 1);
        $this->info("{$directory}: ".count($files)." files, {$converted} converted to JPEG, {$savedMb} MB saved.");
    }
}
