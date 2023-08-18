<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderAutoRemove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:auto:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove canceled orders';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
		$time = Settings::adminSettings()->where('key', 'order_auto_remove')->first()?->value ?? 5;
		$time = date('Y-m-d 23:59:59', strtotime("-$time minute"));

        $orders = Order::where('created_at', '<=', $time)->get();

        foreach ($orders as $order) {

            try {
				$order->delete();
            } catch (Throwable $e) {
                Log::error($e->getMessage(), [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTrace(),
                    'file'    => $e->getFile(),
                ]);
            }

        }

        return 0;
    }
}
