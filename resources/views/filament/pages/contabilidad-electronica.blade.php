<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>
    </div>
</x-filament-panels::page>
