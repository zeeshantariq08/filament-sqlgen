<?php

namespace ZeeshanTariq\FilamentAiAgent\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AIChatBotWidget extends Widget
{
    protected static string $view = 'filament-ai-agent::widgets.a-i-chat-bot-widget';

    public ?string $question = '';
    public ?string $answer = '';

    public function ask()
    {
        $sqlQuery = $this->askGeminiService($this->question);
        $this->answer = $this->handleDynamicQuery($sqlQuery);
    }

    protected function askGeminiService(string $question): string
    {
        $apiKey = env('GEMINI_API_KEY');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        $prompt = <<<EOT
You are an expert SQL assistant. Generate a single MySQL query only. Do not include markdown, comments, or explanation. Use lowercase Laravel-style column names like 'created_at', 'updated_at'. Example: SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE();

User Question: {$question}
EOT;

        $response = Http::post($endpoint, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]);

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Remove markdown fences and comments
        $text = preg_replace('/```sql(.*?)```/is', '$1', $text);
        $text = preg_replace('/```(.*?)```/is', '$1', $text);
        $text = preg_replace('/--.*$/m', '', $text);
        $text = preg_replace('/\/\*.*?\*\//s', '', $text);

        // Get first statement
        preg_match('/^(.*?;)/s', trim($text), $matches);
        $sqlQuery = isset($matches[1]) ? trim($matches[1]) : trim($text);

        return $sqlQuery;
    }

    protected function handleDynamicQuery(string $sqlQuery): string
    {
        if (empty($sqlQuery)) {
            return "ℹ️ I couldn't process your request at the moment. Please try again.";
        }

        if (!preg_match('/^\s*select\s/i', $sqlQuery)) {
            return "🤖 I'm currently only able to answer questions about viewing information. Try rephrasing your request.";
        }

        try {
            $results = DB::select($sqlQuery);

            if (empty($results)) {
                return "ℹ️ No data found for your request.";
            }

            // HTML table
            $html = "<div class='overflow-x-auto'><table class='table-auto w-full text-sm text-left text-gray-800 border border-gray-300 rounded'>";
            $html .= "<thead><tr class='bg-gray-100 font-semibold'>";

            foreach ((array)$results[0] as $key => $val) {
                $html .= "<th class='px-4 py-2 border'>" . e(ucwords(str_replace('_', ' ', $key))) . "</th>";
            }
            $html .= "</tr></thead><tbody>";

            foreach ($results as $row) {
                $html .= "<tr class='hover:bg-gray-50'>";
                foreach ((array)$row as $val) {
                    $html .= "<td class='px-4 py-2 border'>" . e($val) . "</td>";
                }
                $html .= "</tr>";
            }

            $html .= "</tbody></table></div>";

            return $html;
        } catch (\Exception $e) {
            return "❌ There was an issue processing your request. Please try again later.";
        }
    }
}
