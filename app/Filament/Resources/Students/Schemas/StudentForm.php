<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\Semester;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')
                    ->label('Student ID')
                    ->required()
                    ->hiddenOn('edit')
                    ->unique(ignoreRecord: true)
                    ->rules([
                        'digits:8',  // Must be exactly 8 digits
                    ]),
                TextInput::make('name')
                    ->label('Full Name')
                    ->hiddenOn('edit')
                    ->required(),
                TextInput::make('major')
                    ->label('Major')
                    ->hiddenOn('edit')
                    ->required(),
                Select::make('semester_id')
                    ->hiddenOn('edit')
                    ->options(
                        Semester::where('status', 1)
                            ->get()
                            ->mapWithKeys(function ($semester) {
                                return [$semester->id => $semester->name . ' - ' . $semester->year];
                            })
                    )
                    ->preload()
                    ->required(),
                TextInput::make('percentage')
                    ->hiddenOn('edit')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),
            ]);
    }
}
