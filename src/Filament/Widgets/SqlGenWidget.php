<?php

namespace ZeeshanTariq\FilamentSqlGen\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZeeshanTariq\FilamentSqlGen\Models\SqlGenLog;
use ZeeshanTariq\FilamentSqlGen\Services\GeminiSqlGenService;

class SqlGenWidget extends Widget
{
    protected static string $view = 'filament-sqlgen::widgets.sql-gen-widget';

    public ?string $question = '';
    public ?string $answer = '';
    public array $notes = [];

    public function ask()
    {
        $start = microtime(true);
        $service = $this->resolveSqlService();
        $response = $service->generateSql($this->question);

        $this->notes = $response['notes'] ?? [];
        $this->answer = $this->handleMultipleQueries($response['queries'] ?? [], $start, $this->notes);
    }

    protected function resolveSqlService(): GeminiSqlGenService
    {
        return match (config('filament-sqlgen.provider')) {
            'gemini' => new GeminiSqlGenService(),
            // 'openai' => new OpenAiSqlGenService(),
            default => new GeminiSqlGenService(),
        };
    }

    protected function handleMultipleQueries(array $queries, float $startTime, array $notes = []): string
    {
        $message = '';
        $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        // Show notes (if any)
        if (!empty($notes)) {
            $message .= "<div class='mt-2 text-sm text-blue-600'>" .
                implode('<br>', array_map('e', $notes)) . "</div>";
        }

        // Handle each query
        foreach ($queries as $queryData) {
            $description = e($queryData['description'] ?? '🔍 Query');
            $sqlQuery = trim($queryData['query'] ?? '');

            $message .= "<div class='mt-4'>";
            $message .= "<h3 class='font-semibold text-gray-700 mb-1'>{$description}</h3>";

            if (empty($sqlQuery)) {
                $message .= "<p class='text-sm text-gray-500'>⚠️ No SQL query was generated.</p>";
            } else {
                try {
                    // Log and dd the generated query to debug
                    Log::info("Generated SQL query: {$sqlQuery}");
                    // dd($sqlQuery); // Uncomment to dump and die for further inspection

                    // Execute the query if it's a valid SELECT query
                    $results = DB::select($sqlQuery);
                    $message .= $this->formatResults($results);
                } catch (\Exception $e) {
                    Log::error('SQL query execution failed', [
                        'sql_query' => $sqlQuery,
                        'exception' => $e->getMessage(),
                    ]);
                    $message .= "<p class='text-sm text-red-600'>⚠️ Query failed: " . e($e->getMessage()) . "</p>";
                }
            }

            $message .= "</div>";

            // Log each query separately
            $this->logSqlGenInteraction($this->question, $sqlQuery, $message, $responseTimeMs);
        }

        return $message;
    }



    protected function logSqlGenInteraction(string $question, string $sqlQuery, string $response, float $responseTimeMs): void
    {
        SqlGenLog::create([
            'question' => $question,
            'sql_query' => $sqlQuery,
            'response' => $response,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    protected function formatResults(array $results): string
    {
        if (empty($results)) {
            return "ℹ️ No data found for your request.";
        }

        $html = "<div class='overflow-x-auto'><table class='table-auto w-full text-sm text-left text-gray-800 border border-gray-300 rounded'>";
        $html .= $this->generateTableHeader($results);
        $html .= $this->generateTableBody($results);
        $html .= "</table></div>";

        return $html;
    }

    protected function generateTableHeader(array $results): string
    {
        $html = "<thead><tr class='bg-gray-100 font-semibold'>";
        foreach ((array)$results[0] as $key => $val) {
            $html .= "<th class='px-4 py-2 border'>" . e(ucwords(str_replace('_', ' ', $key))) . "</th>";
        }
        $html .= "</tr></thead>";
        return $html;
    }

    protected function generateTableBody(array $results): string
    {
        $html = "<tbody>";
        foreach ($results as $row) {
            $html .= "<tr class='hover:bg-gray-50'>";
            foreach ((array)$row as $val) {
                $html .= "<td class='px-4 py-2 border'>" . e($val) . "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody>";
        return $html;
    }
}
