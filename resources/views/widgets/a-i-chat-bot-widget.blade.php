<div class="space-y-4">
    <input
        type="text"
        wire:model.defer="question"
        placeholder="Ask something..."
        class="w-full border rounded p-2"
    />

    <x-filament::button
        wire:click="ask"
        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
    >
        Ask AI
    </x-filament::button>

    @if($answer)
        <div class="p-4 bg-gray-100 rounded">
            <div class="prose max-w-full">{!! $this->answer !!}</div>
        </div>
    @endif
</div>
