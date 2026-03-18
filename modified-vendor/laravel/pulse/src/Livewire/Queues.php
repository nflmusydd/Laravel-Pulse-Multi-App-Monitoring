<?php

namespace Laravel\Pulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Laravel\Pulse\Recorders\Queues as QueuesRecorder;
use Livewire\Attributes\Lazy;
use Livewire\Livewire;
use Livewire\Attributes\Url;

/**
 * @internal
 */
#[Lazy]
class Queues extends Card
{
    #[Url(as: 'queues')]
    public ?string $filter = null;      //All

    public function mount()
    {
        // jangan timpa $filter, biar query string jalan
    }
    
    public function getQueuesProperty()
    {
        $allQueues = $this->fetchQueues();

        return is_null($this->filter)
            ? $allQueues
            : $allQueues->filter(fn($_, $app) => $app === $this->filter);

    }

    public function updatedFilter($value)
    {
        // Treat empty string as null
        $this->filter = $value ?: null;
    }


    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$queues, $time, $runAt] = $this->remember(fn () => $this->graph(
            ['queued', 'processing', 'processed', 'released', 'failed'],
            'count',
        ));

        if (Livewire::isLivewireRequest()) {
            $this->dispatch('queues-chart-update', queues: $queues);
        }
        // dd($queues);
        
        return View::make('pulse::livewire.queues', [
            'queues' => $queues,
            'showConnection' => $queues->keys()->map(fn ($queue) => Str::before($queue, ':'))->unique()->count() > 1,
            'time' => $time,
            'runAt' => $runAt,
            'config' => Config::get('pulse.recorders.'.QueuesRecorder::class),
        ]);
    }
}
