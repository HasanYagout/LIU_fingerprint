<?php

namespace App\Filament\Resources\Semesters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SemesterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                DatePicker::make('start_date')
                    ->required()
                    ->native(false)
                    ->format('d-m-Y')
                    ->displayFormat('d-m-Y'),

                DatePicker::make('midterm_date')
                    ->required()
                    ->native(false)
                    ->after('start_date')
                    ->displayFormat('Y-m-d'),

                DatePicker::make('end_date')
                    ->native(false)
                    ->required()
                    ->after('midterm_date')
                    ->displayFormat('Y-m-d'),


            ]);
    }
}
