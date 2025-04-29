<?php

namespace ZeeshanTariq\FilamentAiAgent;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentAiAgentServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-ai-agent';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(); // This will register the views
    }

    public function packageBooted(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-ai-agent');
    }

}
