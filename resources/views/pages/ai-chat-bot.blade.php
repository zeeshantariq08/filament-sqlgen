<x-filament::page>
    <div class="space-y-4">
        <div class="bg-gray-100 p-4 rounded h-64 overflow-y-auto">
            @foreach($this->messages as $message)
                <div class="{{ $message['type'] === 'user' ? 'text-right' : 'text-left' }}">
                    <p class="p-2 inline-block rounded {{ $message['type'] === 'user' ? 'bg-blue-200' : 'bg-green-200' }}">
                        {{ $message['text'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <form wire:submit.prevent="send" class="flex gap-2">
            <input wire:model.defer="question" class="w-full rounded border p-2" placeholder="Ask something..." />
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded">Send</button>
        </form>
    </div>
</x-filament::page>
