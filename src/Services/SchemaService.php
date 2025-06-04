<?php

namespace ZeeshanTariq\FilamentSqlGen\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class SchemaService
{
    protected string $yamlPath;

    public function __construct()
    {
        $this->yamlPath = base_path('database/schema/database_schema.yaml');
    }

    public function getSchemaSummary(): string
    {
        $hash = $this->getSchemaFileHash();
        $cacheKey = 'sqlgen_schema_summary_' . $hash;

        return Cache::remember($cacheKey, now()->addHours(1), function () {
            $yamlSchema = $this->parseYaml();

            $tables = DB::select('SHOW TABLES');
            $summary = [];

            foreach ($tables as $tableObj) {
                $tableName = array_values((array)$tableObj)[0];
                $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
                $columnNames = array_map(fn($col) => $col->Field, $columns);

                if (isset($yamlSchema[$tableName])) {
                    $description = $yamlSchema[$tableName]['description'] ?? 'No description provided.';
                    $summary[] = "- {$tableName}: {$description}\n  Columns: " . implode(', ', $columnNames);
                } else {
                    $summary[] = "- {$tableName}\n  Columns: " . implode(', ', $columnNames);
                }
            }

            return implode("\n", $summary);
        });
    }

    public function getSchemaFileHash(): string
    {
        return file_exists($this->yamlPath) ? md5_file($this->yamlPath) : 'no-schema';
    }

    protected function parseYaml(): array
    {
        if (!file_exists($this->yamlPath)) {
            return [];
        }

        try {
            $parsed = Yaml::parseFile($this->yamlPath);
            if (isset($parsed['tables']) && is_array($parsed['tables'])) {
                return $parsed['tables'];
            }

            Log::warning("Missing or invalid 'tables' key in YAML schema.");
        } catch (\Exception $e) {
            Log::error("Failed to parse YAML schema", ['exception' => $e->getMessage()]);
        }

        return [];
    }
}
