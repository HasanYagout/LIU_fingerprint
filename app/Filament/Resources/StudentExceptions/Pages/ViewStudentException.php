<?php

namespace App\Filament\Resources\StudentExceptions\Pages;

use App\Filament\Resources\StudentExceptions\StudentExceptionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentException extends ViewRecord
{
    protected static string $resource = StudentExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
