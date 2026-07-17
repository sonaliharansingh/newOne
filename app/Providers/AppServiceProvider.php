<?php

namespace App\Providers;

use App\Services\LogSmsGateway;
use App\Services\SmsGateway;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, LogSmsGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');
    }
}
