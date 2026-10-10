<?php

namespace App\Adapters\Outbound\Storage;

use App\Application\Ports\Outbound\FileStorageInterface;
use Illuminate\Support\Facades\Storage;

class LocalCoverStorage implements FileStorageInterface
{
    public function store(mixed $file, string $path = 'covers'): string
    {
        if (is_string($file)) {
            return $file;
        }
        $storedPath = Storage::disk('public')->putFile($path, $file);
        return Storage::url($storedPath);
    }

    public function delete(string $filePath): void
    {
        $relative = str_replace('/storage/', '', $filePath);
        Storage::disk('public')->delete($relative);
    }
}
