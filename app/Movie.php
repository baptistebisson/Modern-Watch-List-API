<?php

namespace App;

use App\Helpers\Crawler;
use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Curl;
use DB;
use App\Helpers\Response;
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
        'image_original', 'image_small', 'backdrop_path',
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
        $response = new Response();

        if (substr($id, 0, 2) == "tt") {
            $column = 'imdb_id';
        } else {
            $column = 'api_id';
        }
        
        if (!isset($data->status_code)) {
            //Check if movie already exist in the Database
            if (!DB::table('movies')->where($column, $id)->exists()) {
                // dispatch(new MovieJob($user_id, $id));
                $curl = new Curl;
                $data = $curl->getData("https://api.themoviedb.org/3/movie/". $id ."?language=en-US&api_key=MOVIE_KEY");
                $curlTMP = curl_init();
                $url = 'http://www.imdb.com/title/'. $data->imdb_id .'/?ref_=ttfc_fc_tt';
                curl_setopt($curlTMP, CURLOPT_URL, $url);
                curl_setopt($curlTMP, CURLOPT_RETURNTRANSFER, true);
                //Only english page
                curl_setopt($curlTMP, CURLOPT_HTTPHEADER, ['Accept-Language: en']);
                $result = curl_exec($curlTMP);
                curl_close($curlTMP);

                $title = isset($data->title) ? $data->title : null;

                preg_match('/Also Known As.*> (.*)/', $result, $match);
                $french_title = isset($match[1]) ? $match[1] : null;

                preg_match('/Filming Locations.*\n.*\n.*url\'>(.*)</', $result, $match);
                $filming_location = isset($match[1]) ? $match[1] : null;

                $imdb_id = isset($data->imdb_id) ? $data->imdb_id : null;

                $duration = isset($data->runtime) ? $data->runtime : null;

                $rating = isset($data->vote_average) ? $data->vote_average : null;

                $country = isset($data->production_countries[0]->name) ? $data->production_countries[0]->name : null;

                $release_date = isset($data->release_date) ? $data->release_date : null;

                $description = isset($data->overview) ? $data->overview : null;

                $gross = isset($data->revenue) ? $data->revenue : null;

                $budget = isset($data->budget) ? $data->budget : null;

                $backdrop_path = null;
                if ($data->backdrop_path !== null) {
                    $backdrop_path = str_replace(" ", "_", $data->title). '.jpg';
                }

                $movie = new Movie([
                    'imdb_id' => $imdb_id,
                    'api_id' => $data->id,
                    'title' => $title,
                    'other_title' => $french_title,
                    'duration' => $duration,
                    'rating' => $rating,
                    'backdrop_path' => $backdrop_path,
                    'image_original' => str_replace(" ", "_", $data->title). '.jpg',
                    'image_small' => str_replace(" ", "_", $data->title). '_small.jpg',
                    'description' => $description,
                    'gross' => $gross,
                    'budget' => $budget,
                    'country' => $country,
                    'filming_location' => $filming_location,
                    'release_date' => $release_date,
                ]);

                $saved = $movie->save();

                //Check if image already exist
                if (!file_exists('/var/www/api/public/img/'. str_replace(" ", "_", $data->title). '.jpg')) {
                    $util->save_image('https://image.tmdb.org/t/p/w185'. $data->poster_path,
                        '/var/www/api/public/img/'. str_replace(" ", "_", $data->title). '_small.jpg');

                    $util->save_image('https://image.tmdb.org/t/p/original'. $data->poster_path,
                        '/var/www/api/public/img/'. str_replace(" ", "_", $data->title). '.jpg');

                    if ($backdrop_path !== null) {
                        $util->save_image('https://image.tmdb.org/t/p/w1400_and_h450_face'. $data->backdrop_path,
                            '/var/www/api/public/img/b/'. str_replace(" ", "_", $data->title). '.jpg');
                    }
                }


                $position = DB::table('movie_user')->where('user_id', $user_id)->orderBy('position', 'desc')->first();
                if ($position) {
                    $position = $position->position;
                } else {
                    $position = 0;
                }

                // If we have an user id
                if ($user_id !== null) {
                    //Save to pivot table
                    $movieusers = new Movieusers([
                        'user_id' => $user_id,
                        'movie_id' => $movie->id,
                        'date_added' => date("Y/m/d-H:i:s"),
                        'rating' => 0,
                        'position' => $position + 1,
                    ]);
                    $saved = $movieusers->save();
                }

                //Genres
                foreach ($data->genres as $key => $value) {
                    //Check if genre exist
                    if (!DB::table('genres')->where('name', $value->name)->exists()) {
                        $genre = new Genre();
                        $genre->name = $value->name;
                        $genre->save();
                    } else {
                        //If exist we need to get it
                        $genre = DB::table('genres')->where('name', $value->name)->first();
                    }
                    $moviesgenres = new MovieGenre([
                        'genre_id' => $genre->id,
                        'movie_id' => $movie->id,
                    ]);
                    $moviesgenres->save();
                }

                $curl = new Curl;

                //Casting
                $tmp = 0;
                $cast = $curl->getData("https://api.themoviedb.org/3/movie/". $movie->imdb_id ."/credits?api_key=MOVIE_KEY");
                //Foreach person into cast array
                foreach ($cast->cast as $key => $value) {
                    //We only want 5 first peoples
                    if ($tmp < 5) {
                        $curl = new Curl;

                        //If actor doesn't exist
                        if (!DB::table('actors')->where('name', $value->name)->exists() && !DB::table('actors')->where('api_id', $value->id)->exists()) {
                            $actorData = $curl->getData("https://api.themoviedb.org/3/person/". $value->id ."?api_key=MOVIE_KEY&language=en-US");
                            $actor = new Actor();
                            $actor = $actor->importActor($actorData);
                        } else {
                            $actor = DB::table('actors')->where('name', $value->name)->first();
                        }

                        $moviesactors = new MovieActor([
                            'actor_id' => $actor->id,
                            'movie_id' => $movie->id,
                            'name' => $value->character,
                        ]);
                        $moviesactors->save();
                        $tmp++;
                    } else {
                        //Stop iteration if reach 5
                        break;
                    }
                }

                $tmp = 0;
                //Get directors
                foreach ($cast->crew as $key => $value) {
                    if ($value->job == "Producer") {
                        if ($tmp < 5) {
                            $curl = new Curl;
                            if (!DB::table('directors')->where('name', $value->name)->exists()) {
                                $directorData = $curl->getData("https://api.themoviedb.org/3/person/". $value->id ."?api_key=MOVIE_KEY&language=en-US");
                                $director = new Director();
                                $director = $director->importDirector($directorData);
                            } else {
                                $director = DB::table('directors')->where('name', $value->name)->first();
                            }
                            $moviesdirectors = new MovieDirector([
                                'director_id' => $director->id,
                                'movie_id' => $movie->id,
                            ]);
                            $moviesdirectors->save();
                        }
                    }
                }
                $response->error([false, 'Movie will be added']);
            } else {
                if ($user_id == null) {
                    $response->error([true, 'Movie already exist']);
                } else {
                    $movie_id = DB::table('movies')->where('api_id', $id)->select('id')->first();
                    if (!DB::table('movie_user')->where('user_id', $user_id)->where('movie_id', $movie_id->id)->exists()) {
                        $position = DB::table('movie_user')->where('user_id', $user_id)->orderBy('position', 'desc')->first();
                        if ($position) {
                            $position = $position->position;
                        } else {
                            $position = 0;
                        }

                        //Save to pivot table
                        $movieusers = new Movieusers([
                            'user_id' => $user_id,
                            'movie_id' => $movie_id->id,
                            'date_added' => date("Y/m/d-H:i:s"),
                            'rating' => 0,
                            'position' => $position + 1,
                        ]);
                        $saved = $movieusers->save();
                        if ($saved) {
                            $response->error([false, 'Movie added']);
                        }
                    } else {
                        $response->error([true, 'You already have this movie']);
                    }
                }
            }
        } else {
            $response->error([true, 'Database problem']);
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
        $return = array(
            'error' => false,
            'message' => 'Position updated',
        );

        foreach ($movies as $key => $value) {
            $update = DB::table('movie_user')
            ->where('user_id', $user_id)
            ->where('movie_id', $value['id'])
            ->update(['position' => $value['position']]);
        }
        
        return $return;
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

        return $refresh;
    }

    /**
     * Import popular movies into database
     */
    public function importPopularMovies() {
        $total = 0;
        $u = new Utils();
        $u->timeInit();

        $c = new Crawler("https://www.imdb.com/chart/moviemeter?ref_=nv_mv_mpm_8");
        $match = $c->find('/<tr>(.*?)<\/tr>/sm', true);

        // Reset column popular
        DB::table('movies')->where('popular', 1)->update(['popular', 0]);

        foreach ($match[0] as $key => $value) {
            $id = $c->findIn($value, '(tt\d+)', false);
            if (sizeof($id) > 0 && substr($id[0], 0, 2) === "tt") {
                // Check if movie already exist
                if (DB::table('movies')->where('imdb_id', $id[0])->exist()) {
                    // Update column
                    DB::table('movies')->where('imdb_id', $id[0])->update(['popular', 1]);
                } else {
                    // Create movie
                    $insert = Movie::getMovie($id[0]);
                    if ($insert['error'] == false) {
                        $total++;
                    }
                    Log::debug("Popular Movie import movie", $insert);
                    // Avoid too many request at the same time
                    sleep(2);
                }
            }
        }
        Log::debug("Popular Movie execution time : " . $u->timeGet());
        Log::debug("Popular Movie total imported : " . $total);
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
}
