<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="search" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{ $this->form }}
            </div>

            <div class="flex justify-end mt-4">
                <x-filament::button
                    type="submit"
                    :loading="$loading"
                    icon="heroicon-o-magnifying-glass"
                    color="primary"
                >
                    Search
                </x-filament::button>
            </div>
        </form>

        {{ $this->table }}

        @php
            $paginationInfo = $this->getPaginationInfo();
        @endphp

        @if ($paginationInfo['total'] > 0)
            <div class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                Showing
                <span class="font-medium">{{ $paginationInfo['from'] ?? 0 }}</span>
                to
                <span class="font-medium">{{ $paginationInfo['to'] ?? 0 }}</span>
                of
                <span class="font-medium">{{ $paginationInfo['total'] ?? 0 }}</span>
                records
            </div>
        @else
            <div class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 text-center">
                No records found.
            </div>
        @endif
    </div>

    <script>
        // Auto-refresh when typing (optional)
        document.addEventListener('livewire:load', function () {
            let studentIdTimeout;

            Livewire.hook('element.updated', (el, component) => {
                if (el.getAttribute('wire:model') === 'student_id') {
                    clearTimeout(studentIdTimeout);
                    studentIdTimeout = setTimeout(() => {
                        @this.search();
                    }, 800); // Wait 800ms after typing stops
                }

                if (el.getAttribute('wire:model') === 'date') {
                    @this.search();
                }
            });
        });
    </script>
</x-filament-panels::page>
