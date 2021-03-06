<?php

namespace App;

use App\Events\ExampleEvent;
use App\Events\MoviedetailsEvent;
use App\Helpers\Crawler;
use App\Helpers\Image;
use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Curl;
use DB;
use App\Helpers\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class Movie extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','imdb_id','api_id','title','french_title','year','image','duration',
        'category','rating','description','gross','budget','country','filming_location','release_date',
        'image_original', 'image_small', 'image_api', 'backdrop_path',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function movie_user() {
        return $this->belongsToMany('App\User', 'movie_id');
    }

    public function getMovie($id, $user_id = null) {
        $util = new Utils();
        $imageInstance = new Image();
        $response = new Response();

        if (strpos($id, 'tt') === 0) {
            $column = 'imdb_id';
        } else {
            $column = 'api_id';
        }
        
        if (!isset($data->status_code)) {
            //Check if movie already exist in the Database
            if (!DB::table('movies')->where($column, $id)->exists()) {
                // dispatch(new MovieJob($user_id, $id));
                $curl = new Curl;
                $data = $curl->getData('https://api.themoviedb.org/3/movie/' . $id . '?language=en-US&api_key=MOVIE_KEY');

                $title = $data->title ?? null;

                $imdb_id = $data->imdb_id ?? null;

                $duration = $data->runtime ?? null;

                $rating = $data->vote_average ?? null;

                $country = $data->production_countries[0]->name ?? null;

                $release_date = $data->release_date ?? null;

                $description = $data->overview ?? null;

                $gross = $data->revenue ?? null;

                $budget = $data->budget ?? null;

                $backdrop_path = null;
                if ($data->backdrop_path !== null) {
                    $backdrop_path = str_replace(' ', '_', $data->title). '.jpg';
                }

                // Format name for file
                $name_lower = $util->normalizeString($data->title);

                $movie = new self([
                    'imdb_id' => $imdb_id,
                    'api_id' => $data->id,
                    'title' => $title,
                    'other_title' => null,
                    'duration' => $duration,
                    'rating' => $rating,
                    'backdrop_path' => $backdrop_path,
                    'image_original' => $name_lower. '.jpg',
                    'image_small' => $name_lower. '_small.jpg',
                    'image_api' => $name_lower. '.jpg',
                    'description' => $description,
                    'gross' => $gross,
                    'budget' => $budget,
                    'country' => $country,
                    'filming_location' => null,
                    'release_date' => $release_date,
                ]);

                $movie->save();

                $this->getMoreDetails($movie->id);

                // Upload image to host
                $upload = $imageInstance->uploadImage('https://image.tmdb.org/t/p/original'. $data->poster_path, array(
                    'use_filename' => true,
                    'public_id' => $name_lower,
                    'folder' => 'movie/p',
                ));

                Log::debug('Movie.php upload image to host', $upload);

                // Upload cover
                $upload = $imageInstance->uploadImage('https://image.tmdb.org/t/p/w1400_and_h450_face'. $data->poster_path, array(
                    'use_filename' => true,
                    'public_id' => $name_lower,
                    'folder' => 'movie/c',
                ));

                $position = DB::table('movie_user')->where('user_id', $user_id)->orderBy('position', 'desc')->first();
                if ($position) {
                    $position = $position->position;
                } else {
                    $position = 0;
                }

                // If we have an user id
                if ($user_id !== null) {
                    //Save to pivot table
                    $movieUser = new Movieusers([
                        'user_id' => $user_id,
                        'movie_id' => $movie->id,
                        'date_added' => date('Y/m/d-H:i:s'),
                        'rating' => 0,
                        'position' => $position + 1,
                    ]);
                    $movieUser->save();
                }

                //Genres
                $util->addGenre($data->genres, 'movie', $movie->id);

                $curl = new Curl;

                // Casting
                $cast = $curl->getData('https://api.themoviedb.org/3/movie/' . $movie->imdb_id . '/credits?api_key=MOVIE_KEY');
                $util->addCasting($cast->cast, 'movie', $movie->id, 5);

                // Directors
                $util->addDirector($cast->crew, 'movie', $movie->imdb_id, 5);

                $response->error(false, 'Movie will be added');
            } else {
                // Movie exist in database
                if ($user_id === null) {
                    // When we import popular movie user_id is set to null
                    // We can stop here
                    $response->error(true, 'Movie already exist');
                } else {
                    // Add existing movie to user list
                    $movie_id = DB::table('movies')->where('api_id', $id)->select('id')->first();

                    // If user doesn't have this movie
                    if (!DB::table('movie_user')->where('user_id', $user_id)->where('movie_id', $movie_id->id)->exists()) {
                        // Get last position
                        $position = DB::table('movie_user')->where('user_id', $user_id)->orderBy('position', 'desc')->first();
                        if ($position) {
                            $position = $position->position;
                        } else {
                            $position = 0;
                        }

                        //Save to pivot table
                        $movieUser = new Movieusers([
                            'user_id' => $user_id,
                            'movie_id' => $movie_id->id,
                            'date_added' => date('Y/m/d-H:i:s'),
                            'rating' => 0,
                            'position' => $position + 1,
                        ]);
                        $saved = $movieUser->save();
                        Log::debug('Movie.php adding movie to user list', $util->toArray($movieusers));
                        Event::dispatch(new ExampleEvent($movieUser));

                        if ($saved) {
                            $response->error(false, 'Movie added');
                        }
                    } else {
                        $response->error(true, 'You already have this movie');
                    }
                }
            }
        } else {
            $response->error(true, 'Database problem');
        }

        return $response->get();
    }

    public function findMovie($movie_title) {
        $result = null;
        $curl = new Curl();
        $result = $curl->getData("https://api.themoviedb.org/3/search/movie?api_key=MOVIE_KEY&language=en-US&query=$movie_title&page=1&include_adult=false");

        return $result;
    }

    /**
     * Move movie to desired position
     * @param  array  $movies   List of movie_id and movie_position
     * @param  int    $user_id  User id
     * @return array            Message
     */
    public function moveMovie($movies, $user_id) {
        $response = new Response();
        $response->error(false, 'Position updated');

        foreach ($movies as $key => $value) {
            DB::table('movie_user')
            ->where('user_id', $user_id)
            ->where('movie_id', $value['id'])
            ->update(['position' => $value['position']]);
        }
        
        return $response->get();
    }

    /**
     * Check if user movies is up to date
     * @param  array    $movies    List of movie_id and movie_position
     * @param  int      $user_id   User id
     * @return boolean             Response
     */
    public function upToDate($movies, $user_id) {
        $refresh = false;
        $bdd = DB::table('movie_user')
        ->where('user_id', $user_id)
        ->orderBy('position')
        ->get();

         foreach ($bdd as $key => $value) {
             //For each movies in bdd and from request
             //Check if there is a difference
             if (!isset($movies[$key]) || $value->movie_id !== $movies[$key]['id'] || $value->position !== $movies[$key]['position']) {
                 $refresh = true;
                 break;
             }
         }
        foreach ($movies as $key => $value) {
            //For each movies in bdd and from request
            //Check if there is a difference
            if (!isset($bdd[$key]) || $value['id'] !== $bdd[$key]->movie_id || $value['position'] !== $bdd[$key]->position) {
                $refresh = true;
                break;
            }
        }

//        $refresh = false;
//
//        $last_date = DB::table('users')->where('id', $user_id)->first();
//        $last_date = $last_date->updated_at;
//
//        // If last date different from user cookie, refresh
//        if ($last_date !== $date) {
//            $refresh = true;
//        }
//
//        return $refresh;

        return $refresh;
    }

    /**
     * Import popular movies into database
     */
    public function importPopularMovies() {
        $total = 0;
        $u = new Utils();
        $u->timeInit();

        $c = new Crawler('https://www.imdb.com/chart/moviemeter?ref_=nv_mv_mpm_8');
        $match = $c->find('/<tr>(.*?)<\/tr>/sm', true);

        // Reset column popular
        DB::table('movies')->where('popular', 1)->update(['popular', 0]);

        foreach ($match[0] as $key => $value) {
            $id = $c->findIn($value, '(tt\d+)', false);
            if (\count($id) > 0 && strpos($id[0], 'tt') === 0) {
                // Check if movie already exist
                if (DB::table('movies')->where('imdb_id', $id[0])->exist()) {
                    // Update column
                    DB::table('movies')->where('imdb_id', $id[0])->update(['popular', 1]);
                } else {
                    // Create movie
                    $insert = Movie::getMovie($id[0]);
                    if ($insert['error'] === false) {
                        $total++;
                    }
                    Log::debug('Popular Movie import movie', $insert);
                    // Avoid too many request at the same time
                    sleep(2);
                }
            }
        }
        Log::debug('Popular Movie execution time : ' . $u->timeGet());
        Log::debug('Popular Movie total imported : ' . $total);
    }

    /**
     * Get more details about the movie
     * @param int $id
     * @return array
     */
    public function getMoreDetails(int $id) {
        $response = new Response();
        $curl = new Curl();
        // Check if we already have more details
        $movie = DB::table('movies')->where('id', $id)->first();
        if ($movie->other_title === null) {

            $data = $curl->getData('https://www.imdb.com/title/'. $movie->imdb_id .'/?ref_=ttfc_fc_tt');

            preg_match('/Also Known As.*> (.*)/', $data, $match);
            $other_title = $match[1] ?? null;

            preg_match('/Filming Locations.*\n.*\n.*url\'>(.*)</', $data, $match);
            $filming_location = $match[1] ?? null;

            if ($other_title !== null && $filming_location !== null) {
                DB::table('movies')->where('id', $id)->update([
                    'other_title' => $other_title,
                    'filming_location' => $filming_location,
                ]);
                $response->error(false, 'Details added');
            } else {
                $response->error(true, 'No details');
            }
        } else {
            $response->error(true, 'No more details');
        }

        return $response->get();
    }

    /**
     * Get popular movies
     * @return mixed
     */
    public function getPopularMovies() {
        $movies = DB::table('movies')->where('popular', 1)->get();
        return $movies;
    }


    public function genres() {
        return $this->belongsToMany('App\Genre', 'movie_genre', 'movie_id', 'genre_id');
    }

    public function actors() {
        return $this->belongsToMany('App\Actor', 'movie_actor', 'movie_id', 'actor_id')->withPivot('name');
    }

    public function directors() {
        return $this->belongsToMany('App\Director', 'movie_director', 'movie_id', 'director_id');
    }

//    protected $dispatchesEvents = [
//        'saving' => MoviedetailsEvent::class,
//    ];
}
