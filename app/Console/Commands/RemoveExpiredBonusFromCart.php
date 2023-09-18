<?php

namespace App\Console\Commands;

use App\Models\Bonus;
use DB;
use Illuminate\Console\Command;

class RemoveExpiredBonusFromCart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:expired:bonus:from:cart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove expired bonuses from cart';

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

        $bonuses = Bonus::where('expired_at', '<=', now())
            ->pluck('bonus_stock_id')
            ->toArray();

        DB::table('cart_details')
            ->where('bonus', true)
            ->whereIn('stock_id', $bonuses)
            ->delete();

        return 0;
    }
}
