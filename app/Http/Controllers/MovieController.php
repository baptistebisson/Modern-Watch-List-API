<?php

namespace App\Http\Controllers;
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
        $movie = new Movies();
        $movie = $movie->getMovie($request->get('id'), $request->get('user_id'));
        return $movie;
    }

    public function getUserMovies()
    {
        $movie = new Movies();
        $user = User::find(1);
        $return = null;
        $return = $user->movies;
        //dd($return->toSql(), $return->getBindings());
        return json_encode($return);
    }

    public function getDetailsMovie(Request $request)
    {
        $movie = new Movies();
        if (DB::table('movie_user')->where('movie_id', $request->get('id'))->where('user_id', 1)->exists()) {
            $rate = DB::table('movie_user')->where('movie_id', $request->get('id'))->where('user_id', 1)->select('rating')->first();
            $movie = Movies::with('genres', 'actors', 'directors')->where('id', $request->get('id'))->first();
            $movie['user_rate'] = $rate->rating;
            return json_encode($movie);
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function moveMovie(Request $request) {
        $movie = new Movies();
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();

        $user = JWTAuth::toUser($token);
        $return = $movie->moveMovie($request->get('movies'), $user->id);
        return $return;
    }

    public function refresh(Request $request) {
        $movie = new Movies();
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();

        $user = JWTAuth::toUser($token);
        $return = $movie->upToDate($request->get('movies'), $user->id);
        return json_encode($return);
    }

    public function getPopularMovies() {
        $movie = new Movies();
        $movies = $movie->getPopularMovies();
        return json_encode($movies);
    }

}
