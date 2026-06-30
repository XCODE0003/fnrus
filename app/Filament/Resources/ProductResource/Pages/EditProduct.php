<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * The "Функционал" repeater (functional_data) is dehydrated(false) and
     * is hydrated for display from the `functional` JSON column. Without
     * this hook, edits to it were silently dropped on save ("не изменяется
     * в админке"). Mirror CreateProduct: rebuild the `functional` JSON from
     * the repeater state on every save.
     *
     * Read from $this->data (raw Livewire form state) so it works regardless
     * of the field being excluded from the dehydrated $data.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $blocks = (array) ($this->data['functional_data'] ?? $data['functional_data'] ?? []);

        // Same shape the storefront expects: [{id, title, lines[]}, ...].
        $idValues = ['visuals', 'aimbot', 'misc'];
        $functional = [];
        foreach (array_values($blocks) as $index => $block) {
            if (! is_array($block)) {
                continue;
            }
            $title = trim((string) ($block['title'] ?? ''));
            $lines = array_values(array_filter(
                array_map('trim', explode("\n", (string) ($block['lines'] ?? ''))),
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

        $data['functional'] = json_encode($functional, JSON_UNESCAPED_UNICODE);

        // These are handled separately (functional above, tariffs on their own
        // page) — never let them reach the model's mass-assignment.
        unset($data['functional_data'], $data['tariffs_data']);

        return $data;
    }
}
