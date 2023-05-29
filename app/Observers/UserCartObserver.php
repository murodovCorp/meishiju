<?php

namespace App\Observers;

use App\Models\UserCart;
use App\Services\ModelLogService\ModelLogService;
use Illuminate\Support\Str;

class UserCartObserver
{
    /**
     * Handle the UserCart "creating" event.
     *
     * @param UserCart $userCart
     * @return void
     */
    public function creating(UserCart $userCart): void
    {
        $userCart->uuid = Str::uuid();
    }

    /**
     * Handle the UserCart "created" event.
     *
     * @param UserCart $userCart
     * @return void
     */
    public function created(UserCart $userCart): void
    {
//        (new ModelLogService)->logging($userCart, $userCart->getAttributes(), 'created');
    }

    /**
     * Handle the UserCart "updated" event.
     *
     * @param UserCart $userCart
     * @return void
     */
    public function updated(UserCart $userCart): void
    {
//        (new ModelLogService)->logging($userCart, $userCart->getAttributes(), 'updated');
    }

    /**
     * Handle the UserCart "deleted" event.
     *
     * @param UserCart $userCart
     * @return void
     */
    public function deleted(UserCart $userCart): void
    {
//        (new ModelLogService)->logging($userCart, $userCart->getAttributes(), 'deleted');
    }

    /**
     * Handle the UserCart "restored" event.
     *
     * @param UserCart $userCart
     * @return void
     */
    public function restored(UserCart $userCart): void
    {
//        (new ModelLogService)->logging($userCart, $userCart->getAttributes(), 'restored');
    }
}
