<?php

namespace App\Observers;

use App\Models\Bonus;
use App\Services\ModelLogService\ModelLogService;

class BonusObserver
{
    /**
     * Handle the Category "creating" event.
     *
     * @param Bonus $bonus
     * @return void
     */
    public function creating(Bonus $bonus): void
    {
    }

    /**
     * Handle the Bonus "created" event.
     *
     * @param Bonus $bonus
     * @return void
     */
    public function created(Bonus $bonus): void
    {
    }

    /**
     * Handle the Bonus "updated" event.
     *
     * @param Bonus $bonus
     * @return void
     */
    public function updated(Bonus $bonus): void
    {
		$bonus->loadMissing(['stock.cartDetails'])->stock?->cartDetails()->delete();
	}

    /**
     * Handle the Bonus "deleted" event.
     *
     * @param Bonus $bonus
     * @return void
     */
    public function deleted(Bonus $bonus): void
    {
		$bonus->loadMissing(['stock.cartDetails'])->stock?->cartDetails()->delete();

        (new ModelLogService)->logging($bonus, $bonus->getAttributes(), 'deleted');
    }

    /**
     * Handle the Bonus "restored" event.
     *
     * @param Bonus $bonus
     * @return void
     */
    public function restored(Bonus $bonus): void
    {
        (new ModelLogService)->logging($bonus, $bonus->getAttributes(), 'restored');
    }
}
