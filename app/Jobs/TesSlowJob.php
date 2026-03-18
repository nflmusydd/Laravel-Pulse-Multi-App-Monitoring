<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TesSlowJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // // Simulasi proses berat
        sleep(3); // 3 detik
        // \Log::info("Slow job selesai.");

        // if (rand(0, 1) === 0) { 
        //     // gagal, kembalikan ke queue dengan delay 5 detik
        //     $this->release(5);
        //     return;
        // }
    
        // // kalau sukses
        // \Log::info("Job berhasil diproses di " . now());
    }
}
