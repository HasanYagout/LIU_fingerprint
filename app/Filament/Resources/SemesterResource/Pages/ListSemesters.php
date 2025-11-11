<?php

namespace App\Filament\Resources\SemesterResource\Pages;

use App\Filament\Resources\SemesterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListSemesters extends ListRecords
{
    protected static string $resource = SemesterResource::class;

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return 'full';
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
