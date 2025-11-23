<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Helpers\Helpers;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SemestersRelationManager extends RelationManager
{
    protected static string $relationship = 'semesters';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Semester')
                    ->disabled(), // Show semester name but don't allow editing
                TextInput::make('percentage')
                    ->label('Percentage')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('semesters')
            ->columns([
                TextColumn::make('pivot.student_id')
                ->label('Student ID'),
                TextColumn::make('student_name')
                    ->label('Name')
                    ->getStateUsing(fn () => $this->getOwnerRecord()->name),
                TextColumn::make('name'),
                TextColumn::make('paid_pct')
                    ->label('Paid %')
                    ->getStateUsing(function ($record) {
                        return Helpers::getStatus(
                            $this->getOwnerRecord()->student_id, // Get student_id from parent record
                            $record->id // semester_id from related record
                        )['percentage'];
                    })
                    ->color(function ($record) {
                        return Helpers::getStatus(
                            $this->getOwnerRecord()->student_id,
                            $record->id
                        )['status'];
                    })
                    ->sortable()
                    ->description(function ($record) {
                        $status = Helpers::getStatus(
                            $this->getOwnerRecord()->student_id,
                            $record->id
                        );
                        return 'Required: ' . $status['required'] . '%';
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([

            ])
            ->recordActions([
                EditAction::make(),

            ])
            ->toolbarActions([
                BulkActionGroup::make([

                ]),
            ]);
    }
}
