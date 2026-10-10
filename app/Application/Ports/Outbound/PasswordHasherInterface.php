<?php
declare(strict_types=1);
namespace App\Application\Ports\Outbound;
interface PasswordHasherInterface {
    public function hash(string $plain): string;
    public function verify(string $plain, string $hash): bool;
}