<?php

namespace Laravel\Pulse\Livewire;
// dd('masuk modified vendor');
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Recorders\Concerns\Thresholds;
use Laravel\Pulse\Recorders\SlowOutgoingRequests as SlowOutgoingRequestsRecorder;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;

/**
 * @internal
 */
#[Lazy]
class SlowOutgoingRequests extends Card
{
    use Thresholds;
    /**
     * Ordering.
     *
     * @var 'slowest'|'count'
     */
    // default bawaan
    // #[Url(as: 'slow-outgoing-requests')]
    // public string $orderBy = 'slowest';

    #[Url(as: 'slow-outgoing-requests')]
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
        // dd('function render modified vendor');
        // \Log::info('function render modified vendor');   //gak ke-eksekusi

        [$slowOutgoingRequests, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(
                'slow_outgoing_request',
                ['max', 'count'],
                match ($this->orderBy) {
                    'count' => 'count',
                    default => 'max',
                },
            )->map(function ($row) {
                [$method, $uri] = json_decode($row->key, flags: JSON_THROW_ON_ERROR);

                return (object) [
                    'method' => $method,
                    'uri' => $uri,
                    'slowest' => $row->max,
                    'count' => $row->count,
                    'app_name' => $row->app_name ?? null,
                    'threshold' => $this->threshold($uri, SlowOutgoingRequestsRecorder::class),
                ];
            }),
            $this->orderBy,
        );

        return View::make('pulse::livewire.slow-outgoing-requests', [
            'time' => $time,
            'runAt' => $runAt,
            'config' => Config::get('pulse.recorders.'.SlowOutgoingRequestsRecorder::class),
            'slowOutgoingRequests' => $slowOutgoingRequests,
        ]);
    }
}
