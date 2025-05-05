<?php

namespace ZeeshanTariq\FilamentSqlGen\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZeeshanTariq\FilamentSqlGen\Services\GeminiSqlGenService;

class SqlGenWidget extends Widget
{
    protected static string $view = 'filament-sqlgen::widgets.sql-gen-widget';

    public ?string $question = '';
    public ?string $answer = '';

    public function ask()
    {
        $service = $this->resolveSqlService();
        $sqlQuery = $service->generateSql($this->question);
        $this->answer = $this->handleDynamicQuery($sqlQuery);
    }

    protected function resolveSqlService(): GeminiSqlGenService
    {
        return match (config('filament-sqlgen.provider')) {
            'gemini' => new GeminiSqlGenService(),
            // 'openai' => new OpenAiSqlGenService(),
            default => new GeminiSqlGenService(),
        };
    }

    protected function handleDynamicQuery(string $sqlQuery): string
    {

        if (empty($sqlQuery)) {
            return "ℹ️ I couldn't process your request at the moment. Please try again.";
        }

        // Clean unwanted prefixes like 'sql' and trim whitespace
        $cleanQuery = trim(preg_replace('/^sql\s*/i', '', $sqlQuery));

        // Ensure it starts with a SELECT statement
        if (!preg_match('/^\s*select\s/i', $cleanQuery)) {
            return "🤖 I'm only allowed to run SELECT queries for safety. Please rephrase your question to ask for information.";
        }

        try {
            $results = DB::select($cleanQuery);

            return $this->formatResults($results);
        } catch (\Exception $e) {
            Log::error('SQL query execution failed', [
                'sql_query' => $cleanQuery,
                'exception' => $e->getMessage(),
            ]);
            return "❌ There was an issue processing your request. Please try again later.";
        }
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
