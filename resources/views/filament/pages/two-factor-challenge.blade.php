<x-filament-panels::page>
    <div class="max-w-md mx-auto w-full">
        <x-filament::section>
            <x-slot name="heading">Введите код из приложения</x-slot>
            <x-slot name="description">
                Шестизначный код из приложения-аутентификатора (Google Authenticator, Authy и т.п.).
                Сессия после проверки активна 12 часов.
            </x-slot>

            <form wire:submit="submit" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-lock-closed">
                        Подтвердить
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
