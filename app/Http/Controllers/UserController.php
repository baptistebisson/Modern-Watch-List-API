<?php

namespace App\Http\Controllers;

use App\Helpers\Utils;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;

class UserController extends BaseController
{
    public function getStats(Request $request)
    {
        $util = new Utils();
        $user = new User();
        $movie = $user->getStats($util->getUserId($request));
        return $movie;
    }

    public function deleteData(Request $request) {
        $util = new Utils();
        $user = new User();
        $movie = $user->deleteMovie(
            $request->get('id'),
            $request->get('type'),
            $util->getUserId($request)
        );
        return $movie;
    }

    public function addMark(Request $request) {
        $util = new Utils();
        $user = new User();
        $movie = $user->addMark(
            $request->get('id'),
            $request->get('type'),
            $request->get('mark'),
            $util->getUserId($request)
        );
        return $movie;
    }

    public function addToList(Request $request) {
        $util = new Utils();
        $user = new User();
        $response = $user->addToList(
            $request->get('id'),
            $request->get('type'),
            $util->getUserId($request)
        );
        return $response;
    }

    /**
     * Get user movies list
     * @param Request $request
     * @return string
     */
    public function getUserMovies(Request $request)
    {
        $util = new Utils();
        $user = new User();
        $user = $user->find($util->getUserId($request));
        $movies = $user->movies;

        return json_encode($movies);
    }
}
