<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Slow Requests"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="{{ is_array($config['threshold']) ? '' : $config['threshold'].'ms threshold, ' }}past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.arrows-left-right />
        </x-slot:icon>
        <x-slot:actions>
            <x-pulse::select
                wire:model.live="orderBy"
                id="select-slow-requests-order-by"
                label="Sort by"
                :options="[
                    'slowest' => 'slowest',
                    'count' => 'count',
                ]"
                @change="loading = true"
            />

            {{-- untuk filter per app_name --}}
            @php
                $appOptions = $slowRequests->pluck('app_name')->filter()->unique()->sort();

                // Pastikan app yang dipilih tetap muncul di dropdown meskipun tidak ada di data
                if ($appName && !$appOptions->contains($appName)) {
                    $appOptions->push($appName);
                }

                $appOptions = [null => 'All'] + $appOptions->mapWithKeys(fn($name) => [$name => $name])->toArray();
            @endphp

            <x-pulse::select
                wire:model.live="appName"
                id="select-slow-requests-app-name"
                label="Filter App"
                :options="$appOptions"
                @change="loading = true"
            />
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($slowRequests->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <colgroup>
                    <col width="0%" />
                    <col width="100%" />
                    <col width="0%" />
                    <col width="0%" />
                    @if (empty($appName))
                        <col width="0%" />
                    @endif
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Method</x-pulse::th>
                        <x-pulse::th>Route</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                        <x-pulse::th class="text-right">Slowest</x-pulse::th>
                        @if (empty($appName))
                            <x-pulse::th class="text-right">App Name</x-pulse::th>
                        @endif
                    </tr>
                </x-pulse::thead>
                <tbody>
                    {{-- @foreach ($slowRequests->take(100) as $slowRequest) --}}
                    @foreach ($slowRequests->take(100)->filter(fn($query) => empty($appName) || $query->app_name === $appName)->take(100)  as $slowRequest)
                        <tr wire:key="{{ $slowRequest->method.$slowRequest->uri }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $slowRequest->method.$slowRequest->uri }}-row">
                            <x-pulse::td>
                                <x-pulse::http-method-badge :method="$slowRequest->method" />
                            </x-pulse::td>
                            <x-pulse::td class="overflow-hidden max-w-[1px]">
                                <code class="block text-xs text-gray-900 dark:text-gray-100 truncate" title="{{ $slowRequest->uri }}">
                                    {{ $slowRequest->uri }}
                                </code>
                                @if ($slowRequest->action)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $slowRequest->action }}">
                                        {{ $slowRequest->action }}
                                    </p>
                                @endif
                                @if (is_array($config['threshold']))
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $slowRequest->threshold }}ms threshold
                                    </p>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                @if ($config['sample_rate'] < 1)
                                    <span title="Sample rate: {{ $config['sample_rate'] }}, Raw value: {{ number_format($slowRequest->count) }}">~{{ number_format($slowRequest->count * (1 / $config['sample_rate'])) }}</span>
                                @else
                                    {{ number_format($slowRequest->count) }}
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                @if ($slowRequest->slowest === null)
                                <strong>Unknown</strong>
                                @else
                                <strong>{{ number_format($slowRequest->slowest) ?: '<1' }}</strong> ms
                                @endif
                            </x-pulse::td>
                            {{-- kolom app_name --}}
                            @if (empty($appName))
                                <x-pulse::td class="text-gray-700 dark:text-gray-300 font-bold text-right">
                                    {{ $slowRequest->app_name ?? 'Unknown' }}
                                </x-pulse::td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>

            @if ($slowRequests->count() > 100)
                <div class="mt-2 text-xs text-gray-400 text-center">Limited to 100 entries</div>
            @endif
        @endif
    </x-pulse::scroll>
</x-pulse::card>
