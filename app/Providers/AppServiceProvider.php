<?php

namespace App\Providers;

use App\Models\Curriculum;
use App\Observers\CurriculumObserver;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;
use Filament\Facades\Filament;
use App\Filament\Widgets\FileListWidget;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Curriculum::observe(CurriculumObserver::class);
        Filament::registerWidgets([
            FileListWidget::class,
        ]);
    }
}
