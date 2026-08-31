<x-filament-panels::page>
    <div class="space-y-4">

        @if (! $ipStorageAvailable || ! $attemptStorageAvailable)
            <div class="rounded-xl border border-warning-500/40 bg-warning-50 px-4 py-3 text-sm text-warning-800 dark:bg-warning-950/40 dark:text-warning-200">
                <div class="font-semibold">Хранилище контроля доступа не подготовлено</div>
                <div class="mt-1">
                    Страница больше не прерывается ошибкой 500. Чтобы включить недоступные функции,
                    выполните на сервере <code class="rounded bg-black/5 px-1 py-0.5 dark:bg-white/10">php artisan migrate --force</code>.
                </div>
            </div>
        @endif

        {{-- IP allow-list --}}
        <x-filament::section>
            <x-slot name="heading">IP allow-list</x-slot>
            <x-slot name="description">
                Статический список из <code>.env</code> (<code>ADMIN_ALLOWED_IPS</code>) показан ниже и редактируется через файл.
                Динамические записи можно добавлять прямо отсюда, при необходимости — с TTL.
            </x-slot>

            <div class="mb-4 text-sm">
                <span class="text-gray-500">Из .env:</span>
                @if (count($staticEnv))
                    <code class="ml-2">{{ implode(', ', $staticEnv) }}</code>
                @else
                    <span class="ml-2 text-gray-400">— (не задан)</span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-3">CIDR</th>
                            <th class="py-2 pr-3">Заметка</th>
                            <th class="py-2 pr-3">Истекает</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dynamicIps as $row)
                            @php $expired = $row['expires_at'] !== null && $row['expires_at'] < $now; @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-800 {{ $expired ? 'text-gray-400' : '' }}">
                                <td class="py-2 pr-3"><code>{{ $row['cidr'] }}</code></td>
                                <td class="py-2 pr-3">{{ $row['note'] }}</td>
                                <td class="py-2 pr-3">
                                    @if ($row['expires_at'] === null)
                                        бессрочно
                                    @else
                                        {{ date('d.m.Y H:i', $row['expires_at']) }}
                                        @if ($expired)
                                            <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-gray-200 dark:bg-gray-700">истёк</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-2 text-right">
                                    <button
                                        type="button"
                                        wire:click="deleteIp({{ $row['id'] }})"
                                        wire:confirm="Удалить запись {{ $row['cidr'] }}?"
                                        class="text-danger-600 hover:text-danger-700 px-2 py-1 rounded hover:bg-danger-50 dark:hover:bg-danger-950"
                                        title="Удалить"
                                    >
                                        ×
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-center text-gray-400">Динамических записей нет</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Последние попытки входа --}}
        <x-filament::section>
            <x-slot name="heading">Последние попытки входа</x-slot>
            <x-slot name="description">До 50 последних записей из <code>login_attempts</code>.</x-slot>

            <div class="overflow-x-auto max-h-[480px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white dark:bg-gray-900">
                        <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-3">Время</th>
                            <th class="py-2 pr-3">IP</th>
                            <th class="py-2 pr-3">Username</th>
                            <th class="py-2">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attempts as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-1.5 pr-3 whitespace-nowrap">{{ $row['created_at'] }}</td>
                                <td class="py-1.5 pr-3"><code>{{ $row['ip'] }}</code></td>
                                <td class="py-1.5 pr-3">{{ $row['username'] }}</td>
                                <td class="py-1.5">
                                    @if ($row['successful'])
                                        <span class="px-1.5 py-0.5 text-xs rounded bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300">OK</span>
                                    @else
                                        <span class="px-1.5 py-0.5 text-xs rounded bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300">{{ $row['reason'] ?: 'fail' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-center text-gray-400">Нет записей</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
