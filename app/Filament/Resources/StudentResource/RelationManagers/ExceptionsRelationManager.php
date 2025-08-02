<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\Semester;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::where('status',1)->pluck('name', 'id'))
                    ->required(),
                DatePicker::make('from_date')
                    ->native(false)
                    ->placeholder('DD-MM-YYYY')
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('to_date')
                    ->native(false)
                    ->placeholder('DD-MM-YYYY')
                    ->displayFormat('d/m/Y')
                    ->required(),
                Forms\Components\Textarea::make('reason'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('semester_id')
            ->columns([
                Tables\Columns\TextColumn::make('semester.name')
                    ->label('Semester'),
                Tables\Columns\TextColumn::make('semester.year')
                    ->label('Year'),
                Tables\Columns\TextColumn::make('from_date')
                    ->date(),
                Tables\Columns\TextColumn::make('to_date')
                    ->date(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
