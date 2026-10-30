<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Provides automatic image optimization for uploaded files.
 * Resizes images to max 1200px and converts to WebP format.
 */
trait OptimizesImages
{
    /**
     * Optimize an uploaded image file.
     *
     * @param  UploadedFile|string  $image  The uploaded file or existing path
     * @param  string  $directory  Target storage directory
     * @param  int  $maxWidth  Maximum width in pixels
     * @param  int  $quality  WebP quality (1-100)
     * @return string The stored file path
     */
    public function optimizeAndStoreImage(
        UploadedFile|string $image,
        string $directory = 'images',
        int $maxWidth = 1200,
        int $quality = 85
    ): string {
        // If it's already a path string, return it as-is
        if (is_string($image)) {
            return $image;
        }

        // Generate unique filename with .webp extension
        $filename = uniqid().'_'.time().'.webp';
        $path = $directory.'/'.$filename;

        // Load and optimize image using Intervention Image v3
        $manager = new ImageManager(new Driver);
        $optimizedImage = $manager->read($image)
            ->scaleDown(width: $maxWidth)
            ->toWebp($quality);

        // Store to disk
        Storage::disk('public')->put($path, (string) $optimizedImage);

        return $path;
    }

    /**
     * Optimize multiple images.
     *
     * @param  array  $images  Array of UploadedFile or path strings
     * @param  string  $directory  Target storage directory
     * @return array Array of stored file paths
     */
    public function optimizeAndStoreImages(
        array $images,
        string $directory = 'images'
    ): array {
        $paths = [];

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $paths[] = $this->optimizeAndStoreImage($image, $directory);
            } elseif (is_string($image) && ! empty($image)) {
                $paths[] = $image; // Keep existing paths
            }
        }

        return $paths;
    }
}
