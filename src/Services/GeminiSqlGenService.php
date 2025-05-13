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

            $temperature = config('filament-sqlgen.gemini.temperature', 0.2);
            $maxTokens = config('filament-sqlgen.gemini.max_output_tokens', 1024);

            $response = Http::post($endpointWithKey, [
                'contents' => [
                    ['parts' => [['text' => $this->buildPrompt($question)]]]
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'topK' => 1,
                    'topP' => 1.0,
                    'maxOutputTokens' => $maxTokens,
                    'stopSequences' => []
                ]
            ]);

            dd($response->json(), $question);


            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Gemini API request failed', ['exception' => $e->getMessage()]);
            return [
                'queries' => [],
                'notes' => ['ℹ️ Something went wrong.']
            ];
        }
    }

    protected function buildPrompt(string $question): string
    {
        $schema = $this->getDatabaseSchemaSummary();
        $today = now()->format('Y-m-d');
        $currentYear = now()->year;

        return <<<EOT
You are a strict SQL assistant for a Laravel application using a MySQL database. Always generate valid MySQL syntax only.
ONLY use the tables and columns provided below. DO NOT invent any columns or tables.

Schema (use ONLY these tables and columns):
{$schema}

Assume today's date is {$today}. If the user asks about a specific month (e.g. "April") but does not mention a year, always assume they mean {$currentYear}.

Instructions:
- ✅ Always return one or more SELECT SQL queries based on the user request.
- ✅ For each query, start with a short plain-text description or heading (e.g. "🔍 List of users created this month"), followed by the SQL query.
- ✅ If multiple queries are needed, separate each with a blank line.
- ❌ NEVER use markdown (no ```sql).
- ❌ NEVER return queries that modify data (INSERT, UPDATE, DELETE, etc.).
- ❌ Do NOT invent columns or tables not present in the schema.
- ❌ If any requested column is not found in the schema, SKIP it and ADD a note like:
  "ℹ️ Column 'postcode' not found. Using only available columns."
  Always add a note when a column is missing, even if the query is valid without it.
- Do NOT include markdown (no ```sql).
- Use lowercase column names like 'created_at'.
- Use lowercase column names like 'created_at'.

Sensitive Data:
- If user asks to exclude sensitive data, skip columns like password, token, api_key, secret.

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
        $cleanText = trim($rawText);

        // Split based on the 🔍 indicator
        $blocks = preg_split('/🔍\s*/', $cleanText, -1, PREG_SPLIT_NO_EMPTY);

        $queries = [];

        foreach ($blocks as $block) {
            $lines = preg_split('/\r\n|\r|\n/', trim($block));

            if (count($lines) >= 2) {
                $description = '🔍 ' . trim($lines[0]);
                $query = implode("\n", array_slice($lines, 1));

                // Clean the SQL query by removing markdown code block syntax (```sql and ``` )
                $query = preg_replace('/```sql|```/', '', $query);

                $queries[] = [
                    'description' => $description,
                    'query' => trim($query),
                ];
            }
        }

        // Extract notes like ℹ️ and ❌
        preg_match_all('/(ℹ️|❌)[^\n]*/', $cleanText, $noteMatches);
        $notes = $noteMatches[0] ?? [];

        return [
            'queries' => $queries,
            'notes' => $notes,
        ];
    }



}
