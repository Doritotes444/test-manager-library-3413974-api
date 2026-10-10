<?php

namespace App\Adapters\Outbound\Security;

use App\Application\Ports\Outbound\PasswordHasherInterface;
use App\Application\Ports\Outbound\TokenGeneratorInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Argon2PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_ARGON2ID);
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }
}

class JwtTokenIssuer implements TokenGeneratorInterface
{
    private string $secret;
    private int $ttl;

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', 'manager_library_super_secret_jwt_key_3413974');
        $this->ttl = (int)env('JWT_TTL', 28800);
    }

    public function generate(array $payload): string
    {
        $now = time();
        $tokenPayload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->ttl,
            'iss' => 'manager-library-api'
        ]);

        return JWT::encode($tokenPayload, $this->secret, 'HS256');
    }

    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}
