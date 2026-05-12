<x-filament-panels::page>
    <div class="max-w-xl mx-auto w-full space-y-6">
        @if ($recoveryCodes)
            <x-filament::section>
                <x-slot name="heading">Сохраните резервные коды</x-slot>
                <x-slot name="description">
                    Каждый код одноразовый. Используйте, если потеряли доступ к приложению-аутентификатору.
                    Сохраните их в надёжном месте — после закрытия страницы вы их больше не увидите.
                </x-slot>

                <pre class="rounded bg-gray-50 dark:bg-gray-900 p-3 font-mono text-sm overflow-x-auto whitespace-pre-wrap">{{ $this->recoveryCodesText() }}</pre>

                <div class="flex justify-end mt-4">
                    <x-filament::button wire:click="done" icon="heroicon-o-check">
                        Я сохранил коды, в админку
                    </x-filament::button>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">Добавьте аккаунт в приложение</x-slot>
                <x-slot name="description">
                    Откройте Google Authenticator / Authy / 1Password / Yandex Key и отсканируйте QR-код
                    или введите ключ вручную. Затем введите шестизначный код ниже.
                </x-slot>

                <div class="flex flex-col md:flex-row gap-6 items-start">
                    <div class="shrink-0 p-2 bg-white rounded border border-gray-200 dark:border-gray-700 [&_svg]:block [&_svg]:h-48 [&_svg]:w-48">
                        {!! $qrSvg !!}
                    </div>
                    <div class="flex-1 min-w-0 space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500">Ключ (для ручного ввода):</div>
                            <code class="font-mono text-base break-all">{{ $secret }}</code>
                        </div>
                        <div>
                            <div class="text-gray-500">Тип:</div>
                            <span>TOTP, SHA1, 6 цифр, 30 сек</span>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Подтвердите код</x-slot>

                <form wire:submit="submit" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-shield-check">
                            Включить 2FA
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
