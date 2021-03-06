<?php

namespace App;

use App\Events\ExampleEvent;
use App\Helpers\Curl;
use App\Helpers\Utils;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use DB;
use App\movie_user;
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

        $men = DB::table('movies')
            ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
            ->join('movie_actor', 'movies.id', '=', 'movie_actor.movie_id')
            ->join('actors', 'movie_actor.actor_id', '=', 'actors.id')
            ->where('user_id', '=', $user_id)
            ->where('gender', 2)->count();

        $woman = DB::table('movies')
            ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
            ->join('movie_actor', 'movies.id', '=', 'movie_actor.movie_id')
            ->join('actors', 'movie_actor.actor_id', '=', 'actors.id')
            ->where('user_id', '=', $user_id)
            ->where('gender', 1)->count();

        $durationMax = DB::table('movies')
            ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
            ->where('user_id', '=', $user_id)
            ->where('duration', '!=', null)
            ->orderBy('duration', 'desc')
            ->limit(1)
            ->first();

        $durationMin = DB::table('movies')
            ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
            ->where('user_id', '=', $user_id)
            ->where('duration', '!=', null)
            ->orderBy('duration', 'asc')
            ->limit(1)
            ->first();

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
            'days' => $dayChart,
            'parity' => [
                'men' => $men,
                'woman' => $woman
            ],
            'duration' => [
                'max' => $durationMax,
                'min' => $durationMin,
            ]
        );

        return $stats;
    }

    public function test() {
        $util = new Utils();
        $actors = DB::table('movies')->get();
        foreach ($actors as $actor) {
            $curl = new Curl;
            $data = $curl->getData('https://api.themoviedb.org/3/movie/' . $actor->imdb_id . '?language=en-US&api_key=MOVIE_KEY');


            $upload = $imageInstance->uploadImage('https://image.tmdb.org/t/p/w1400_and_h450_face'. $data->poster_path, array(                    'folder' => "movie/d",
                'use_filename' => true,
                'public_id' => $actor->imdb_id,
                'folder' => 'movie/c',
            ));
            sleep(1);

            //DB::table('movie')->where('id',  $actor->id)->update(['image_api' => $newName . '.jpg']);
        }
        return true;
    }

    /**
     * Delete movie from user list
     * @param int $movie_id
     * @param string $type
     * @param int $user_id
     * @return array
     */
    public function deleteMovie(int $movie_id, string $type, int $user_id) {
        $response = new Response();
        
        if ($type === "movie") {
            if (DB::table('movies')->where('id', $movie_id)->exists()) {
                $movie = DB::table('movies')->where('id', $movie_id)->first();
                $delete = DB::table('movie_user')->where('user_id', $user_id)->where('movie_id', $movie->id)->delete();
                if ($delete) {
                    Log::debug('User.php delete movie '. $movie_id . ' for user: '. $user_id, array());
                    $response->error(false, 'Success');
                } else {
                    $response->error(true, 'Can\'t delete movie');
                }

            } else {
                $response->error(true, 'Movie doesn\'t exist');
            }
        }
        return $response->get();
    }


    /**
     * Add a mark to the movie
     * @param int $movie_id
     * @param string  $type
     * @param int $mark
     * @param int $user_id
     * @return array
     */
    public function addMark(int $movie_id, string $type, int $mark, int $user_id) {
        $response = new Response();

        if ($type === 'movie') {
            $saved = DB::table('movie_user')
            ->where('movie_id', $movie_id)
            ->where('user_id', $user_id)
            ->update(['rating' => $mark]);
            if ($saved) {
                Log::debug('User.php add mark to movie '. $movie_id . ' for user: '. $user_id, array());
                $response->error(false, 'Mark added');
            }
        }

        return $response->get();
    }

    public function addToList(int $movie_id, string $type, int $user_id) {
        $response = new Response();
        $util = new Utils();

        if ($type === 'movie') {
            $position = DB::table('movie_user')->where('user_id', $user_id)->orderBy('position', 'desc')->first();
            if ($position) {
                $position = $position->position;
            } else {
                $position = 0;
            }

            $movieUsers = new Movieusers([
                'user_id' => $user_id,
                'movie_id' => $movie_id,
                'date_added' => date('Y/m/d-H:i:s'),
                'rating' => 0,
                'position' => $position + 1,
            ]);
            $saved = $movieUsers->save();
            if ($saved) {
                Log::debug('Movie.php adding movie to user list',$util->toArray($movieUsers));
                Event::dispatch(new ExampleEvent($movieUsers));
                $response->error(false, 'Movie added');
            } else {
                Log::debug('Movie.php error while adding movie to user list', $util->toArray($movieUsers));
            }
        }

        return $response->get();
    }

    public function movies() {
        return $this->belongsToMany('App\Movie', 'movie_user', 'user_id', 'movie_id')
            ->orderBy('position', 'desc')
            ->withPivot('date_added', 'rating', 'position');
    }

    public function shows() {
        return $this->belongsToMany('App\Tv', 'tv_user', 'user_id', 'tv_id')
            ->orderBy('position', 'desc')
            ->withPivot('created_at', 'rating', 'position');
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
