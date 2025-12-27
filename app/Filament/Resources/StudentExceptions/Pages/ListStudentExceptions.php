<?php

namespace App\Filament\Resources\StudentExceptions\Pages;

use App\Filament\Resources\StudentExceptions\StudentExceptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentExceptions extends ListRecords
{
    protected static string $resource = StudentExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
