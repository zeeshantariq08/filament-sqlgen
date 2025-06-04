<?php

namespace ZeeshanTariq\FilamentSqlGen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearSchemaCache extends Command
{
    protected $signature = 'filament-sqlgen:clear-schema';

    protected $description = 'Clear the SQLGen schema summary cache';

    public function handle()
    {
        $yamlPath = base_path('database/schema/database_schema.yaml');
        $hash = file_exists($yamlPath) ? md5_file($yamlPath) : 'no-schema';
        $cacheKey = 'sqlgen_schema_summary_' . $hash;

        if (Cache::forget($cacheKey)) {
            $this->info("Schema cache cleared: {$cacheKey}");
        } else {
            $this->info("No schema cache found for key: {$cacheKey}");
        }

        return 0;
    }
}
