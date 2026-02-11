<x-filament-panels::page>
    <div wire:poll.5s="$refresh">
        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
