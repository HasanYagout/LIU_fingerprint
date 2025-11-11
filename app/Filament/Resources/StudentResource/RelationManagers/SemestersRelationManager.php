<?php
// app/Filament/Resources/StudentResource/Pages/SemesterStudentRelationManager.php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Helpers\Helpers;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\RelationManagers\RelationManager;

class SemestersRelationManager extends RelationManager
{
    protected static string $relationship = 'semesters';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('semester_id')
                ->relationship('semester', 'name')
                ->disabled(), // Show but don't allow changing semester

            Forms\Components\TextInput::make('pivot.percentage')
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
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('paid_pct')
                    ->label('Paid %')
                    ->getStateUsing(function ($record) {
                        return Helpers::getPaymentStatus(
                            $this->getOwnerRecord()->student_id, // Get student_id from parent record
                            $record->id // semester_id from related record
                        )['percentage'];
                    })
                    ->color(function ($record) {
                        return Helpers::getPaymentStatus(
                            $this->getOwnerRecord()->student_id,
                            $record->id
                        )['color'];
                    })
                    ->sortable()
                    ->description(function ($record) {
                        $status = Helpers::getPaymentStatus(
                            $this->getOwnerRecord()->student_id,
                            $record->id
                        );
                        return 'Required: ' . $status['required'] . '%';
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
//                Tables\Actions\EditAction::make(),
//                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
