<?php

namespace App\Http\Controllers;

use App\Helpers\Utils;
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

    public function getMoreDetails(Request $request) {
        $util = new Utils();
        $result = $util->getPersonMoreDetails('actor', $request->get('id'));
        return $result;
    }
}
