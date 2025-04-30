<?php

namespace ZeeshanTariq\FilamentSqlGen;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Livewire\Livewire;
use ZeeshanTariq\FilamentSqlGen\Filament\Widgets\SqlGenWidget;

class FilamentSqlGenServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-sqlgen';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews()
            ->hasConfigFile(); // Register the config file
    }

    public function packageBooted(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-sqlgen');

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
    }
}
