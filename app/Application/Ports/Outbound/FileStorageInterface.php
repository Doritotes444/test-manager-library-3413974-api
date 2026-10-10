<?php
declare(strict_types=1);
namespace App\Application\Ports\Outbound;
interface FileStorageInterface {
    public function storeCover(string $tempPath): string;
    public function deleteCover(string $key): void;
    public function getCoverPath(string $key): ?string;
}