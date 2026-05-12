<?php

declare(strict_types=1);

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use App\Models\Material;
use App\Models\Product;
use App\Models\Shop;
use Filament\Actions;
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
        ];
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
