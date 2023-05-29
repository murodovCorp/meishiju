<?php

namespace App\Jobs;

use App\Models\User;
use App\Traits\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImportReadyNotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private mixed $shopId, private mixed $filename)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $sellers = User::query()
            ->whereHas('shop', fn($q) => $q->where('id', $this->shopId))
            ->whereNotNull('firebase_token')
            ->pluck('firebase_token');

        $this->sendNotification($sellers->toArray(), 'Excel file imported successfully! But images will be updated later');

        File::delete($this->filename);

        Log::info('tugadi');
    }

}
