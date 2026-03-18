<?php

namespace Laravel\Pulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Recorders\CacheInteractions as CacheInteractionsRecorder;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;

/**
 * @internal
 */
#[Lazy]
class Cache extends Card
{
    use Concerns\HasPeriod, Concerns\RemembersQueries;

    #[Url(as: 'cache')]
    public ?string $appName = null;

    public function updatedAppName($value)
    {
        $this->appName = $value ?: null;
    }
    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        // [$cacheInteractions, $allTime, $allRunAt] = $this->remember(
        //     fn () => with(
        //         $this->aggregateTotal(['cache_hit', 'cache_miss'], 'count'),
        //         fn ($results) => (object) [
        //             'hits' => $results['cache_hit'] ?? 0,
        //             'misses' => $results['cache_miss'] ?? 0,
        //             ]
        //     ),
        //     'all'
        // );

        [$cacheKeyInteractions, $keyTime, $keyRunAt] = $this->remember(
            fn () => $this->aggregateTypes(['cache_hit', 'cache_miss'], 'count')
                ->map(function ($row) {
                    // dd($row->app_name);
                    return (object) [
                        'key' => $row->key,
                        'hits' => $row->cache_hit ?? 0,
                        'misses' => $row->cache_miss ?? 0,
                        'app_name' => $row->app_name ?? null,
                    ];
                }),
            'keys'
        );

        $allApps = $cacheKeyInteractions->pluck('app_name')->filter()->unique()->sort();
        
        // Filter appName jika ada
        $filteredInteractions = $this->appName
            ? $cacheKeyInteractions->filter(fn ($row) => $row->app_name === $this->appName)
            : $cacheKeyInteractions;
        
        $totalHits = $filteredInteractions->sum('hits');
        $totalMisses = $filteredInteractions->sum('misses');

        $allCacheInteractions = (object) [
            'hits' => $totalHits,
            'misses' => $totalMisses,
        ];
        
        // Ambil waktu global (nggak perlu dihitung ulang)
        [$allCacheStats, $allTime, $allRunAt] = $this->remember(
            fn () => with(
                $this->aggregateTotal(['cache_hit', 'cache_miss'], 'count'),
                fn ($results) => (object) [
                    'hits' => $results['cache_hit'] ?? 0,
                    'misses' => $results['cache_miss'] ?? 0,
                ]
            ),
            'all'
        );


        return View::make('pulse::livewire.cache', [
            'allTime' => $allTime,
            'allRunAt' => $allRunAt,
            // 'allCacheInteractions' => $cacheInteractions,
            'allCacheInteractions' => $allCacheInteractions,
            'keyTime' => $keyTime,
            'keyRunAt' => $keyRunAt,
            // 'cacheKeyInteractions' => $cacheKeyInteractions,
            'cacheKeyInteractions' => $filteredInteractions,
            'config' => Config::get('pulse.recorders.'.CacheInteractionsRecorder::class),
            'allApps' => $allApps,
        ]);
    }
}
