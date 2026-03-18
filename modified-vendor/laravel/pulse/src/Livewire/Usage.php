<?php

namespace Laravel\Pulse\Livewire;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Recorders\SlowRequests;
use Laravel\Pulse\Recorders\UserJobs;
use Laravel\Pulse\Recorders\UserRequests;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Laravel\Pulse\Storage\DatabaseStorage;

/**
 * @internal
 */
#[Lazy]
class Usage extends Card
{
    /**
     * The type of usage to show.
     *
     * @var 'requests'|'slow_requests'|'jobs'|null
     */
    public ?string $type = null;

    /**
     * The usage type.
     *
     * @var 'requests'|'slow_requests'|'jobs'
     */

    // bawaaan pulse
    // #[Url]
    // public string $usage = 'requests';
    #[Url(as: 'usage')]
    public string $filter = 'requests,'; 

    public string $usage = 'requests'; 
    public ?string $appName = null;    


    protected DatabaseStorage $storage;

    public function __construct()
    {
        $this->storage = app(DatabaseStorage::class);
    }

    public function mount()
    {
        $parts = explode(',', $this->filter);
        $this->usage = $parts[0] ?? 'requests';
        $this->appName = $parts[1] ?? null;
    }

    public function updated($property)
    {
        if (in_array($property, ['usage', 'appName'])) {
            $this->filter = $this->usage . ',' . ($this->appName ?? '');
        }
    }


    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        $type = $this->type ?? $this->usage;

        [$userRequestCounts, $time, $runAt] = $this->remember(
            function () use ($type) {
                $counts = $this->aggregate(
                    match ($type) {
                        'requests' => 'user_request',
                        'slow_requests' => 'slow_user_request',
                        'jobs' => 'user_job',
                    },
                    'count',
                    // CarbonInterval::minutes(10), // wajib ada kalau function buatan
                    limit: 12,
                );
                //bawaan pulse, key = id 
                $users = Pulse::resolveUsers($counts->pluck('key'));   

                $dd = $counts->map(fn ($row) => (object) [
                    'key' => $row->key,
                    'user' => $users->find($row->key),
                    'count' => (int) $row->count,
                    'row' => $row,
                    'user_raw' => $users,
                ]);
                // dd($dd);
                return $counts->map(fn ($row) => (object) [
                    'key' => $row->key,
                    'user' => $users->find($row->key),
                    'count' => (int) $row->count,
                    'app_name' => $row->app_name,
                ]);
            },
            $type
        );

        // Ambil semua app name sebelum difilter
        $allApps = collect($userRequestCounts)->pluck('app_name')->filter()->unique()->sort();

        // Filter per app name untuk tampilan
        if ($this->appName) {
            $userRequestCounts = $userRequestCounts->filter(fn ($row) => $row->app_name === $this->appName);
        }

        // dd($userRequestCounts);
        return View::make('pulse::livewire.usage', [
            'time' => $time,
            'runAt' => $runAt,
            'userRequestsConfig' => Config::get('pulse.recorders.'.UserRequests::class),
            'slowRequestsConfig' => Config::get('pulse.recorders.'.SlowRequests::class),
            'jobsConfig' => Config::get('pulse.recorders.'.UserJobs::class),
            'userRequestCounts' => $userRequestCounts,
            'allApps' => $allApps,
        ]);
    }
}
