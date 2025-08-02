{{-- resources/views/filament/pages/attendance-log-search.blade.php --}}

<x-filament::page>
    <div class="space-y-6">
        <form wire:submit.prevent="search" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{ $this->form }}
            </div>

            <div class="flex justify-end mt-4">
                <x-filament::button
                    type="submit"
                    :loading="$loading"
                    icon="{!! $loading ? 'heroicon-o-refresh animate-spin' : '' !!}"
                    color="primary"
                >
                    Search
                </x-filament::button>
            </div>
        </form>

{{--        @if($error)--}}
{{--            <div class="bg-red-50 dark:bg-red-900/10 border-l-4 border-red-400 dark:border-red-600 p-4">--}}
{{--                <div class="flex">--}}
{{--                    <div class="flex-shrink-0">--}}
{{--                        <svg class="h-5 w-5 text-red-400 dark:text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"--}}
{{--                             fill="currentColor">--}}
{{--                            <path fill-rule="evenodd"--}}
{{--                                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"--}}
{{--                                  clip-rule="evenodd"/>--}}
{{--                        </svg>--}}
{{--                    </div>--}}
{{--                    <div class="ml-3">--}}
{{--                        <p class="text-sm text-red-700 dark:text-red-300">--}}
{{--                            {{ $error }}--}}
{{--                        </p>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        @endif--}}

        @if(count($logs) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-primary-600 dark:bg-primary-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date (Day)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Student ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Terminal ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ Carbon\Carbon::createFromFormat('Ymd', $log['C_Date'])->format('l, Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ Carbon\Carbon::createFromFormat('His', $log['C_Time'])->format('h:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ $log['C_Name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ $log['C_Unique'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ $log['L_TID'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @switch($log['L_Result'])
                                        @case(0)
                                            <x-filament::badge color="success" class="inline-flex items-center">
                                                <x-heroicon-o-check-circle class="w-4 h-4 mr-1" />
                                                Present
                                            </x-filament::badge>
                                            @break

                                        @case(3)
                                            <x-filament::badge color="danger" class="inline-flex items-center">
                                                <x-heroicon-o-x-circle class="w-4 h-4 mr-1" />
                                                No Permission
                                            </x-filament::badge>
                                            @break

                                        @default
                                            <x-filament::badge color="warning" class="inline-flex items-center">
                                                <x-heroicon-o-exclamation-circle class="w-4 h-4 mr-1" />
                                                Unknown
                                            </x-filament::badge>
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center text-gray-500 dark:text-gray-400">
                No attendance logs found. Perform a search to view results.
            </div>
        @endif
    </div>
</x-filament::page>
