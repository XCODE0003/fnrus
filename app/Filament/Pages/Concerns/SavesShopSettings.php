<?php

declare(strict_types=1);

namespace App\Filament\Pages\Concerns;

use App\Models\ShopSettings;
use Filament\Notifications\Notification;

/**
 * Общая инфраструктура для всех страниц настроек, которые живут в строке
 * shops_settings. Дочерний класс задаёт schema() и settingsFields() — массив
 * имён колонок ShopSettings, которыми управляет страница.
 */
trait SavesShopSettings
{
    public ?array $data = [];

    /** @return array<int, string> */
    abstract protected function settingsFields(): array;

    public function mount(): void
    {
        $s = ShopSettings::getDefault();
        $state = [];
        foreach ($this->settingsFields() as $field) {
            $state[$field] = $s?->{$field};
        }
        $this->form->fill($state);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $allowed = array_intersect_key($data, array_flip($this->settingsFields()));

        $settings = ShopSettings::getDefault();
        if ($settings === null) {
            ShopSettings::query()->create($allowed);
        } else {
            $settings->fill($allowed)->save();
        }

        Notification::make()->success()->title('Сохранено')->send();
    }
}
