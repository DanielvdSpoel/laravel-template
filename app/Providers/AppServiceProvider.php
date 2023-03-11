<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use pxlrbt\FilamentEnvironmentIndicator\FilamentEnvironmentIndicator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerViteTheme('resources/css/filament.css');
        });

        FilamentEnvironmentIndicator::configureUsing(function (FilamentEnvironmentIndicator $indicator) {
            $indicator->visible = fn () => ! App::environment('production');
        }, isImportant: true);
    }
}
