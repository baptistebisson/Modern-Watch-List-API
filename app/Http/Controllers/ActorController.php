<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Actor;

class ActorController extends BaseController
{
    public function getDetails(Request $request)
    {
        $actor = new Actor();
        $actor = $actor->getDetails($request->get('id'));
        return $actor;
    }

    public function getMovieCredits(Request $request)
    {
        $actor = new Actor();
        $actor = $actor->getMovieCredits($request->get('id'));
        return $actor;
    }
}
