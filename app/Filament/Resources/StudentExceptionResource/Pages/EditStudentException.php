<?php

namespace App\Filament\Resources\StudentExceptionResource\Pages;

use App\Filament\Resources\StudentExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentException extends EditRecord
{
    protected static string $resource = StudentExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
