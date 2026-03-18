<?php

namespace Laravel\Pulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Recorders\Concerns\Thresholds;
use Laravel\Pulse\Recorders\SlowRequests as SlowRequestsRecorder;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;

/**
 * @internal
 */
#[Lazy]
class SlowRequests extends Card
{
    use Thresholds;

    /**
     * Ordering.
     *
     * @var 'slowest'|'count'
     */
    // default bawaan
    // #[Url(as: 'slow-requests')]
    // public string $orderBy = 'slowest';

    #[Url(as: 'slow-requests')]
    public string $filter = 'slowest,';

    public string $orderBy = 'slowest';
    public ?string $appName = null;

    public function mount()
    {
        $parts = explode(',', $this->filter);
        $this->orderBy = $parts[0] ?? 'slowest';
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
        [$slowRequests, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(
                'slow_request',
                ['max', 'count'],
                match ($this->orderBy) {
                    'count' => 'count',
                    default => 'max',
                },
            )->map(function ($row) {
                [$method, $uri, $action] = json_decode($row->key, flags: JSON_THROW_ON_ERROR);

                return (object) [
                    'uri' => $uri,
                    'method' => $method,
                    'action' => $action,
                    'count' => $row->count,
                    'slowest' => $row->max,
                    'threshold' => $this->threshold($uri, SlowRequestsRecorder::class),
                    'app_name' => $row->app_name ?? null,
                ];
            }),
            $this->orderBy,
        );

        return View::make('pulse::livewire.slow-requests', [
            'time' => $time,
            'runAt' => $runAt,
            'slowRequests' => $slowRequests,
            'config' => [
                'threshold' => Config::get('pulse.recorders.'.SlowRequestsRecorder::class.'.threshold'),
                'sample_rate' => Config::get('pulse.recorders.'.SlowRequestsRecorder::class.'.sample_rate'),
            ],
        ]);
    }
}
