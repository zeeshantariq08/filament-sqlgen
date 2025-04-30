<?php

namespace ZeeshanTariq\FilamentSqlGen;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentSqlGenServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-sqlgen';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(); // Register the views
    }

    public function packageBooted(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-sqlgen');
    }
}
