<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\ModelLogService\ModelLogService;
use Illuminate\Support\Str;

class TicketObserver
{
    /**
     * Handle the Category "creating" event.
     *
     * @param Ticket $ticket
     * @return void
     */
    public function creating(Ticket $ticket): void
    {
        $ticket->uuid = Str::uuid();
    }

    /**
     * Handle the Ticket "created" event.
     *
     * @param Ticket $ticket
     * @return void
     */
    public function created(Ticket $ticket): void
    {
        (new ModelLogService)->logging($ticket, $ticket->getAttributes(), 'created');
    }

    /**
     * Handle the Ticket "updated" event.
     *
     * @param Ticket $ticket
     * @return void
     */
    public function updated(Ticket $ticket): void
    {
        (new ModelLogService)->logging($ticket, $ticket->getAttributes(), 'updated');
    }

    /**
     * Handle the Ticket "deleted" event.
     *
     * @param Ticket $ticket
     * @return void
     */
    public function deleted(Ticket $ticket): void
    {
        (new ModelLogService)->logging($ticket, $ticket->getAttributes(), 'deleted');
    }

    /**
     * Handle the Ticket "restored" event.
     *
     * @param Ticket $ticket
     * @return void
     */
    public function restored(Ticket $ticket): void
    {
        (new ModelLogService)->logging($ticket, $ticket->getAttributes(), 'restored');
    }
}
