<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Ask SQLGen
        </x-slot>

        <div class="space-y-4">
            {{-- Input with Enter support --}}
            <input
                type="text"
                wire:model.defer="question"
                wire:keydown.enter="ask"
                placeholder="Ask something like 'How many users signed up today?'"
                class="w-full border border-gray-300 rounded px-4 py-2 shadow-sm focus:ring focus:ring-primary-300 focus:border-primary-500 transition"
            />

            {{-- Submit button with spinner --}}
            <div class="flex items-center gap-3">
                <x-filament::button
                    wire:click="ask"
                    wire:loading.attr="disabled"
                    wire:target="ask"
                    class="px-5 py-2 bg-primary-600 text-white rounded-lg shadow hover:bg-primary-700 flex items-center gap-2"
                >
                    <span wire:loading.remove wire:target="ask">Ask AI</span>

                    {{-- Spinner visible only during "ask" --}}
                    <span wire:loading.flex wire:target="ask" class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"></path>
                        </svg>
                        Thinking...
                    </span>
                </x-filament::button>

                @if($answer)
                    <x-filament::button
                        color="gray"
                        wire:click="$set('answer', '')"
                        size="sm"
                        icon="heroicon-o-x-circle"
                    >
                        Clear
                    </x-filament::button>
                @endif
            </div>

            @if($answer)
                <div
                    class="p-4 bg-gray-50 rounded-lg border border-gray-200 shadow-sm transition-all duration-300"
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                >
                    <div class="prose max-w-full text-sm text-gray-800">
                        {!! $this->answer !!}
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
