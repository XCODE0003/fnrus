<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Shop;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    /**
     * ТЗ §3.1 — games and their platform categories used to share one flat
     * list, which produced a wall of identical "Android"/"iOS" rows with no
     * way to tell them apart. Split the same table into tabs instead: the
     * table, its reordering, visibility and CRUD all stay exactly as they were.
     */
    public function getTabs(): array
    {
        return [
            'games' => Tab::make('Игры')
                ->icon('heroicon-o-puzzle-piece')
                ->badge(fn () => Category::where('cid', 0)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cid', 0)),

            'categories' => Tab::make('Категории игр')
                ->icon('heroicon-o-rectangle-stack')
                ->badge(fn () => Category::where('cid', '!=', 0)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cid', '!=', 0)),

            'orphans' => Tab::make('Без игры')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(fn () => self::orphanQuery()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => self::orphanQuery($query)),

            'all' => Tab::make('Все')
                ->icon('heroicon-o-bars-3'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'games';
    }

    /**
     * Categories whose parent game no longer exists — the rows the storefront
     * can never render. Surfaced so they can be found and fixed.
     */
    protected static function orphanQuery(?Builder $query = null): Builder
    {
        $query ??= Category::query();

        return $query->where('cid', '!=', 0)
            ->whereNotIn('cid', Category::where('cid', 0)->select('id'));
    }

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
