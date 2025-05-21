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

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Gemini API request failed', ['exception' => $e->getMessage()]);
            return [
                'sql' => '',
                'notes' => ['ℹ️ Something went wrong.']
            ];
        }
    }

    protected function buildPrompt(string $question): string
    {
        $schema = $this->getDatabaseSchemaSummary();
        $today = now()->format('Y-m-d');
        $currentYear = now()->year;

        $sensitiveColumns = ['password', 'secret', 'api_key', 'token'];

        $excludeSensitiveData = stripos($question, 'exclude sensitive data') !== false;

        return <<<EOT
You are a strict SQL assistant for a Laravel application using a MySQL database. Always generate valid MySQL syntax only.
ONLY use the tables and columns provided below. DO NOT invent any columns or tables.

Schema (use ONLY these tables and columns):
{$schema}

Assume today's date is {$today}. If the user asks about a specific month (e.g. "April") but does not mention a year, always assume they mean {$currentYear}.

Instructions:
- ✅ Always return a valid SELECT SQL query based on the user request.
- ❌ NEVER include any column that is not listed above.
- ❌ NEVER assume column names like "postcode", "phone", etc. unless they are explicitly listed.
- ❌ NEVER return queries that modify data (DROP, DELETE, INSERT, UPDATE, TRUNCATE).
- ❌ If any requested column is not found in the schema, SKIP it and ADD a note like:
  "ℹ️ Column 'postcode' not found. Using only available columns."
  Always add a note when a column is missing, even if the query is valid without it.
- Do NOT include markdown (no ```sql).
- Use lowercase column names like 'created_at'.
- Assume all questions are safe unless they explicitly ask to *change* the data.

Sensitive Data Handling:
- If the user asks to *include sensitive data*, skip any columns that may contain passwords, secrets, tokens, or API keys (like 'password', 'secret', 'api_key', 'token') in your query.

User Question: {$question}

If the user asks to include sensitive data, add a note like:
"ℹ️ Sensitive information requested. To ensure privacy and data protection, only general, non-sensitive details have been provided."

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

        $cleanText = preg_replace('/```(sql)?|```/', '', $rawText);

        $sql = trim(preg_replace('/ℹ️.*$/m', '', $cleanText)); // remove inline note
        $notes = [];

        // Extract any ℹ️ or ❌ notes
        if (preg_match_all('/(ℹ️|❌).*$/m', $cleanText, $matches)) {
            $notes = array_map('trim', $matches[0]);
        }

        // If no note found, add a fallback note
//        if (empty($notes)) {
//            $notes[] = "ℹ️ Column(s) missing from the schema.";
//        }

        return [
            'sql' => $sql,
            'notes' => $notes,
        ];
    }



}
