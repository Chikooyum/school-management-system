<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;          // [TAMBAHKAN INI]
use App\Observers\UserObserver; // [TAMBAHKAN INI]

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // [TAMBAHKAN INI]
        // Mendaftarkan UserObserver secara manual di sini.
        User::observe(UserObserver::class);
    }
}
