<?php

namespace App\Http\Controllers;
use App\Helpers\Utils;
use Illuminate\Http\Request;
use App\Movies;
use App\User;
use App\movie_user;
use DB;
use Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

use Laravel\Lumen\Routing\Controller as BaseController;

class MovieController extends BaseController
{

    public function searchMovie(Request $request) {
        $movie = new Movies();
        $return['error'] = true;

        $movie_to_find = $request->get('title');
        $movie_to_find = str_replace(" ", "_", $movie_to_find);
        $first_letter = strtolower(substr($movie_to_find, 0, 1));
        if (strlen($movie_to_find) > 3) {
            $return = $movie->findMovie($movie_to_find, $first_letter);
        }

        return json_encode($return);
    }

    public function createMovie(Request $request)
    {
        $util = new Utils();
        $movie = new Movies();
        $movie = $movie->getMovie($request->get('id'), $util->getUserId($request));
        return $movie;
    }

    public function getUserMovies(Request $request)
    {
        $util = new Utils();
        $user = User::find($util->getUserId($request));
        $movies = $user->movies;

        return json_encode($movies);
    }

    public function getDetailsMovie(Request $request)
    {
        $util = new Utils();
        if (DB::table('movie_user')->where('movie_id', $request->get('id'))->where('user_id', $util->getUserId($request))->exists()) {
            $rate = DB::table('movie_user')
                ->where('movie_id', $request->get('id'))
                ->where('user_id', $util->getUserId($request))
                ->select('rating')->first();

            $movie = Movies::with('genres', 'actors', 'directors')
                ->where('id', $request->get('id'))
                ->first();

            $movie['user_rate'] = $rate->rating;
            return json_encode($movie);
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function moveMovie(Request $request) {
        $movie = new Movies();
        $util = new Utils();

        $return = $movie->moveMovie($request->get('movies'), $util->getUserId($request));
        return $return;
    }

    public function refresh(Request $request) {
        $movie = new Movies();
        $util = new Utils();

        $return = $movie->upToDate($request->get('movies'), $util->getUserId($request));
        return json_encode($return);
    }

    public function getPopularMovies() {
        $movie = new Movies();
        $movies = $movie->getPopularMovies();
        return json_encode($movies);
    }

}
