<?php

namespace App\Adapters\Inbound\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Adapters\Inbound\Http\Responses\StandardApiResponse;

class RequireAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->get('auth_user') ?? $request->user();
        if (!$user || $user->getRole() !== 'admin') {
            return StandardApiResponse::error('FORBIDDEN', 'Acceso denegado: se requiere rol de administrador', [], 403);
        }
        return $next($request);
    }
}
