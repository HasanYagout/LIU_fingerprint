<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

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
