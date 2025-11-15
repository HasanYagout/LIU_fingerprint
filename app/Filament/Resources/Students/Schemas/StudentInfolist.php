<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Carbon;

class StudentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('student_id')
                    ->label('Student ID')
                    ->color('primary'),

                TextEntry::make('name')
                    ->label('Full Name')
                    ->color('primary'),

                TextEntry::make('major')
                    ->color('primary'),

                Section::make('Semesters')
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('semesters')
                            ->schema([
                                Grid::make(2) // 2 columns side by side
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Semester')
                                        ->color('primary'),

                                    TextEntry::make('pivot.percentage')
                                        ->label('Percentage')
                                        ->suffix('%')
                                        ->color('primary'),
                                ]),
                            ])
                            ->grid(2), // make repeatable show 2 items per row
                    ]),
            ]);
    }
}
