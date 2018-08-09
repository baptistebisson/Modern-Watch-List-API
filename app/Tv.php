<?php

namespace App;


use Illuminate\Support\Facades\Event;
use App\Helpers\Curl;
use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TvUser;

class Tv extends Model
{
    protected $fillable = [
        'api_id','title','homepage','other_title','duration','rating','backdrop_path','gross','budget',
        'country','filming_location','release_date', 'description', 'popular'
    ];

    public function getShow($id, $user_id = null) {
        $util = new Utils();
        $response = new Response();

        if (!isset($data->status_code)) {
            //Check if movie already exist in the Database
            if (!DB::table('tv')->where('api_id', $id)->exists()) {
                $curl = new Curl;
                $data = $curl->getData('https://api.themoviedb.org/3/tv/' . $id . '?language=en-US&api_key=MOVIE_KEY');

                $title = $data->title ?? null;
                $homepage = $data->homepage ?? null;
                $backdrop_path = $data->backdrop_path ?? null;
                $rating = $data->vote_average ?? null;
                $first_air_date = $data->first_air_date ?? null;
                $last_air_date = $data->last_air_date;
                $description = $data->overview ?? null;

                $tv = new Tv([
                    'api_id' => $data->id,
                    'title' => $title,
                    'other_title' => null,
                    'homepage' => $homepage,
                    'rating' => $rating,
                    'backdrop_path' => $backdrop_path,
                    'description' => $description,
                    'gross' => null,
                    'budget' => null,
                    'country' => null,
                    'filming_location' => null,
                    'first_air_date' => $first_air_date,
                    'last_air_date' => $last_air_date
                ]);

                $tv->save();

                // Upload image to host
                $upload = $util->upload_image('https://image.tmdb.org/t/p/original'. $data->poster_path, array(                    'folder' => "movie/d",
                    'use_filename' => true,
                    'public_id' => $data->id,
                    'folder' => 'tv/p',
                ));

                Log::debug('Tv.php upload image to host', $upload);

                // Upload cover
                $upload = $util->upload_image('https://image.tmdb.org/t/p/w1400_and_h450_face'. $data->poster_path, array(                    'folder' => "movie/d",
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
                    $tvGenre = new TvGenre([
                        'genre_id' => $genre->id,
                        'tv_id' => $tv->id,
                    ]);
                    $tvGenre->save();
                }

                $curl = new Curl;

                //Casting
                $tmp = 0;
                $cast = $curl->getData('https://api.themoviedb.org/3/tv/' . $tv->imdb_id . '/credits?api_key=MOVIE_KEY');
                //Foreach person into cast array
                foreach ($cast->cast as $key => $value) {
                    //We only want 5 first peoples
                    if ($tmp < 5) {
                        $curl = new Curl;

                        //If actor doesn't exist
                        if (!DB::table('actors')->where('name', $value->name)->exists() && !DB::table('actors')->where('api_id', $value->id)->exists()) {
                            $actorData = $curl->getData('https://api.themoviedb.org/3/person/' . $value->id . '?api_key=MOVIE_KEY&language=en-US');
                            $actor = new Actor();
                            $actor = $actor->importActor($actorData);
                        } else {
                            $actor = DB::table('actors')->where('name', $value->name)->first();
                        }

                        $tvActor = new MovieActor([
                            'actor_id' => $actor->id,
                            'tv_id' => $tv->id,
                            'name' => $value->character,
                        ]);
                        $tvActor->save();
                        $tmp++;
                    } else {
                        //Stop iteration if reach 5
                        break;
                    }
                }

                $tmp = 0;
                //Get directors
                foreach ($cast->crew as $key => $value) {
                    if ($value->job === 'Producer') {
                        if ($tmp < 5) {
                            $curl = new Curl;
                            if (!DB::table('directors')->where('name', $value->name)->exists()) {
                                $directorData = $curl->getData('https://api.themoviedb.org/3/person/' . $value->id . '?api_key=MOVIE_KEY&language=en-US');
                                $director = new Director();
                                $director = $director->importDirector($directorData);
                            } else {
                                $director = DB::table('directors')->where('name', $value->name)->first();
                            }
                            $tvDirector = new TvDirector([
                                'director_id' => $director->id,
                                'tv_id' => $tv->id,
                            ]);
                            $tvDirector->save();
                        }
                    }
                }
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
                            'movie_id' => $tv_id->id,
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
}