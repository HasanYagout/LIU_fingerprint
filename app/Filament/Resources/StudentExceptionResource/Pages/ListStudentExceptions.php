<?php

namespace App\Filament\Resources\StudentExceptionResource\Pages;

use App\Filament\Resources\StudentExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentExceptions extends ListRecords
{
    protected static string $resource = StudentExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
