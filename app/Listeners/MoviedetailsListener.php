<?php

namespace App\Listeners;

use App\Events\MoviedetailsEvent;
use App\Movie;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MoviedetailsListener implements ShouldQueue
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
     * @param MoviedetailsEvent $event
     */
    public function handle(MoviedetailsEvent $event)
    {
        // Get details of movie
        $movie = new Movie();
        $details = $movie->getMoreDetails($event->movie->id);
        Log::debug('MoviedetailsListener.php get more details of movie '. $event->movie->title, $details);
    }
}
