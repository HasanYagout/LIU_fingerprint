<?php

namespace App\Filament\Resources\StudentExceptions;

use App\Filament\Resources\StudentExceptions\Pages\CreateStudentException;
use App\Filament\Resources\StudentExceptions\Pages\EditStudentException;
use App\Filament\Resources\StudentExceptions\Pages\ListStudentExceptions;
use App\Filament\Resources\StudentExceptions\Pages\ViewStudentException;
use App\Filament\Resources\StudentExceptions\Schemas\StudentExceptionForm;
use App\Filament\Resources\StudentExceptions\Schemas\StudentExceptionInfolist;
use App\Filament\Resources\StudentExceptions\Tables\StudentExceptionsTable;
use App\Models\StudentException;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class StudentExceptionResource extends Resource
{
    protected static ?string $model = StudentException::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StudentExceptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StudentExceptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentExceptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentExceptions::route('/'),
            'create' => CreateStudentException::route('/create'),
            'view' => ViewStudentException::route('/{record}'),
            'edit' => EditStudentException::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
