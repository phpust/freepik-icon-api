<?php

namespace Freepik\IconApi;

use Illuminate\Support\ServiceProvider;

class FreepikServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('freepik-icon-api', function () {
            return new FreepikIconApi();
        });
    }

    public function boot(): void
    {
        // publishable config or migrations if needed in future
    }
}