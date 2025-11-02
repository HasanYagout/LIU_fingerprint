<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Role Name'),
                MultiSelect::make('permissions')
                    ->label('Permissions')
                    ->relationship('permissions', 'name'),
            ]);
    }
}
