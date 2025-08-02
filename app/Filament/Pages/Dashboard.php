<?php

namespace App\Filament\Pages;


use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;
    protected ?string $maxContentWidth='full';

    public function filtersForm(Form $form): Form
    {


        return $form
            ->schema([
                Section::make('Filters')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->native(false)
                            ->translateLabel()
                            ->displayFormat('Y-m-d')
                            ->default(now()->startOfMonth()),


                DatePicker::make('endDate')
                            ->label('End Date')
                            ->translateLabel()
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->default(now()->endOfMonth()),

                    ])
                    ->extraAttributes([
                        'class'=>'shadow-2xl'
                    ])
                    ->columns(2),
            ]);
    }




}
