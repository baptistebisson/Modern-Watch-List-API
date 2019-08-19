<?php

namespace App;


use App\Helpers\Image;
use App\Helpers\Response;
use App\Jobs\GetSeasonsJob;
use Illuminate\Support\Facades\Event;
use App\Helpers\Curl;
use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Tv extends Model
{
    protected $table = 'tv';

    protected $fillable = [
        'api_id','title','status','homepage','other_title','duration','first_air_date',
        'rating','backdrop_path','gross','budget','last_air_date','duration','network',
        'country','filming_location','release_date', 'description', 'popular'
    ];

    public function getShow($id, $user_id = null) {
        $util = new Utils();
        $imageInstance = new Image();
        $response = new Response();

        if (!isset($data->status_code)) {
            //Check if movie already exist in the Database
            if (!DB::table('tv')->where('api_id', $id)->exists()) {
                $curl = new Curl;
                $data = $curl->getData('https://api.themoviedb.org/3/tv/' . $id . '?language=en-US&api_key=MOVIE_KEY');

                $title = $data->name ?? null;
                $homepage = $data->homepage ?? null;
                $duration = $data->episode_run_time[0] ?? null;
                $backdrop_path = $data->backdrop_path ?? null;
                $rating = $data->vote_average ?? null;
                $first_air_date = $data->first_air_date ?? null;
                $last_air_date = $data->last_air_date;
                $description = $data->overview ?? null;
                $status = $data->status ?? null;
                $network = $data->networks[0]->name ?? null;
                $country = $data->origin_country[0] ?? null;

                $tv = new self([
                    'api_id' => $data->id,
                    'title' => $title,
                    'other_title' => null,
                    'homepage' => $homepage,
                    'rating' => $rating,
                    'backdrop_path' => $backdrop_path,
                    'description' => $description,
                    'duration' => $duration,
                    'network' => $network,
                    'gross' => null,
                    'budget' => null,
                    'country' => $country,
                    'filming_location' => null,
                    'first_air_date' => $first_air_date,
                    'last_air_date' => $last_air_date,
                    'status', $status
                ]);

                $tv->save();

                // Upload image to host
                $upload = $imageInstance->uploadImage('https://image.tmdb.org/t/p/original'. $data->poster_path, array(
                    'use_filename' => true,
                    'public_id' => $data->id,
                    'folder' => 'tv/p',
                ));

                Log::debug('Tv.php upload image to host', $upload);

                // Upload cover
                $upload = $imageInstance->uploadImage('https://image.tmdb.org/t/p/w1400_and_h450_face'. $data->poster_path, array(
                    'use_filename' => true,
                    'public_id' => $data->id,
                    'folder' => 'tv/c',
                ));

                Log::debug('Tv.php upload cover to host', $upload);

                $position = DB::table('tv_user')->where('user_id', $user_id)->orderBy('position', 'desc')->first();
                if ($position) {
                    $position = $position->position;
                } else {
                    $position = 0;
                }

                // Call job for seasons and episodes
                dispatch(new GetSeasonsJob($tv->id, $user_id, $data->seasons, $data->id));

                // If we have an user id
                if ($user_id !== null) {
                    //Save to pivot table
                    $tvUser = new TvUser([
                        'user_id' => $user_id,
                        'tv_id' => $tv->id,
                        'rating' => 0,
                        'position' => $position + 1,
                    ]);
                    $tvUser->save();
                }

                //Genres
                $util->addGenre($data->genres,'tv', $tv->id);

                $curl = new Curl;

                //Casting
                $cast = $curl->getData('https://api.themoviedb.org/3/tv/' . $tv->api_id . '/credits?api_key=MOVIE_KEY');
                $util->addCasting($cast->cast, 'tv', $tv->id, 15);

                $tmp = 0;
                // Directors
                $util->addDirector($cast->crew, 'tv', $tv->api_id, 5);

                $response->error(false, 'Tv show will be added');
            } else {
                // Show exist in database
                if ($user_id === null) {
                    // When we import popular shows user_id is set to null
                    // We can stop here
                    $response->error(true, 'Tv show already exist');
                } else {
                    // Add existing movie to user list
                    $tv_id = DB::table('tv')->where('api_id', $id)->select('id')->first();

                    // If user doesn't have this movie
                    if (!DB::table('tv_user')->where('user_id', $user_id)->where('tv_id', $tv_id->id)->exists()) {
                        // Get last position
                        $position = DB::table('tv_user')->where('user_id', $user_id)->orderBy('position', 'desc')->first();
                        if ($position) {
                            $position = $position->position;
                        } else {
                            $position = 0;
                        }

                        //Save to pivot table
                        $tvUser = new TvUser([
                            'user_id' => $user_id,
                            'tv_id' => $tv_id->id,
                            'rating' => 0,
                            'position' => $position + 1,
                        ]);
                        $saved = $tvUser->save();
                        Log::debug('Tv.php adding show to user list', $util->toArray($tvUser));
                        //Event::dispatch(new ExampleEvent($movieusers));

                        if ($saved) {
                            $response->error(false, 'Tv show added');
                        }
                    } else {
                        $response->error(true, 'You already have this tv show');
                    }
                }
            }
        } else {
            $response->error(true, 'Database problem');
        }

        return $response->get();
    }

    public function findShow($title) {
        $result = null;
        $curl = new Curl();
        $result = $curl->getData("https://api.themoviedb.org/3/search/tv?api_key=MOVIE_KEY&language=en-US&query=$title&page=1&include_adult=false");

        return $result;
    }

    /**
     * Check if user tv shows is up to date
     * @param  array    $tv    List of tv_id and tv_position
     * @param  int      $user_id   User id
     * @return boolean             Response
     */
    public function upToDate($tv, $user_id) {
        $refresh = false;
        $bdd = DB::table('tv_user')
            ->where('user_id', $user_id)
            ->orderBy('position')
            ->get();

        foreach ($bdd as $key => $value) {
            //For each tv shows in bdd and from request
            //Check if there is a difference
            if (!isset($tv[$key]) || $value->tv_id !== $tv[$key]['id'] || $value->position !== $tv[$key]['position']) {
                $refresh = true;
                break;
            }
        }
        foreach ($tv as $key => $value) {
            //For each tv shows in bdd and from request
            //Check if there is a difference
            if (!isset($bdd[$key]) || $value['id'] !== $bdd[$key]->tv_id || $value['position'] !== $bdd[$key]->position) {
                $refresh = true;
                break;
            }
        }

        return $refresh;
    }

    public function genres() {
        return $this->belongsToMany('App\Genre', 'tv_genre', 'tv_id', 'genre_id');
    }

    public function actors() {
        return $this->belongsToMany('App\Actor', 'tv_actor', 'tv_id', 'actor_id')->withPivot('name');
    }

    public function directors() {
        return $this->belongsToMany('App\Director', 'tv_director', 'tv_id', 'director_id');
    }
}