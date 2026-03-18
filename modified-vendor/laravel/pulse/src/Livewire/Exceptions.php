<?php

namespace Laravel\Pulse\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Recorders\Exceptions as ExceptionsRecorder;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;

/**
 * @internal
 */
#[Lazy]
class Exceptions extends Card
{
    /**
     * Ordering.
     *
     * @var 'count'|'latest'
     */
    // default bawaan
    // #[Url(as: 'exceptions')]
    // public string $orderBy = 'count';

    #[Url(as: 'exceptions')]
    public string $filter = 'count,';

    public string $orderBy = 'count';
    public ?string $appName = null;

    public function mount()
    {
        $parts = explode(',', $this->filter);
        $this->orderBy = $parts[0] ?? 'count';
        $this->appName = $parts[1] ?? null;
    }

    public function updated($property)
    {
        if (in_array($property, ['orderBy', 'appName'])) {
            $this->filter = $this->orderBy . ',' . ($this->appName ?? '');
        }
    }

    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$exceptions, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(
                'exception',
                ['max', 'count'],
                match ($this->orderBy) {
                    'latest' => 'max',
                    default => 'count'
                },
            )->map(function ($row) {
                [$class, $location] = json_decode($row->key, flags: JSON_THROW_ON_ERROR);
                return (object) [
                    'class' => $class,
                    'location' => $location,
                    'latest' => CarbonImmutable::createFromTimestamp($row->max),
                    'count' => $row->count,
                    'app_name' => $row->app_name ?? null,
                ];
            }),
            $this->orderBy
        );
        return View::make('pulse::livewire.exceptions', [
            'time' => $time,
            'runAt' => $runAt,
            'exceptions' => $exceptions,
            'config' => Config::get('pulse.recorders.'.ExceptionsRecorder::class),
        ]);
    }
}
