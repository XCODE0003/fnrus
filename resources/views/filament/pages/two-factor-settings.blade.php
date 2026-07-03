<x-filament-panels::page>
    @php
        $setupHeading = $enabled ? 'Переподключение приложения' : 'Подключение';
        $enableBtnLabel = $enabled ? 'Подтвердить переподключение' : 'Включить 2FA';
    @endphp

    <div class="max-w-xl mx-auto w-full space-y-6">

        {{-- Резервные коды — показываются один раз --}}
        @if ($recoveryCodes)
            <x-filament::section>
                <x-slot name="heading">Сохраните резервные коды</x-slot>
                <x-slot name="description">
                    Каждый код одноразовый — пригодится, если потеряете доступ к приложению-аутентификатору.
                    После ухода со страницы вы их больше не увидите.
                </x-slot>
                <pre class="rounded bg-gray-50 dark:bg-gray-900 p-3 font-mono text-sm overflow-x-auto whitespace-pre-wrap">{{ $this->recoveryCodesText() }}</pre>
            </x-filament::section>
        @endif

        {{-- Статус --}}
        <x-filament::section>
            <x-slot name="heading">Статус</x-slot>
            @if ($enabled && ! $configuring)
                <div class="text-sm font-medium text-success-600 dark:text-success-400">
                    ✓ Двухфакторная защита включена
                </div>
            @elseif (! $enabled)
                <div class="text-sm text-warning-600 dark:text-warning-400">
                    2FA не подключена.
                    @if ($enforced)
                        Она обязательна — подключите ниже.
                    @endif
                </div>
            @endif
        </x-filament::section>

        {{-- Подключение / переподключение (QR) --}}
        @if ($configuring)
            <x-filament::section>
                <x-slot name="heading">{{ $setupHeading }}</x-slot>
                <x-slot name="description">
                    Отсканируйте QR-код в Google Authenticator / Authy / 1Password / Yandex Key
                    (или введите ключ вручную), затем введите шестизначный код.
                </x-slot>

                <div class="flex flex-col md:flex-row gap-6 items-start">
                    <div class="shrink-0 p-2 bg-white rounded border border-gray-200 dark:border-gray-700 [&_svg]:block [&_svg]:h-48 [&_svg]:w-48">
                        {!! $qrSvg !!}
                    </div>
                    <div class="flex-1 min-w-0 space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500">Ключ (ручной ввод):</div>
                            <code class="font-mono text-base break-all">{{ $secret }}</code>
                        </div>
                        <div>
                            <div class="text-gray-500">Тип:</div>
                            <span>TOTP, SHA1, 6 цифр, 30 сек</span>
                        </div>
                    </div>
                </div>

                <form wire:submit="enable" class="space-y-4 mt-6">
                    {{ $this->form }}
                    <div class="flex justify-end gap-2">
                        @if ($enabled)
                            <x-filament::button type="button" color="gray" wire:click="cancelReconfigure">
                                Отмена
                            </x-filament::button>
                        @endif
                        <x-filament::button type="submit" icon="heroicon-o-shield-check">
                            {{ $enableBtnLabel }}
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

        {{-- Управление (2FA включена) --}}
        @elseif ($enabled)
            <x-filament::section>
                <x-slot name="heading">Управление</x-slot>
                <x-slot name="description">
                    Чтобы перевыпустить резервные коды или (если 2FA не обязательна) отключить её,
                    введите текущий код из приложения либо резервный код.
                </x-slot>

                {{ $this->form }}

                <div class="flex flex-wrap gap-2 mt-4">
                    <x-filament::button type="button" color="gray" icon="heroicon-o-qr-code" wire:click="startReconfigure">
                        Переподключить (новый QR)
                    </x-filament::button>
                    <x-filament::button type="button" color="warning" icon="heroicon-o-arrow-path" wire:click="regenerateRecoveryCodes">
                        Перевыпустить резервные коды
                    </x-filament::button>
                    @if (! $enforced)
                        <x-filament::button type="button" color="danger" icon="heroicon-o-shield-exclamation" wire:click="disable">
                            Отключить 2FA
                        </x-filament::button>
                    @endif
                </div>

                @if ($enforced)
                    <p class="text-sm text-gray-500 mt-3">
                        2FA обязательна политикой безопасности, поэтому отключить её нельзя — доступно только
                        переподключение приложения и перевыпуск резервных кодов.
                    </p>
                @endif
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
