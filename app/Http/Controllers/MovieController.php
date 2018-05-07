<?php

namespace App\Http\Controllers;
use App\Helpers\Response;
use App\Helpers\Utils;
use Illuminate\Http\Request;
use App\Movie;
use App\User;
use App\movie_user;
use DB;
use Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

use Laravel\Lumen\Routing\Controller as BaseController;

class MovieController extends BaseController
{

    /**
     * Search a movie online
     * @param Request $request
     * @return string Result
     */
    public function searchMovie(Request $request) {
        $movies = null;
        $movie = new Movie();

        $request_title = $request->get('title');
        $movie_to_find = str_replace(" ", "%20%", $request_title);
        if (strlen($movie_to_find) > 3) {
            $movies = $movie->findMovie($movie_to_find);
        }

        return json_encode($movies);
    }

    /**
     * Create a movie into the database
     * @param Request $request
     * @return Movie|array
     */
    public function createMovie(Request $request)
    {
        $util = new Utils();
        $movie = new Movie();
        $movie = $movie->getMovie($request->get('id'), $util->getUserId($request));
        return $movie;
    }

    /**
     * Get user movies list
     * @param Request $request
     * @return string
     */
    public function getUserMovies(Request $request)
    {
        $util = new Utils();
        $user = User::find($util->getUserId($request));
        $movies = $user->movies;

        return json_encode($movies);
    }

    /**
     * Get details of a movie
     * @param Request $request
     * @return string
     */
    public function getDetailsMovie(Request $request)
    {
        $util = new Utils();
        if (DB::table('movie_user')->where('movie_id', $request->get('id'))->where('user_id', $util->getUserId($request))->exists()) {
            $rate = DB::table('movie_user')
                ->where('movie_id', $request->get('id'))
                ->where('user_id', $util->getUserId($request))
                ->select('rating')->first();

            $movie = Movie::with('genres', 'actors', 'directors')
                ->where('id', $request->get('id'))
                ->first();

            $movie['user_rate'] = $rate->rating;
            return json_encode($movie);
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Move the position of a movie into user list
     * @param Request $request
     * @return array
     */
    public function moveMovie(Request $request) {
        $movie = new Movie();
        $util = new Utils();

        $return = $movie->moveMovie($request->get('movies'), $util->getUserId($request));
        return $return;
    }

    /**
     * Refresh movies list
     * @param Request $request
     * @return string
     */
    public function refresh(Request $request) {
        $movie = new Movie();
        $util = new Utils();

        $return = $movie->upToDate($request->get('movies'), $util->getUserId($request));
        return json_encode($return);
    }

    /**
     * Get popular movies
     * @return string
     */
    public function getPopularMovies() {
        $movie = new Movie();
        $movies = $movie->getPopularMovies();
        return json_encode($movies);
    }

}
