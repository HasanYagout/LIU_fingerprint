<?php

namespace App\Filament\Resources\Semesters\Pages;

use App\Filament\Resources\Semesters\SemesterResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSemester extends EditRecord
{
    protected static string $resource = SemesterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action) {
                    $semester = $this->record;

                    // Check related students
                    if ($semester->students()->exists()) {
                        Notification::make()
                            ->title('Cannot delete semester')
                            ->body('This semester has enrolled students and cannot be deleted.')
                            ->danger()
                            ->send();
                        $action->cancel();   // ⛔ stop the deletion
                    }
                }),
        ];
    }
}
