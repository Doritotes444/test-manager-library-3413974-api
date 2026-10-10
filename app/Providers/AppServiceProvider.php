<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Bootstrap\Composition\PortBindings;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(PortBindings::class);
    }

    public function boot(): void
    {
        //
    }
}
