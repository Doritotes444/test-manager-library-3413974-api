<?php
declare(strict_types=1);
namespace App\Application\Ports\Outbound;
interface TokenGeneratorInterface {
    public function generate(string $userId, string $role): string;
}