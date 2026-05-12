<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Shop;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(static function (array $data): array {
                    $shop = Shop::getDefault();
                    $now = time();
                    return array_merge($data, [
                        'sid' => $shop?->id ?? 0,
                        'cid' => (int) ($data['cid'] ?? 0),
                        'image' => $data['image'] ?? '',
                        'image_site' => $data['image_site'] ?? '',
                        'image_spoiler' => (int) ($data['image_spoiler'] ?? 0),
                        'disable_web_page_preview' => (int) ($data['disable_web_page_preview'] ?? 0),
                        'display_products' => (int) ($data['display_products'] ?? 0),
                        'count_column' => (int) ($data['count_column'] ?? 1),
                        'count_views' => 0,
                        'sort' => 0,
                        'visibility' => (int) ($data['visibility'] ?? 1),
                        'seo_description' => $data['seo_description'] ?? '',
                        'seo_keywords' => $data['seo_keywords'] ?? '',
                        'description' => $data['description'] ?? '',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }),
        ];
    }
}
