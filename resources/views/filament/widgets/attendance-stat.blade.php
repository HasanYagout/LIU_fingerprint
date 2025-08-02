<x-filament::widget>
    <x-filament::card>
        <div class="space-y-4">
            <!-- Date Range Picker -->
            <div class="flex space-x-4 items-end">
                <div class="flex-1">
                    <x-filament::input.wrapper>
                        <x-filament::input.label>Start Date</x-filament::input.label>
                        <x-filament::input.date
                            wire:model="startDate"
                            type="date"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div class="flex-1">
                    <x-filament::input.wrapper>
                        <x-filament::input.label>End Date</x-filament::input.label>
                        <x-filament::input.date
                            wire:model="endDate"
                            type="date"
                        />
                    </x-filament::input.wrapper>
                </div>

                <x-filament::button
                    wire:click="fetchData"
                    wire:loading.attr="disabled"
                    color="primary"
                >
                    <span wire:loading.remove>Apply</span>
                    <span wire:loading>Loading...</span>
                </x-filament::button>
            </div>

            <!-- Stats -->
            <div wire:loading.remove>
                @foreach($this->getStats() as $stat)
                    {{ $stat }}
                @endforeach
            </div>

            <!-- Loading Indicator -->
            <div wire:loading>
                <x-filament::card>
                    <div class="p-6 text-center">
                        <x-filament::loading-indicator class="h-8 w-8 mx-auto" />
                        <p class="mt-2 text-sm font-medium">Calculating statistics...</p>
                    </div>
                </x-filament::card>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
