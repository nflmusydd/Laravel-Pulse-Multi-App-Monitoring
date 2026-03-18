<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Exceptions"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.bug-ant />
        </x-slot:icon>
        <x-slot:actions>
            <x-pulse::select
                wire:model.live="orderBy"
                id="select-exceptions-order-by"
                label="Sort by"
                :options="[
                    'count' => 'count',
                    'latest' => 'latest',
                ]"
                @change="loading = true"
            />

            {{-- untuk filter per app_name --}}
            @php
                $appOptions = $exceptions->pluck('app_name')->filter()->unique()->sort();

                // Pastikan app yang dipilih tetap muncul di dropdown meskipun tidak ada di data
                if ($appName && !$appOptions->contains($appName)) {
                    $appOptions->push($appName);
                }

                $appOptions = [null => 'All'] + $appOptions->mapWithKeys(fn($name) => [$name => $name])->toArray();
            @endphp

            <x-pulse::select
                wire:model.live="appName"
                id="select-exceptions-app-name"
                label="Filter App"
                :options="$appOptions"
                @change="loading = true"
            />
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($exceptions->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <colgroup>
                    <col width="100%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    @if (empty($appName))
                        <col width="0%" />
                    @endif
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Type</x-pulse::th>
                        <x-pulse::th class="text-right">Latest</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                        @if (empty($appName))
                            <x-pulse::th class="text-right">App Name</x-pulse::th>
                        @endif
                    </tr>
                </x-pulse::thead>
                <tbody>
                    {{-- @foreach ($exceptions->take(100) as $exception) --}}
                    @foreach ($exceptions->take(100)->filter(fn($query) => empty($appName) || $query->app_name === $appName)->take(100)  as $exception)
                        <tr wire:key="{{ $exception->class.$exception->location }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $exception->class.$exception->location }}-row">
                            <x-pulse::td class="max-w-[1px]">
                                <code class="block text-xs text-gray-900 dark:text-gray-100 truncate" title="{{ $exception->class }}">
                                    {{ $exception->class }}
                                </code>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $exception->location }}">
                                    {{ $exception->location }}
                                </p>
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                {{ $exception->latest->ago(syntax: Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                @if ($config['sample_rate'] < 1)
                                    <span title="Sample rate: {{ $config['sample_rate'] }}, Raw value: {{ number_format($exception->count) }}">~{{ number_format($exception->count * (1 / $config['sample_rate'])) }}</span>
                                @else
                                    {{ number_format($exception->count) }}
                                @endif
                            </x-pulse::td>

                            {{-- kolom app_name --}}
                            @if (empty($appName))
                                <x-pulse::td class="text-gray-700 dark:text-gray-300 font-bold text-right">
                                {{ $exception->app_name ?? 'Unknown' }}
                            </x-pulse::td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>
        @endif

        @if ($exceptions->count() > 100)
            <div class="mt-2 text-xs text-gray-400 text-center">Limited to 100 entries</div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
