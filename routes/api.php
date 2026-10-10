<?php

use Illuminate\Support\Facades\Route;
use App\Adapters\Inbound\Http\Controllers\HealthController;
use App\Adapters\Inbound\Http\Controllers\AuthController;
use App\Adapters\Inbound\Http\Controllers\UserController;
use App\Adapters\Inbound\Http\Controllers\CategoryController;
use App\Adapters\Inbound\Http\Controllers\BookController;
use App\Adapters\Inbound\Http\Controllers\MemberController;
use App\Adapters\Inbound\Http\Controllers\LoanController;
use App\Adapters\Inbound\Http\Controllers\ReportController;
use App\Adapters\Inbound\Http\Middleware\JwtAuthMiddleware;
use App\Adapters\Inbound\Http\Middleware\RequireAdminMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes — Manager Library (Hexagonal Architecture / Puertos y Adaptadores)
| Base URL: /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Health check (Público)
    Route::get('/health', HealthController::class);

    // Autenticación (Público)
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rutas protegidas por JWT
    Route::middleware([JwtAuthMiddleware::class])->group(function () {

        // Categorías
        Route::get('/categories', [CategoryController::class, 'index']);

        // Libros (Lectura autenticada)
        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/{id}', [BookController::class, 'show']);

        // Socios
        Route::get('/members', [MemberController::class, 'index']);
        Route::get('/members/{id}', [MemberController::class, 'show']);
        Route::post('/members', [MemberController::class, 'store']);
        Route::put('/members/{id}', [MemberController::class, 'update']);

        // Préstamos y Devoluciones
        Route::get('/loans', [LoanController::class, 'index']);
        Route::get('/loans/overdue', [LoanController::class, 'overdue']);
        Route::get('/loans/{id}', [LoanController::class, 'show']);
        Route::post('/loans', [LoanController::class, 'store']);
        
        // Devolución Canónica y Retrocompatible
        Route::post('/loans/{id}/items/{item_id}/return', [LoanController::class, 'returnItemCanonical']);
        Route::post('/loans/{id}/return', [LoanController::class, 'returnItem']);

        // Rutas exclusivas para Administrador
        Route::middleware([RequireAdminMiddleware::class])->group(function () {
            Route::post('/users', [UserController::class, 'store']);
            Route::post('/books', [BookController::class, 'store']);
            Route::put('/books/{id}', [BookController::class, 'update']);
            Route::delete('/books/{id}', [BookController::class, 'destroy']);
            Route::put('/books/{id}/cover', [BookController::class, 'updateCover']);
            Route::post('/books/{id}/cover', [BookController::class, 'updateCover']);
            Route::get('/reports/most-borrowed', [ReportController::class, 'mostBorrowed']);
        });
    });
});
