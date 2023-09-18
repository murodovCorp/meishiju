<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ModelLogService\ModelLogService;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     *
     * @param User $user
     * @return void
     * @throws InvalidArgumentException
     */
    public function creating(User $user): void
    {
        $myReferral = Str::random(2) . $user->id . Str::random(2);

        if (Str::length($myReferral) > 8) {

            $myReferral = Str::limit($myReferral, 8);

        } else if (Str::length($myReferral) < 8) {

            $myReferral .= Str::random(8 - Str::length($myReferral));

        }

        $user->uuid         = Str::uuid();
        $user->my_referral  = Str::upper($myReferral);
    }

    /**
     * Handle the User "created" event.
     *
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        $user->point()->create();

        (new ModelLogService)->logging($user, $user->getAttributes(), 'created');
    }

    /**
     * Handle the User "updated" event.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
        (new ModelLogService)->logging($user, $user->getAttributes(), 'updated');
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function deleted(User $user): void
    {
        $user->shop()->delete();
        (new ModelLogService)->logging($user, $user->getAttributes(), 'deleted');
    }

    /**
     * Handle the User "restored" event.
     *
     * @param User $user
     * @return void
     */
    public function restored(User $user): void
    {
        (new ModelLogService)->logging($user, $user->getAttributes(), 'restored');
    }

}
