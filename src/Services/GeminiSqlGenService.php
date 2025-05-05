<?php
namespace ZeeshanTariq\FilamentSqlGen\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiSqlGenService implements SqlGenServiceInterface
{
    public function generateSql(string $question): string
    {
        try {
            $apiKey = config('filament-sqlgen.gemini.api_key');
            $endpoint = config('filament-sqlgen.gemini.endpoint');

            $endpointWithKey = "{$endpoint}?key={$apiKey}";

            $response = Http::post($endpointWithKey, [
                'contents' => [
                    ['parts' => [['text' => $this->buildPrompt($question)]]]
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Gemini API request failed', ['exception' => $e->getMessage()]);
            return '';
        }
    }

    protected function buildPrompt(string $question): string
    {
        $schema = $this->getDatabaseSchemaSummary();

        return <<<EOT
You are an expert SQL assistant for a Laravel MySQL application.

Use only the following tables and columns from the database:

{$schema}

Instructions:
- Only return a valid SELECT SQL query.
- Do NOT invent tables or columns.
- Do NOT include markdown or comments.
- Use lowercase column names in Laravel-style like 'created_at'.

User Question: {$question}
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
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $text = preg_replace('/```(.*?)```/is', '$1', $text);
        $text = preg_replace('/--.*$/m', '', $text);
        $text = preg_replace('/\/\*.*?\*\//s', '', $text);
        preg_match('/^(.*?;)/s', trim($text), $matches);

        return isset($matches[1]) ? trim($matches[1]) : trim($text);
    }
}
