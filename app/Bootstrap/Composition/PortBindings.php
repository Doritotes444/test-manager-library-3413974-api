<?php

namespace App\Bootstrap\Composition;

use Illuminate\Support\ServiceProvider;
use App\Application\Ports\Outbound\BookRepositoryInterface;
use App\Application\Ports\Outbound\CategoryRepositoryInterface;
use App\Application\Ports\Outbound\LoanRepositoryInterface;
use App\Application\Ports\Outbound\MemberRepositoryInterface;
use App\Application\Ports\Outbound\UserRepositoryInterface;
use App\Application\Ports\Outbound\MostBorrowedReportQueryInterface;
use App\Application\Ports\Outbound\ClockInterface;
use App\Application\Ports\Outbound\PasswordHasherInterface;
use App\Application\Ports\Outbound\TokenGeneratorInterface;
use App\Application\Ports\Outbound\FileStorageInterface;
use App\Application\Ports\Outbound\UnitOfWorkInterface;
use App\Adapters\Outbound\Persistence\Repositories\EloquentBookRepository;
use App\Adapters\Outbound\Persistence\Repositories\EloquentCategoryRepository;
use App\Adapters\Outbound\Persistence\Repositories\EloquentLoanRepository;
use App\Adapters\Outbound\Persistence\Repositories\EloquentMemberRepository;
use App\Adapters\Outbound\Persistence\Repositories\EloquentUserRepository;
use App\Adapters\Outbound\Persistence\Repositories\DatabaseMostBorrowedReportQuery;
use App\Adapters\Outbound\Persistence\EloquentUnitOfWork;
use App\Adapters\Outbound\Clock\SystemClock;
use App\Adapters\Outbound\Security\Argon2PasswordHasher;
use App\Adapters\Outbound\Security\JwtTokenIssuer;
use App\Adapters\Outbound\Storage\LocalCoverStorage;

/**
 * PortBindings — Único Punto de Composición (Artículo III de Constitution)
 *
 * Aquí es donde cada puerto del núcleo se encuentra con su adaptador de salida.
 * Fuera de este módulo no se instancia infraestructura.
 */
class PortBindings extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClockInterface::class, SystemClock::class);
        $this->app->singleton(PasswordHasherInterface::class, Argon2PasswordHasher::class);
        $this->app->singleton(TokenGeneratorInterface::class, JwtTokenIssuer::class);
        $this->app->singleton(FileStorageInterface::class, LocalCoverStorage::class);
        $this->app->singleton(UnitOfWorkInterface::class, EloquentUnitOfWork::class);

        $this->app->singleton(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->singleton(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->singleton(MemberRepositoryInterface::class, EloquentMemberRepository::class);
        $this->app->singleton(BookRepositoryInterface::class, EloquentBookRepository::class);
        $this->app->singleton(LoanRepositoryInterface::class, EloquentLoanRepository::class);
        $this->app->singleton(MostBorrowedReportQueryInterface::class, DatabaseMostBorrowedReportQuery::class);
    }

    public function boot(): void
    {
        //
    }
}
