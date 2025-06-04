<?php

namespace ZeeshanTariq\FilamentSqlGen\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiSqlGenService implements SqlGenServiceInterface
{
    protected SchemaService $schemaService;

    public function __construct(SchemaService $schemaService)
    {
        $this->schemaService = $schemaService;
    }

    public function generateSql(string $question): array
    {
        $schemaHash = $this->schemaService->getSchemaFileHash();
        $cacheKey = 'sqlgen:' . md5($question . $schemaHash);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($question) {
            try {
                $apiKey = config('filament-sqlgen.gemini.api_key');
                $endpoint = config('filament-sqlgen.gemini.endpoint');
                $endpointWithKey = "{$endpoint}?key={$apiKey}";

                $temperature = config('filament-sqlgen.gemini.temperature', 0.2);
                $maxTokens = config('filament-sqlgen.gemini.max_output_tokens', 1024);

                $prompt = $this->buildPrompt($question);
                $tokenEstimate = $this->estimateTokenCount($prompt);

                Log::info('[GeminiSQLGen] Token Estimate', [
                    'question' => $question,
                    'estimated_tokens' => $tokenEstimate,
                ]);

                $response = Http::post($endpointWithKey, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
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
        });
    }

    protected function buildPrompt(string $question): string
    {
        $schema = $this->schemaService->getSchemaSummary();
        $today = now()->format('Y-m-d');
        $currentYear = now()->year;

        return <<<EOT
You are a strict SQL assistant for a Laravel application, dedicated to generating valid MySQL queries ONLY.

---
**Core Directives:**
* **Database Context:** Always generate valid MySQL syntax.
* **Schema Adherence:** ONLY use the tables and columns provided in the schema below. DO NOT invent any columns or tables.
* **Date Context:** Assume today's date is {$today}. If a user asks about a specific month (e.g., "April") without specifying a year, ALWAYS assume they mean {$currentYear}.
* **Query Type:** ALWAYS return a valid SELECT SQL query. NEVER generate queries that modify data (DROP, DELETE, INSERT, UPDATE, TRUNCATE).
* **Formatting:** DO NOT include markdown (e.g., no ```sql). Use lowercase column names (e.g., 'created_at').
* **Soft Deletes:** Unless the user explicitly asks for "deleted" or "trashed" data, always exclude records where a `deleted_at` column is NOT NULL. If the user does ask for deleted data, include `deleted_at` records.


---
**Schema (use ONLY these tables and columns):**
{$schema}

---
**Error and Warning Handling:**
* **Missing Columns:** If any requested column is not found in the provided schema, SKIP that column and ADD a note like:
    "ℹ️ Column 'postcode' not found.  Using available columns only."
* **Sensitive Data:** If the user asks to *include sensitive data* (e.g., 'password', 'secret', 'api_key', 'token'), EXCLUDE these columns from your query. Additionally, add this note:
    "ℹ️ Sensitive information requested. To ensure privacy and data protection, only general, non-sensitive details have been provided."

---
**User Question:** {$question}
EOT;
    }

    protected function parseResponse($response): array
    {
        $data = $response->json();
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $cleanText = preg_replace('/```(sql)?|```/', '', $rawText);

        $sql = trim(preg_replace('/ℹ️.*$/m', '', $cleanText)); // remove inline note
        $notes = [];

        if (preg_match_all('/(ℹ️|❌).*$/m', $cleanText, $matches)) {
            $notes = array_map('trim', $matches[0]);
        }

        return [
            'sql' => $sql,
            'notes' => $notes,
        ];
    }

    protected function estimateTokenCount(string $text): int
    {
        // Rough estimation: 1 token ~ 4 characters
        return (int)ceil(strlen($text) / 4);
    }
}
