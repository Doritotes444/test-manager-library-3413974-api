<?php

namespace App\Adapters\Inbound\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Application\Ports\Outbound\TokenGeneratorInterface;
use App\Application\Ports\Outbound\UserRepositoryInterface;
use App\Adapters\Inbound\Http\Responses\StandardApiResponse;

class JwtAuthMiddleware
{
    public function __construct(
        private TokenGeneratorInterface $tokenGen,
        private UserRepositoryInterface $userRepo
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return StandardApiResponse::error('UNAUTHORIZED', 'Token de autenticación no proporcionado', [], 401);
        }

        $token = substr($authHeader, 7);
        $payload = $this->tokenGen->decode($token);
        if (!$payload || !isset($payload['sub'])) {
            return StandardApiResponse::error('UNAUTHORIZED', 'Token inválido o expirado', [], 401);
        }

        $user = $this->userRepo->findById($payload['sub']);
        if (!$user) {
            return StandardApiResponse::error('UNAUTHORIZED', 'Usuario del token no existe', [], 401);
        }

        $request->merge(['auth_user' => $user]);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
