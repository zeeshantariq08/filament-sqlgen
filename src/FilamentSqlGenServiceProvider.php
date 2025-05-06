<?php

namespace ZeeshanTariq\FilamentSqlGen;

use Filament\Facades\Filament;
use Spatie\LaravelPackageTools\Package;
use Filament\Resources\Resource;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Livewire\Livewire;
use ZeeshanTariq\FilamentSqlGen\Filament\Resources\SqlGenLogResource;
use ZeeshanTariq\FilamentSqlGen\Filament\Widgets\SqlGenWidget;

class FilamentSqlGenServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-sqlgen';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews()
            ->hasConfigFile(); // Removed hasMigration() to avoid duplication
    }

    public function packageBooted(): void
    {
        // Load the package views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-sqlgen');

        // Register the Livewire component
        Livewire::component(
            'zeeshan-tariq.filament-sql-gen.filament.widgets.sql-gen-widget',
            SqlGenWidget::class
        );
    }

    public function packageRegistered(): void
    {
        // Publish the config file
        $this->publishes([
            __DIR__ . '/../config/filament-sqlgen.php' => config_path('filament-sqlgen.php'),
        ], 'filament-sqlgen-config');

        // Publish the migration file (with dynamic timestamp to avoid duplicates)
        $this->publishes([
            __DIR__ . '/../database/migrations/create_sql_gen_logs_table.php' => database_path('migrations/' . date('Y_m_d_His') . '_create_sql_gen_logs_table.php'),
        ], 'filament-sqlgen-migrations');

        // Publish the database schema file
        $this->publishes([
            __DIR__ . '/../database/schema/database_schema.yaml' => database_path('schema/database_schema.yaml'),
        ], 'filament-sqlgen-schema');
    }

}
