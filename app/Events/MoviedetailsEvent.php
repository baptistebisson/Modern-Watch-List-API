<?php

namespace App\Events;

use App\Movie;

class MoviedetailsEvent extends Event {
    public $movie;

    public function __construct(Movie $movie)
    {
        $this->movie = $movie;
    }
}