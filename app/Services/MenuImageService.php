<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class MenuImageService
{
    public function convertAndStore(UploadedFile $file, ?string $oldPath = null): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->decode($file);
        $image->scale(width: 800);

        $filename = 'menu_'.time().'_'.uniqid().'.webp';
        $path = 'menus/'.$filename;

        $encoded = $image->encodeUsingFormat(Format::WEBP, quality: 70);
        $binary = base64_decode($encoded->toBase64());
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function getImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return asset('storage/'.$path);
    }
}
