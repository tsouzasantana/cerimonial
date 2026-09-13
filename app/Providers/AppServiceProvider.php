<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Limita tentativas de CPF no gate do portal público por token + IP,
        // evitando que alguém de posse do link tente descobrir o CPF por
        // força bruta (o CPF é a única "senha" do portal do cliente).
        RateLimiter::for('public-verify', function (Request $request) {
            return Limit::perMinute(10)->by($request->route('token').'|'.$request->ip());
        });
    }
}
