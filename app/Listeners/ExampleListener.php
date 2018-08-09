<?php

namespace App\Listeners;

use App\Events\ExampleEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExampleListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(ExampleEvent $event)
    {
        DB::table('users')
            ->where('id', $event->movie_user->user_id)
            ->update(['updated_at' => $event->movie_user->date_added]);

        Log::debug('Event listener updating user '. $event->movie_user->user_id,
            array(json_encode($event->movie_user->user_id)));
    }
}
