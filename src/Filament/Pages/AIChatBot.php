<?php

namespace ZeeshanTariq\FilamentAiAgent\Filament\Pages;

use Filament\Pages\Page;
use Livewire\Component;
use ZeeshanTariq\FilamentAiAgent\Services\GeminiService;

class AIChatBot extends Page
{
    protected static string $view = 'filament-ai-agent::pages.ai-chat-bot';

    public string $question = '';
    public array $messages = [];

    public function send()
    {
        // Add user message to conversation
        $this->messages[] = ['type' => 'user', 'text' => $this->question];

        // Get answer from Gemini service
        $answer = app(GeminiService::class)->ask($this->question);
        $this->messages[] = ['type' => 'bot', 'text' => $answer];

        // Clear input
        $this->question = '';
    }
}
