<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Save section settings
        </x-filament::button>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
