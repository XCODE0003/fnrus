<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Tariff;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected static ?string $title = 'Новый товар';

    /** @var array<int, array{days: mixed, price: mixed}> */
    protected array $pendingTariffs = [];

    /** @var array<int, array{title: string, lines: string}> */
    protected array $pendingFunctional = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingTariffs = (array) ($data['tariffs_data'] ?? []);
        $this->pendingFunctional = (array) ($data['functional_data'] ?? []);
        unset($data['tariffs_data'], $data['functional_data']);

        $shop = Shop::getDefault();
        $now = time();

        // functional → JSON в формате [{id, title, lines[]}, ...]
        $idValues = ['visuals', 'aimbot', 'misc'];
        $functional = [];
        foreach (array_values($this->pendingFunctional) as $index => $block) {
            $title = trim((string) ($block['title'] ?? ''));
            $linesRaw = (string) ($block['lines'] ?? '');
            $lines = array_values(array_filter(
                array_map('trim', explode("\n", $linesRaw)),
                static fn ($l) => $l !== ''
            ));
            if ($title === '' && $lines === []) {
                continue;
            }
            $functional[] = [
                'id' => $idValues[$index % count($idValues)],
                'title' => $title,
                'lines' => $lines,
            ];
        }

        $defaults = [
            'sid' => $shop?->id ?? 0,
            'seo_description' => $data['seo_description'] ?? '',
            'seo_keywords' => $data['seo_keywords'] ?? '',
            'description' => $data['description'] ?? '',
            'image' => $data['image'] ?? '',
            'image_site' => $data['image_site'] ?? '',
            'image_spoiler' => (int) ($data['image_spoiler'] ?? 0),
            'disable_web_page_preview' => (int) ($data['disable_web_page_preview'] ?? 0),
            'gallery' => $data['gallery'] ?? json_encode([]),
            'functional' => json_encode($functional, JSON_UNESCAPED_UNICODE),
            'link_video' => $data['link_video'] ?? '',
            'system_platforms' => '[]',
            'price' => 0,
            'price_type' => 0,
            'currency' => 0,
            'status' => 0,
            'count_views' => 0,
            'count_min' => 1,
            'count_max' => (int) ($data['count_max'] ?? 0),
            'count_sales' => 0,
            'count_all' => 0,
            'count_type' => 0,
            'is_endless' => 0,
            'is_root' => 0,
            'is_jailbreak' => 0,
            'sort' => 0,
            'updated_at' => $now,
            'created_at' => $now,
        ];

        return array_merge($data, $defaults);
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Дубль alias — отдельная проверка, как в старой админке.
        if (Product::where('alias', $data['alias'] ?? '')->exists()) {
            Notification::make()
                ->title('Такой короткий адрес уже существует.')
                ->danger()
                ->send();
            $this->halt();
        }

        return parent::handleRecordCreation($data);
    }

    protected function afterCreate(): void
    {
        /** @var Product $product */
        $product = $this->record;
        $shopId = (int) ($product->sid ?? 0);

        foreach (array_values($this->pendingTariffs) as $index => $tariff) {
            $days = (string) ($tariff['days'] ?? '');
            $price = (string) ($tariff['price'] ?? '');
            if ($days === '' || $price === '') {
                continue;
            }
            Tariff::insert([
                'sid' => $shopId,
                'pid' => $product->id,
                'title' => $days,
                'price' => $price,
                'sort' => $index,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
