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

    public function getColumnSpan(): int | string | array
    {
        return config('filament-sqlgen.widget_column_span', 2);
    }

    public function ask()
    {
        $start = microtime(true);
        $service = $this->resolveSqlService();
        $sqlQuery = $service->generateSql($this->question);
        $this->notes = $sqlQuery['notes'] ?? []; // Save notes separately
        $this->answer = $this->handleDynamicQuery($sqlQuery['sql'], $start, $sqlQuery['notes']);
    }

    protected function resolveSqlService(): GeminiSqlGenService
    {
        return match (config('filament-sqlgen.provider')) {
            'gemini' => new GeminiSqlGenService(),
            // 'openai' => new OpenAiSqlGenService(),
            default => new GeminiSqlGenService(),
        };
    }

    protected function handleDynamicQuery(string $sqlQuery, float $startTime, array $notes = []): string
    {
        $message = '';
        $cleanQuery = '';
        $responseTimeMs = null;

        if (!empty($notes)) {
            $message .= "<div class='mt-2 text-sm text-blue-600'>" .
                implode('<br>', array_map('e', $notes)) . "</div>";
        }

        if (empty($sqlQuery)) {

            $message .= "ℹ️ I couldn't process your request at the moment. Please try again.";
        } else {
            $cleanQuery = trim(preg_replace('/^sql\s*/i', '', $sqlQuery));

            if (!preg_match('/^\s*select\s/i', $cleanQuery)) {
                $message .= "⚠️ I'm only able to show information from the database, not make changes. Please try asking your question differently to view data.";
            } else {
                try {
                    $results = DB::select($cleanQuery);
                    $message .= $this->formatResults($results);
                } catch (\Exception $e) {
                    Log::error('SQL query execution failed', [
                        'sql_query' => $cleanQuery,
                        'exception' => $e->getMessage(),
                    ]);
                    $message .= "ℹ️ Something went wrong. Please try again later.";
                }
            }
        }

        $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);
        $this->logSqlGenInteraction($this->question, $cleanQuery, $message, $responseTimeMs);

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
