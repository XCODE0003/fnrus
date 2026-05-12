<x-filament-panels::page>
    <form wire:submit.prevent="savePermissions" class="space-y-6">
        {{ $this->form }}
    </form>
</x-filament-panels::page>
