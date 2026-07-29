<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Forms\Components\AttachmentImageUpload;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use App\Models\StatusCheat;
use App\Models\Tariff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Товары / Софт';
    protected static ?string $modelLabel = 'товар';
    protected static ?string $pluralModelLabel = 'товары';
    protected static ?int $navigationSort = 10;

    public const VISIBILITY_OPTIONS = [
        1 => 'Общедоступно',
        2 => 'Только на сайте',
        3 => 'Только в боте',
        0 => 'Скрыто',
    ];

    public const HACK_STATUS_OPTIONS = [
        0 => 'Не требуется',
        1 => 'Jailbreak',
        2 => 'Без Jailbreak',
        3 => 'Root',
        4 => 'Без Root',
        5 => 'С обходом',
        6 => 'Без обхода',
    ];

    /**
     * ТЗ §3.2 — category options grouped by their game, e.g.
     *   Pubg Mobile ▸ [Android, iOS], Rust ▸ [ПК]
     * so «Android» is never ambiguous. Filament renders the nested array as
     * <optgroup>. Categories whose game is missing land under «Без игры».
     */
    public static function categoryOptionsByGame(): array
    {
        $rows = Category::orderBy('sort')->get(['id', 'cid', 'title']);
        $games = $rows->where('cid', 0)->pluck('title', 'id')->all();

        $grouped = [];
        foreach ($rows as $row) {
            if ((int) $row->cid === 0) {
                continue; // a game itself is not a product category
            }
            $game = $games[(int) $row->cid] ?? 'Без игры';
            $grouped[$game][$row->id] = $row->title;
        }
        ksort($grouped);

        return $grouped;
    }

    /** id => "Игра · Категория", for table columns. */
    public static function categoryPathLabels(): array
    {
        $rows = Category::get(['id', 'cid', 'title']);
        $games = $rows->where('cid', 0)->pluck('title', 'id')->all();

        $labels = [];
        foreach ($rows as $row) {
            $labels[(int) $row->id] = (int) $row->cid === 0
                ? $row->title
                : (($games[(int) $row->cid] ?? 'Без игры') . ' · ' . $row->title);
        }

        return $labels;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()->columns(2)->schema([

                // ─── Левая колонка ───────────────────────────────────────────
                Forms\Components\Group::make()->schema([

                    AttachmentImageUpload::make('image_site')
                        ->label('Обложка для сайта')
                        ->required(),

                    AttachmentImageUpload::make('image')
                        ->label('Обложка для бота'),

                    AttachmentImageUpload::makeMultiple('gallery')
                        ->label('Галерея изображений'),

                    Forms\Components\TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->minLength(2)
                        ->maxLength(100)
                        ->placeholder('Напишите название'),

                    Forms\Components\TextInput::make('seo_description')
                        ->label('Краткое описание (для SEO)')
                        ->maxLength(255)
                        ->placeholder('Напишите краткое описание'),

                    Forms\Components\TextInput::make('seo_keywords')
                        ->label('Ключевые слова (для SEO)')
                        ->maxLength(255)
                        ->placeholder('Напишите ключевые слова'),

                    // ТЗ §3.2 — the picker used to be a flat list of bare titles,
                    // so it showed a dozen identical «Android» / «iOS» entries.
                    // Group them under their game instead. Only the presentation
                    // changes: the saved value is still the category id.
                    Forms\Components\Select::make('cid')
                        ->label('Категория')
                        ->options(fn () => self::categoryOptionsByGame())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required(),

                    Forms\Components\RichEditor::make('description')
                        ->label('Описание')
                        ->toolbarButtons([
                            'attachFiles', 'blockquote', 'bold', 'bulletList',
                            'codeBlock', 'h2', 'h3', 'italic', 'link',
                            'orderedList', 'strike', 'underline', 'redo', 'undo',
                        ])
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('covers')
                        ->fileAttachmentsVisibility('public')
                        ->maxLength(8000)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('advantages')
                        ->label('Преимущества')
                        ->rows(4)
                        ->required()
                        ->placeholder('Каждое преимущество с новой строки')
                        ->afterStateHydrated(function (Forms\Components\Textarea $component, $state): void {
                            if (is_string($state) && $state !== '') {
                                $decoded = json_decode($state, true);
                                if (is_array($decoded)) {
                                    $component->state(implode("\n", $decoded));
                                }
                            }
                        })
                        ->dehydrateStateUsing(function ($state): string {
                            $lines = array_values(array_filter(
                                array_map('trim', explode("\n", (string) $state)),
                                static fn ($line) => $line !== ''
                            ));
                            return json_encode($lines, JSON_UNESCAPED_UNICODE);
                        }),
                ]),

                // ─── Правая колонка ──────────────────────────────────────────
                Forms\Components\Group::make()->schema([

                    Forms\Components\Repeater::make('functional_data')
                        ->label('Функционал')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок блока')
                                ->required(),
                            Forms\Components\Textarea::make('lines')
                                ->label('Пункты (каждый с новой строки)')
                                ->rows(4),
                        ])
                        ->addActionLabel('Добавить блок')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Repeater $component, ?Product $record): void {
                            if (! $record || ! $record->functional) {
                                return;
                            }
                            $decoded = json_decode((string) $record->functional, true);
                            $state = [];
                            foreach ((is_array($decoded) ? $decoded : []) as $block) {
                                // Guard against malformed data (null/string blocks) — a
                                // null block here threw "array offset on null" (500 on open).
                                if (! is_array($block)) {
                                    continue;
                                }
                                $lines = $block['lines'] ?? '';
                                $state[] = [
                                    'title' => is_scalar($block['title'] ?? null) ? (string) $block['title'] : '',
                                    'lines' => is_array($lines) ? implode("\n", $lines) : (is_scalar($lines) ? (string) $lines : ''),
                                ];
                            }
                            $component->state($state);
                        }),

                    Forms\Components\Repeater::make('tariffs_data')
                        ->label('Тарифы')
                        ->schema([
                            Forms\Components\TextInput::make('days')
                                ->label('Кол-во дней')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('price')
                                ->label('Цена')
                                ->numeric()
                                ->required()
                                ->rule('regex:/^\d+(\.\d{1,2})?$/'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Добавить тариф')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Repeater $component, ?Product $record): void {
                            if (! $record) {
                                return;
                            }
                            $tariffs = Tariff::where('pid', $record->id)->orderBy('sort')->get();
                            $state = [];
                            foreach ($tariffs as $t) {
                                $state[] = ['days' => $t->title, 'price' => $t->price];
                            }
                            $component->state($state);
                        }),

                    Forms\Components\TextInput::make('system_versions')
                        ->label('Поддерживаемые языки')
                        ->required()
                        ->minLength(4)
                        ->maxLength(100)
                        ->placeholder('Напишите языки через запятую'),

                    Forms\Components\TextInput::make('system_auth')
                        ->label('Авторизация в софте')
                        ->required()
                        ->minLength(4)
                        ->maxLength(100)
                        ->placeholder('Через что происходит авторизация'),

                    Forms\Components\Select::make('status_id')
                        ->label('Статус софта')
                        ->options(fn () => StatusCheat::query()->pluck('title', 'id')->all())
                        ->searchable()
                        ->required(),

                    Forms\Components\TextInput::make('link_video')
                        ->label('Ссылка на видеообзор')
                        ->maxLength(100)
                        ->placeholder('Вставьте ссылку'),

                    Forms\Components\TextInput::make('alias')
                        ->label('Короткий адрес')
                        ->required()
                        ->minLength(2)
                        ->maxLength(100)
                        ->placeholder('Напишите короткий адрес'),

                    Forms\Components\Select::make('visibility')
                        ->label('Видимость')
                        ->options(self::VISIBILITY_OPTIONS)
                        ->default(1)
                        ->required(),

                    Forms\Components\Select::make('hack_status')
                        ->label('Взлом')
                        ->options(self::HACK_STATUS_OPTIONS)
                        ->default(0)
                        ->required(),

                    Forms\Components\Toggle::make('disable_web_page_preview')
                        ->label('Отключить предпросмотр ссылок'),

                    Forms\Components\Toggle::make('image_spoiler')
                        ->label('Включить спойлер на изображение'),

                    Forms\Components\Toggle::make('count_max')
                        ->label('Разрешить покупку только один раз')
                        ->afterStateHydrated(function (Forms\Components\Toggle $component, $state): void {
                            $component->state((int) $state === 1);
                        })
                        ->dehydrateStateUsing(fn ($state) => $state ? 1 : 0),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable()->sortable()->wrap(),
                Tables\Columns\TextColumn::make('cid')
                    ->label('Категория')
                    ->formatStateUsing(fn ($state) => self::categoryPathLabels()[(int) $state] ?? '#' . $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('count_sales')->label('Продаж')->sortable(),
                Tables\Columns\TextColumn::make('materials_available')
                    ->label('Материалов')
                    ->state(fn (Product $record): string => self::materialsBreakdownLabel($record->id))
                    ->badge()
                    ->color(fn ($state, Product $record) => match (true) {
                        \App\Models\Material::where('pid', $record->id)->where('status', 1)->count() <= 0 => 'danger',
                        \App\Models\Material::where('pid', $record->id)->where('status', 1)->count() < 5  => 'warning',
                        default => 'success',
                    })
                    ->tooltip(fn (Product $record): string => self::materialsBreakdownTooltip($record->id)),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('Видим.')
                    ->formatStateUsing(fn ($state) => self::VISIBILITY_OPTIONS[$state] ?? $state)
                    ->colors(['success' => 1, 'info' => 2, 'warning' => 3, 'danger' => 0]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')->options(self::VISIBILITY_OPTIONS),
                Tables\Filters\SelectFilter::make('cid')
                    ->label('Категория')
                    ->options(fn () => self::categoryOptionsByGame())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('tariffs')
                    ->label('Сроки и ключи')
                    ->icon('heroicon-o-archive-box')
                    ->color('primary')
                    ->url(fn (Product $record): string => static::getUrl('tariffs', ['record' => $record->id])),
                Tables\Actions\Action::make('bulkCopy')
                    ->label('Экспорт')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->modalHeading(fn (Product $record): string => 'Материалы: ' . $record->title)
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть')
                    ->fillForm(fn (): array => ['tariff_filter' => null, 'status_filter' => 1])
                    ->form(fn (Product $record): array => self::materialsModalForm($record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc');
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    private static function materialsModalForm(Product $record): array
    {
        return [
            Forms\Components\Grid::make()
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('tariff_filter')
                        ->label('Срок (тариф)')
                        ->options(function () use ($record): array {
                            $opts = ['' => 'Все сроки'];
                            $tariffs = Tariff::where('pid', $record->id)
                                ->orderByRaw('CAST(title AS UNSIGNED) ASC')
                                ->get(['id', 'title']);
                            foreach ($tariffs as $t) {
                                $opts[(string) $t->id] = Tariff::num_decline((int) $t->title, ['день', 'дня', 'дней']);
                            }
                            return $opts;
                        })
                        ->placeholder('Все сроки')
                        ->live(),
                    Forms\Components\Select::make('status_filter')
                        ->label('Статус')
                        ->options([
                            '' => 'Все статусы',
                            '1' => 'Доступен',
                            '2' => 'Продан',
                            '3' => 'Отключён',
                            '4' => 'Зарезервирован',
                        ])
                        ->default('1')
                        ->live(),
                ]),

            Forms\Components\Placeholder::make('materials_list')
                ->label('')
                ->columnSpanFull()
                ->content(function (Forms\Get $get) use ($record): \Illuminate\Support\HtmlString {
                    $tariff = $get('tariff_filter');
                    $status = $get('status_filter');
                    return new \Illuminate\Support\HtmlString(self::renderMaterialsExport(
                        $record->id,
                        ($tariff !== null && $tariff !== '' && $tariff !== '0') ? (int) $tariff : null,
                        ($status !== null && $status !== '' && $status !== '0') ? (int) $status : null,
                    ));
                }),
        ];
    }

    private static function renderMaterialsExport(int $pid, ?int $tid, ?int $status): string
    {
        $query = \App\Models\Material::query()
            ->where('pid', $pid)
            ->orderByDesc('id');

        if ($tid !== null) {
            $query->where('tid', $tid);
        }
        if ($status !== null) {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        // Whole result set — admin needs to copy all rows, not a paginated slice.
        $bodies = $query->pluck('body')->all();

        if ($total === 0) {
            return '<div style="padding: 1rem; text-align: center; color: #6b7280;">Материалов не найдено</div>';
        }

        // One material per line. Bodies are typically single-line license keys,
        // but trim trailing whitespace just in case.
        $text = implode("\n", array_map(static fn ($b) => rtrim((string) $b, "\r\n"), $bodies));
        $encoded = e($text);

        $textareaId = 'fnr-materials-export-' . $pid;

        $copyJs = "var ta=document.getElementById('{$textareaId}');"
            . "ta.focus();ta.select();ta.setSelectionRange(0, ta.value.length);"
            . "try{navigator.clipboard.writeText(ta.value).then(function(){"
            . "var btn=event.currentTarget; var orig=btn.innerText;"
            . "btn.innerText='✓ Скопировано'; setTimeout(function(){btn.innerText=orig;}, 1500);"
            . "});}catch(e){document.execCommand('copy');}";

        $selectJs = "this.focus();this.select();this.setSelectionRange(0, this.value.length);";

        $countLine = '<div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0;">'
            . '<div style="color: #374151; font-size: 0.875rem;">Всего материалов: <strong>' . $total . '</strong> (по одному в строке)</div>'
            . '<button type="button" onclick="' . htmlspecialchars($copyJs, ENT_QUOTES) . '" '
            . 'style="background: rgb(217 119 6); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 500;">'
            . '📋 Скопировать всё</button>'
            . '</div>';

        $textarea = '<textarea id="' . $textareaId . '" readonly onclick="' . htmlspecialchars($selectJs, ENT_QUOTES) . '" '
            . 'style="width: 100%; min-height: 400px; padding: 12px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8125rem; line-height: 1.5; border: 1px solid #d1d5db; border-radius: 0.5rem; resize: vertical; white-space: pre; overflow: auto; background: #f9fafb; color: #111827;">'
            . $encoded
            . '</textarea>';

        $hint = '<div style="padding: 0.5rem 0 0; color: #6b7280; font-size: 0.75rem;">'
            . 'Клик по полю — выделит всё. Кнопка «Скопировать всё» — копирует в буфер обмена.'
            . '</div>';

        return $countLine . $textarea . $hint;
    }

    /** "5 / 12 / 30 / 61" — counts of available materials per tariff (sorted by days). */
    private static function materialsBreakdownLabel(int $pid): string
    {
        $rows = self::materialsBreakdownData($pid);
        if ($rows === []) {
            return '0';
        }
        $parts = array_map(static fn (array $r): string => (string) $r['count'], $rows);
        return implode(' / ', $parts);
    }

    private static function materialsBreakdownTooltip(int $pid): string
    {
        $rows = self::materialsBreakdownData($pid);
        if ($rows === []) {
            return 'Нет доступных материалов (status=1)';
        }
        $lines = ['Доступно к продаже (status=1):'];
        foreach ($rows as $r) {
            $lines[] = '• ' . Tariff::num_decline((int) $r['title'], ['день', 'дня', 'дней']) . ' — ' . $r['count'];
        }
        return implode("\n", $lines);
    }

    /**
     * Per-tariff breakdown of status=1 materials for this product, sorted
     * by the tariff's day count (1 → 7 → 30 → 60 …).
     *
     * @return array<int, array{title: int, count: int}>
     */
    private static function materialsBreakdownData(int $pid): array
    {
        $rows = \App\Models\Material::query()
            ->where('pid', $pid)
            ->where('status', 1)
            ->selectRaw('tid, COUNT(*) as cnt')
            ->groupBy('tid')
            ->pluck('cnt', 'tid')
            ->all();

        if ($rows === []) {
            return [];
        }

        $tariffs = Tariff::whereIn('id', array_keys($rows))->get(['id', 'title'])->keyBy('id');

        $out = [];
        foreach ($rows as $tid => $count) {
            $title = (int) ($tariffs[$tid]->title ?? 0);
            $out[] = ['title' => $title, 'count' => (int) $count];
        }
        usort($out, static fn ($a, $b) => $a['title'] <=> $b['title']);
        return $out;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'tariffs' => Pages\ProductTariffs::route('/{record}/tariffs'),
        ];
    }
}
