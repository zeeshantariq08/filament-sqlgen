<?php

namespace ZeeshanTariq\FilamentSqlGen\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class GeminiSqlGenService implements SqlGenServiceInterface
{
    public function generateSql(string $question): array
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
            return [
                'sql' => '',
                'notes' => ['❌ Failed to connect to Gemini API.']
            ];
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
- ✅ Always try to generate a valid SELECT SQL query from the user request.
- ❗ Never return queries that modify data (DROP, DELETE, INSERT, UPDATE, TRUNCATE).
- ❗ If the user's intent is clearly to modify data, return no SQL and instead respond with: "❌ Only SELECT queries are allowed. Destructive operations are not supported."
- ❗ If the question includes non-existent columns or tables, skip them in the SQL and add a note like: "ℹ️ i could not find 'nationality' instead i found these: 'first_name', 'last_name', 'email', 'password', 'created_at', 'updated_at'."
- ❗ Do NOT reject queries just because a column isn't found. Instead, return the partial SQL and the note.
- Do NOT invent tables or columns.
- Do NOT include markdown (no ```sql).
- Use lowercase column names like 'created_at'.
- Assume all questions are safe unless they explicitly ask to *change* the data.

User Question: {$question}
EOT;
    }


    protected function getDatabaseSchemaSummary(): string
    {
        $yamlPath = base_path('database/schema/database_schema.yaml');
        $yamlSchema = [];

        if (file_exists($yamlPath)) {
            try {
                $parsedYaml = Yaml::parseFile($yamlPath);
                if (isset($parsedYaml['tables']) && is_array($parsedYaml['tables'])) {
                    $yamlSchema = $parsedYaml['tables'];
                } else {
                    Log::warning("The 'tables' key is missing or not an array in the YAML schema.");
                }
            } catch (\Exception $e) {
                Log::error('Error parsing YAML schema file', ['exception' => $e->getMessage()]);
            }
        }

        $tables = DB::select('SHOW TABLES');
        $schemaSummary = [];

        foreach ($tables as $tableObj) {
            $tableName = array_values((array)$tableObj)[0];
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
            $columnNames = array_map(fn($col) => $col->Field, $columns);

            if (isset($yamlSchema[$tableName])) {
                $description = $yamlSchema[$tableName]['description'] ?? 'No description provided.';
                $schemaSummary[] = "- {$tableName}: {$description}\n  Columns: " . implode(', ', $columnNames);
            } else {
                $schemaSummary[] = "- {$tableName}\n  Columns: " . implode(', ', $columnNames);
            }
        }

        return implode("\n", $schemaSummary);
    }

    protected function parseResponse($response): array
    {
        $data = $response->json();
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Remove markdown
        $cleanText = preg_replace('/```(sql)?|```/', '', $rawText);

        // Extract SQL part and note part
        $sql = trim(preg_replace('/ℹ️.*$/m', '', $cleanText)); // remove inline note
        $notes = [];

        // Extract any ℹ️ or ❌ notes
        if (preg_match_all('/(ℹ️|❌).*$/m', $cleanText, $matches)) {
            $notes = array_map('trim', $matches[0]);
        }

        return [
            'sql' => $sql,
            'notes' => $notes,
        ];
    }

}
