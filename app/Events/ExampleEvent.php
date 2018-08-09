<?php

namespace App\Events;

use App\Movieusers;

class ExampleEvent extends Event
{
    public $movie_user;

    /**
     * Create a new event instance.
     * @param Movieusers $movie_users
     */
    public function __construct(Movieusers $movie_users)
    {
        $this->movie_user = $movie_users;
    }
}
