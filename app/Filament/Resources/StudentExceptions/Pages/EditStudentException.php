<?php

namespace App\Filament\Resources\StudentExceptions\Pages;

use App\Filament\Resources\StudentExceptions\StudentExceptionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentException extends EditRecord
{
    protected static string $resource = StudentExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            ViewAction::make(),
            DeleteAction::make(),
//            ForceDeleteAction::make(),
//            RestoreAction::make(),
        ];
    }
}
