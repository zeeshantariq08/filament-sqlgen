<?php

namespace ZeeshanTariq\FilamentAiAgent\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function ask(string $question, string $context = ''): string
    {
        $apiKey = config('filament-ai-agent.gemini_api_key');

        // Call to Gemini API
        $response = Http::withToken($apiKey)
            ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $context . "\n\nUser: " . $question],
                        ]
                    ]
                ]
            ]);

        // Handle the response
        return $response->json('candidates.0.content.parts.0.text') ?? 'Sorry, I could not answer that.';
    }
}
