<?php

namespace App;

use App\Helpers\Utils;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use phpDocumentor\Reflection\Types\Integer;
use Tymon\JWTAuth\Contracts\JWTSubject;
use DB;
use App\Movies;
use App\Actor;
use App\User;
use App\movie_user;
use App\Helpers\Crawler;
use App\Helpers\Response;

class User extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'login'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    /**
     * Get user stats
     * @param int $user_id
     * @return array|null
     */
    public function getStats(int $user_id) {
        $stats = null;
        $totalMovies = DB::table('movie_user')->where('user_id', $user_id)->count();

        $totalHours = DB::table('movies')
                        ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
                        ->where('user_id', '=', $user_id)
                        ->sum('movies.duration');

        $actor = DB::table('movies')
        ->select('actors.name', DB::raw('count(movie_actor.movie_id) as total'))
                        ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
                        ->join('movie_actor', 'movies.id', '=', 'movie_actor.movie_id')
                        ->join('actors', 'movie_actor.actor_id', '=', 'actors.id')
                        ->where('user_id', '=', $user_id)
                        ->groupBy('actor_id')
                        ->orderBy('total', 'desc')
                        ->limit(3)
                        ->get();

        $genre = DB::table('movies')
        ->select('genres.name', DB::raw('count(movie_genre.movie_id) as total'))
                        ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
                        ->join('movie_genre', 'movies.id', '=', 'movie_genre.movie_id')
                        ->join('genres', 'movie_genre.genre_id', '=', 'genres.id')
                        ->where('user_id', '=', $user_id)
                        ->groupBy('genres.name')
                        ->orderBy('total', 'desc')
                        ->limit(5)
                        ->get();

        $gross = DB::table('movies')
        ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
        ->where('user_id', '=', $user_id)
        ->orderBy('movies.gross', 'desc')
        ->limit(1)
        ->first();

        $budget = DB::table('movies')
        ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
        ->where('user_id', '=', $user_id)
        ->orderBy('movies.budget', 'desc')
        ->limit(1)
        ->first();
        
        $totalByDay = DB::table('movie_user')
        ->select(DB::raw('DATE(date_added) as date'), DB::raw('count(*) as total'))
        ->where('user_id', '=', $user_id)
        ->groupBy('date')
        ->get();

        $genreChart = null;
        foreach ($genre as $key => $value) {
            $genreChart['data'][] = $value->total;
            $genreChart['labels'][] = $value->name;
        }
        
        $dayChart = null;
        foreach ($totalByDay as $key => $value) {
            $dayChart['data'][] = $value->total;
            $dayChart['labels'][] = $value->date;
        }

        $stats = array(
            'total' => $totalMovies,
            'hours' => $totalHours,
            'top_actors' => $actor,
            'genres' => $genreChart,
            'gross' => $gross,
            'budget' => $budget,
            'day' => $dayChart
        );

        return $stats;
    }

    public function test() {
        $movies = DB::table('movie_user')
            ->where('user_id', 1)->get();

        foreach ($movies as $movie) {
            if ($movie->rating > 0) {
                //DB::table('movie_user')->where('user_id', 1)->update(['rating' => $movie->rating + 1]);
//                var_dump($movie->movie_id);
//                var_dump($movie->rating);
//                var_dump($movie->rating + 1);
            }
        }

        return true;
    }

    /**
     * Delete movie from user list
     * @param Integer $movie_id
     * @param String $type
     * @param Integer $user_id
     * @return array
     */
    public function deleteMovie(Integer $movie_id, String $type, Integer $user_id) {
        $response = new Response();
        
        if ($type == "movie") {
            if (DB::table('movies')->where('id', $movie_id)->exists()) {
                $movie = DB::table('movies')->where('id', $movie_id)->first();
                $delete = DB::table('movie_user')->where('user_id', $user_id)->where('movie_id', $movie->id)->delete();
                if ($delete) {
                    $response->error([false, 'Success']);
                } else {
                    $response->error([true, 'Can\'t delete movie']);
                }

            } else {
                $response->error([true, 'Movie doesn\'t exist']);
            }
        }
        return $response->get();
    }

    /**
     * Add a mark to the movie
     * @param Integer $movie_id
     * @param String $type
     * @param Integer $mark
     * @param Integer $user_id
     * @return array
     */
    public function addMark(Integer $movie_id, String $type, Integer $mark, Integer $user_id) {
        $response = new Response();

        if ($type == "movie") {
            $saved = DB::table('movie_user')
            ->where('movie_id', $movie_id)
            ->where('user_id', $user_id)
            ->update(['rating' => $mark]);
            if ($saved) {
                $response->error([false, 'Mark added']);
            }
        }

        return $response->get();
    }

    public function movies() {
        return $this->belongsToMany('App\Movies', 'movie_user', 'user_id', 'movie_id')->orderBy('position')->withPivot('date_added', 'rating', 'position');
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}