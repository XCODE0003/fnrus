<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageReviews extends ManageRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(static function (array $data): array {
                    // Файл-аватар, если загружен, перезаписывает URL.
                    $file = $data['avatar_file'] ?? null;
                    $filePath = is_array($file) ? ($file[0] ?? null) : $file;
                    if (is_string($filePath) && $filePath !== '') {
                        $data['avatar'] = '/storage/' . ltrim($filePath, '/');
                    }
                    unset($data['avatar_file']);

                    $data['author_link'] = $data['author_link'] ?? '';
                    $data['link'] = $data['link'] ?? '';
                    $data['avatar'] = $data['avatar'] ?? '';
                    $data['created_at'] = time();
                    return $data;
                }),
        ];
    }
}
