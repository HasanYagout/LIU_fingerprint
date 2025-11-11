<?php
// app/Filament/Resources/StudentResource/Pages/EditStudent.php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function resolveRecord(int | string $key): Model
    {
        return $this->getModel()::where('student_id', $key)->firstOrFail();
    }

    protected function getHeaderActions(): array
    {
        return [
//            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return []; // Return empty array to hide all buttons
    }
}
