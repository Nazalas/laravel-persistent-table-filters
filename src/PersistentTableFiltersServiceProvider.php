<?php

namespace Nazalas\PersistentTableFilters;

use Illuminate\Support\ServiceProvider;

class PersistentTableFiltersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/persistent-table-filters.php',
            'persistent-table-filters'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/persistent-table-filters.php' => config_path('persistent-table-filters.php'),
            ], 'persistent-table-filters-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'persistent-table-filters-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
