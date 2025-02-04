<?php

namespace App\Providers;

use App\Models\Curriculum;
use App\Observers\CurriculumObserver;
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
        Curriculum::observe(CurriculumObserver::class);
    }
}
