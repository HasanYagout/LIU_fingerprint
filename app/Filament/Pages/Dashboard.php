<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Forms\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    use InteractsWithPageFilters;
    use BaseDashboard\Concerns\HasFiltersForm;

    protected int | string | array $columnSpan = 'full';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('View:Dashboard');
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()->hasRole('manager') ? 'Monitoring' : 'Dashboard';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();

        // Only fill the form if user has permission to see filters
        if ($this->shouldShowFilters()) {
            $this->getFiltersForm()->fill([
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
        }
    }

    public function filtersForm(Schema $schema): Schema
    {
        // Return empty form if user shouldn't see filters
        if (!$this->shouldShowFilters()) {
            return $schema;
        }

        return $schema
            ->components([
                Section::make('Filters')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('startDate')
                                    ->label('Start Date')
                                    ->columnSpan(1)
                                    ->native(false)
                                    ->default(now()->startOfMonth()->format('Y-m-d'))
                                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('Y-m-d'))
                                    ->reactive() // Add this to make it reactive
                                    ->afterStateUpdated(function ($state) {
                                        // Trigger refresh when state changes
                                        $this->dispatch('updateCalendar');
                                    })
                                    ->rules([
                                        'before_or_equal:endDate',
                                        'before_or_equal:today',
                                    ])
                                    ->validationMessages([
                                        'before_or_equal' => 'Start date must be before or equal to end date and today.',
                                    ]),

                                DatePicker::make('endDate')
                                    ->label('End Date')
                                    ->columnSpan(1)
                                    ->native(false)
                                    ->default(now()->endOfMonth())
                                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('Y-m-d'))
                                    ->reactive() // Add this to make it reactive
                                    ->afterStateUpdated(function ($state) {
                                        // Trigger refresh when state changes
                                        $this->dispatch('updateCalendar');
                                    })
                                    ->rules([
                                        'after_or_equal:startDate',
                                        'before_or_equal:today',
                                    ])
                                    ->validationMessages([
                                        'after_or_equal' => 'End date must be after or equal to start date.',
                                        'before_or_equal' => 'End date cannot be in the future.',
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'shadow-2xl w-full']),
            ]);
    }

    protected function shouldShowFilters(): bool
    {
        return true;
    }
}
