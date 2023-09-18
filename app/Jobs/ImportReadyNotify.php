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
use Throwable;

class ImportReadyNotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private ?int $shopId = null, private ?string $filename = null)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $sellers = User::query()
                ->whereHas('shop', function ($q) {
                    $q->where('id', $this->shopId);
                })
                ->whereNotNull('firebase_token')
                ->pluck('firebase_token');

            $this->sendNotification($sellers->toArray(), 'Excel file imported successfully! But images will be updated later');
        } catch (Throwable $e) {
            Log::error('import ready notify ' . $e->getMessage());
        }
        try {
            File::delete($this->filename);
        } catch (Throwable $e) {
            Log::error('import ready filename ' . $e->getMessage());
        }
    }

}
