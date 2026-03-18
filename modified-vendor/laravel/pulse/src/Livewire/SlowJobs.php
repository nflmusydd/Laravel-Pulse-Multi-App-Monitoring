<?php

namespace Laravel\Pulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Recorders\Concerns\Thresholds;
use Laravel\Pulse\Recorders\SlowJobs as SlowJobsRecorder;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;

/**
 * @internal
 */
#[Lazy]
class SlowJobs extends Card
{
    use Thresholds;

    /**
     * Ordering.
     *
     * @var 'slowest'|'count'
     */
    // default bawaan
    // #[Url(as: 'slow-jobs')]
    // public string $orderBy = 'slowest';

    #[Url(as: 'slow-jobs')]
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
        [$slowJobs, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(
                'slow_job',
                ['max', 'count'],
                match ($this->orderBy) {
                    'count' => 'count',
                    default => 'max',
                },
            )->map(fn ($row) => (object) [
                'job' => $row->key,
                'slowest' => $row->max,
                'count' => $row->count,
                'count' => $row->count,
                'app_name' => $row->app_name ?? null,
                'threshold' => $this->threshold($row->key, SlowJobsRecorder::class),
            ]),
            $this->orderBy,
        );
        // dd($slowJobs);

        return View::make('pulse::livewire.slow-jobs', [
            'time' => $time,
            'runAt' => $runAt,
            'config' => Config::get('pulse.recorders.'.SlowJobsRecorder::class),
            'slowJobs' => $slowJobs,
        ]);
    }
}
