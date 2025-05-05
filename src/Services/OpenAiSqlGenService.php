<?php


namespace ZeeshanTariq\FilamentSqlGen\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiSqlGenService implements SqlGenServiceInterface
{
    public function generateSql(string $question): string
    {
        try {
            $apiKey = config('filament-sqlgen.openai.api_key');
            $endpoint = config('filament-sqlgen.openai.endpoint');
            $model = config('filament-sqlgen.openai.model');

            $response = Http::withToken($apiKey)->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                    ['role' => 'user', 'content' => $question],
                ],
                'temperature' => 0.2,
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('OpenAI API request failed', ['exception' => $e->getMessage()]);
            return '';
        }
    }

    protected function buildSystemPrompt(): string
    {
        $schema = $this->getDatabaseSchemaSummary();

        return <<<EOT
You are a SQL assistant for a Laravel MySQL application.

Use only the following tables and columns from the database:

{$schema}

Instructions:
- Only return a valid SELECT SQL query.
- Do NOT invent tables or columns.
- Do NOT include markdown or comments.
- Use lowercase column names in Laravel-style like 'created_at'.
EOT;
    }

    protected function getDatabaseSchemaSummary(): string
    {
        $tables = DB::select('SHOW TABLES');
        $schema = [];

        foreach ($tables as $tableObj) {
            $tableName = array_values((array)$tableObj)[0];
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
            $columnNames = array_map(fn($col) => $col->Field, $columns);
            $schema[] = "- {$tableName}(" . implode(', ', $columnNames) . ")";
        }

        return implode("\n", $schema);
    }

    protected function parseResponse($response): string
    {
        $data = $response->json();
        $text = $data['choices'][0]['message']['content'] ?? '';

        $text = preg_replace('/```(.*?)```/is', '$1', $text);
        $text = preg_replace('/--.*$/m', '', $text);
        $text = preg_replace('/\/\*.*?\*\//s', '', $text);
        preg_match('/^(.*?;)/s', trim($text), $matches);

        return isset($matches[1]) ? trim($matches[1]) : trim($text);
    }
}
