<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Curl;
use App\MovieActor;
use App\Movies;
use App\Genre;
use App\Director;
use App\Actor;
use App\MovieGenre;
use App\MovieDirector;
use App\Movieusers;
use DB;

class MovieJob extends Job
{
    protected $user_id;
    protected $id;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id, $id)
    {
        $this->user_id = $user_id;
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $curl = new Curl;
        $data = $curl->getData("https://api.themoviedb.org/3/movie/". $this->id ."?language=en-US&api_key=MOVIE_KEY");
        $curlTMP = curl_init();
        $url = 'http://www.imdb.com/title/'. $data->imdb_id .'/?ref_=ttfc_fc_tt';
        curl_setopt($curlTMP, CURLOPT_URL, $url);
        curl_setopt($curlTMP, CURLOPT_RETURNTRANSFER, true);
        //Only english page
        curl_setopt($curlTMP, CURLOPT_HTTPHEADER, ['Accept-Language: en']);
        $result = curl_exec($curlTMP);
        curl_close($curlTMP);

        $title = isset($data->original_title) ? $data->original_title : null;

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
            $backdrop_path = str_replace(" ", "_", $data->original_title). '.jpg';
        }

        $movie = new Movies([
            'imdb_id' => $imdb_id,
            'api_id' => $data->id,
            'title' => $title,
            'other_title' => $french_title,
            'duration' => $duration,
            'rating' => $rating,
            'backdrop_path' => $backdrop_path,
            'image_original' => str_replace(" ", "_", $data->original_title). '.jpg',
            'image_small' => str_replace(" ", "_", $data->original_title). '_small.jpg',
            'description' => $description,
            'gross' => $gross,
            'budget' => $budget,
            'country' => $country,
            'filming_location' => $filming_location,
            'release_date' => $release_date,
        ]);

        $saved = $movie->save();

        //Check if image already exist
        if (!file_exists('/var/www/api/public/img/'. str_replace(" ", "_", $data->original_title). '.jpg')) {
            $this->save_image('https://image.tmdb.org/t/p/w185'. $data->poster_path,
                '/var/www/api/public/img/'. str_replace(" ", "_", $data->original_title). '_small.jpg');

            $this->save_image('https://image.tmdb.org/t/p/original'. $data->poster_path,
                '/var/www/api/public/img/'. str_replace(" ", "_", $data->original_title). '.jpg');

            if ($backdrop_path !== null) {
                $this->save_image('https://image.tmdb.org/t/p/w1400_and_h450_face'. $data->backdrop_path,
                    '/var/www/api/public/img/b/'. str_replace(" ", "_", $data->original_title). '.jpg');
            }
        }


        $position = DB::table('movie_user')->where('user_id', $this->user_id)->orderBy('position', 'desc')->first();
        if ($position) {
            $position = $position->position;
        } else {
            $position = 0;
        }
        //Save to pivot table
        $movieusers = new Movieusers([
            'user_id' => $this->user_id,
            'movie_id' => $movie->id,
            'date_added' => date("Y/m/d-H:i:s"),
            'rating' => 0,
            'position' => $position + 1,
        ]);
        $saved = $movieusers->save();

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
                    $actor = $actor->getActor($actorData);
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
                        $director = $director->getDirector($directorData);
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
    }
    
    private function save_image($img, $fullpath) {
        $write = null;
        $ch = curl_init ($img);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $rawdata = curl_exec($ch);
        curl_close ($ch);
        if (!file_exists($fullpath)) {
            $fp = fopen($fullpath,'x');
            $write = fwrite($fp, $rawdata);
            fclose($fp);
    	}
        if ($write !== null) {
            $write = 1;
        }
        return $write;
    }
}
