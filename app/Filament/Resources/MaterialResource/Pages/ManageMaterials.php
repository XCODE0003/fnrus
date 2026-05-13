<?php

declare(strict_types=1);

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use App\Models\Material;
use App\Models\Product;
use App\Models\Shop;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManageMaterials extends ManageRecords
{
    protected static string $resource = MaterialResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data) {
                    return self::ingestMaterials($data);
                }),

            Actions\Action::make('exportKeys')
                ->label('Экспорт')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading('Экспорт ключей')
                ->modalDescription('Все ключи, попавшие под текущие фильтры (по одному в строке). Можно выделить весь текст и скопировать.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Закрыть')
                ->form(fn (): array => [
                    Forms\Components\Textarea::make('keys_export')
                        ->label('Содержимое')
                        ->default($this->buildExportText())
                        ->rows(20)
                        ->extraAttributes(['readonly' => 'readonly', 'style' => 'font-family: ui-monospace, SFMono-Regular, monospace; white-space: pre;'])
                        ->dehydrated(false),
                ]),

            Actions\Action::make('bulkManage')
                ->label('Управление списком')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->modalHeading('Управление списком ключей')
                ->modalDescription(
                    'Каждая строка — отдельный ключ. Удалённые строки удаляют ключи со склада, '
                    . 'добавленные строки создают новые. Затрагиваются только доступные ключи (status=1) '
                    . 'выбранного товара и срока. Зарезервированные / отключённые ключи не трогаем.'
                )
                ->modalSubmitActionLabel('Сохранить')
                ->modalWidth('5xl')
                ->visible(fn (): bool => $this->resolveScope() !== null)
                ->form(function (): array {
                    $scope = $this->resolveScope();
                    $text = '';
                    if ($scope !== null) {
                        $bodies = Material::query()
                            ->where('pid', $scope['pid'])
                            ->where('tid', $scope['tid'])
                            ->where('status', 1)
                            ->orderBy('id', 'asc')
                            ->pluck('body')
                            ->all();
                        $text = implode("\n", array_map(fn ($b) => (string) $b, $bodies));
                    }

                    return [
                        Forms\Components\Textarea::make('keys_manage')
                            ->label('Ключи (один в строке)')
                            ->default($text)
                            ->rows(20)
                            ->extraAttributes(['style' => 'font-family: ui-monospace, SFMono-Regular, monospace; white-space: pre;'])
                            ->dehydrated(true)
                            ->rules(['nullable', 'string']),
                    ];
                })
                ->action(function (array $data): void {
                    $scope = $this->resolveScope();
                    if ($scope === null) {
                        Notification::make()->title('Установите фильтр по товару и сроку.')->danger()->send();
                        return;
                    }
                    $this->applyBulkManage($scope, (string) ($data['keys_manage'] ?? ''));
                }),
        ];
    }

    /**
     * Resolve the {pid, tid} scope for the bulk-manage / export actions
     * from the table filters URL (?tableFilters[pid][value]=..&[tid][value]=..).
     * Returns null when either is missing — the action then hides itself.
     */
    private function resolveScope(): ?array
    {
        $filters = $this->tableFilters ?? [];
        $pid = (int) (data_get($filters, 'pid.value') ?? 0);
        $tid = (int) (data_get($filters, 'tid.value') ?? 0);

        if ($pid <= 0 || $tid <= 0) {
            return null;
        }
        return ['pid' => $pid, 'tid' => $tid];
    }

    /**
     * Build the export textarea content from the currently visible
     * filtered table query (warehouse statuses only — sold rows are
     * already excluded by the resource's modifyQueryUsing scope).
     */
    private function buildExportText(): string
    {
        $bodies = $this->getFilteredTableQuery()
            ->orderBy('id', 'asc')
            ->pluck('body')
            ->all();

        return implode("\n", array_map(fn ($b) => (string) $b, $bodies));
    }

    /**
     * Diff submitted textarea against the available pool (status=1)
     * for the chosen product+tariff and apply the changes:
     * - lines that disappeared → delete those status=1 rows
     * - lines that are new     → insert new status=1 rows
     * Reserved / disabled rows are never touched.
     */
    private function applyBulkManage(array $scope, string $rawText): void
    {
        $pid = (int) $scope['pid'];
        $tid = (int) $scope['tid'];

        $newLines = [];
        foreach (preg_split('/\r\n|\r|\n/', $rawText) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $newLines[] = $line;
            }
        }

        $existing = Material::query()
            ->where('pid', $pid)
            ->where('tid', $tid)
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get(['id', 'body']);

        $existingByBody = [];
        foreach ($existing as $row) {
            $body = (string) $row->body;
            if (! array_key_exists($body, $existingByBody)) {
                $existingByBody[$body] = [];
            }
            $existingByBody[$body][] = (int) $row->id;
        }

        $keepIds = [];
        $toInsert = [];
        foreach ($newLines as $line) {
            $encoded = htmlspecialchars($line, ENT_QUOTES);
            $candidates = $existingByBody[$encoded] ?? $existingByBody[$line] ?? null;
            if ($candidates) {
                $keepIds[] = array_shift($candidates);
                if ($candidates) {
                    if (isset($existingByBody[$encoded])) {
                        $existingByBody[$encoded] = $candidates;
                    } else {
                        $existingByBody[$line] = $candidates;
                    }
                }
                continue;
            }
            $toInsert[] = $encoded;
        }

        $deleteIds = $existing->pluck('id')->map(fn ($v) => (int) $v)
            ->reject(fn (int $id) => in_array($id, $keepIds, true))
            ->values()
            ->all();

        DB::transaction(function () use ($pid, $tid, $toInsert, $deleteIds): void {
            if ($deleteIds) {
                Material::whereIn('id', $deleteIds)->where('status', 1)->delete();
            }
            if ($toInsert) {
                $shop = Shop::getDefault();
                $sid = $shop?->id ?? 0;
                $now = time();
                $rows = [];
                foreach ($toInsert as $body) {
                    $rows[] = [
                        'sid'        => $sid,
                        'pid'        => $pid,
                        'tid'        => $tid,
                        'eid'        => 0,
                        'oid'        => 0,
                        'bid'        => 0,
                        'body'       => $body,
                        'status'     => 1,
                        'created_at' => (string) $now,
                    ];
                }
                DB::table('materials')->insert($rows);
            }

            $delta = count($toInsert) - count($deleteIds);
            if ($delta !== 0) {
                DB::table('products')
                    ->where('id', $pid)
                    ->update(['count_all' => DB::raw('GREATEST(0, count_all + (' . $delta . '))')]);
            }
        });

        Notification::make()
            ->title(sprintf('Готово: добавлено %d, удалено %d.', count($toInsert), count($deleteIds)))
            ->success()
            ->send();
    }

    /**
     * Reproduce MaterialController::add — split body by lines, optionally append
     * lines from an uploaded .txt, batch-insert one material per line and bump
     * products.count_all.
     */
    private static function ingestMaterials(array $data): Model
    {
        $shop = Shop::getDefault();
        if (! $shop) {
            Notification::make()->title('Магазин не настроен.')->danger()->send();
            throw new \RuntimeException('Shop not configured');
        }

        $pid = (int) ($data['pid'] ?? 0);
        $tid = (int) ($data['tid'] ?? 0);

        $product = Product::where('sid', $shop->id)->where('id', $pid)->first();
        if (! $product) {
            Notification::make()->title('Товар не найден.')->danger()->send();
            throw new \RuntimeException('Product not found');
        }

        $lines = [];

        $body = trim((string) ($data['body'] ?? ''));
        if ($body !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        $uploaded = $data['file_upload'] ?? null;
        $uploadedPath = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
        if (is_string($uploadedPath) && $uploadedPath !== '' && Storage::disk('local')->exists($uploadedPath)) {
            $contents = (string) Storage::disk('local')->get($uploadedPath);
            foreach (preg_split('/\r\n|\r|\n/', $contents) ?: [] as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
            Storage::disk('local')->delete($uploadedPath);
        }

        if ($lines === []) {
            Notification::make()->title('Нечего сохранять: материал пуст.')->danger()->send();
            throw new \RuntimeException('No material lines');
        }

        $now = time();
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = [
                'sid' => $shop->id,
                'pid' => $pid,
                'tid' => $tid,
                'eid' => 0,
                'oid' => 0,
                'bid' => 0,
                'body' => htmlspecialchars($line, ENT_QUOTES),
                'status' => 1,
                'created_at' => (string) $now,
            ];
        }

        DB::table('materials')->insert($rows);

        DB::table('products')
            ->where('sid', $shop->id)
            ->where('id', $pid)
            ->increment('count_all', count($rows));

        Notification::make()
            ->title('Добавлено материалов: ' . count($rows))
            ->success()
            ->send();

        // Filament нужна модель, чтобы корректно завершить экшен.
        return Material::query()->where('pid', $pid)->latest('id')->first()
            ?? new Material();
    }
}
