<?php

namespace App;

use App\Helpers\Image;
use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Helpers\Curl;
use Illuminate\Support\Facades\Log;
use App\Helpers\Response;

class Actor extends Model
{

    protected $fillable = ['imdb_id','api_id','name','biography','place_of_birth',
    'popularity','height','birth_date','death_date','gender','image_original', 'image_small', 'image_api'];
    public $timestamps = false;

    public static function importActor($actorData)
    {
        $util = new Utils();
        $imageInstance = new Image();
        $curl = curl_init();
        $url = 'http://www.imdb.com/name/'. $actorData->imdb_id .'/?ref_=tt_ov_st_sm';
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //Only english page
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept-Language: en']);

        $result = curl_exec($curl);
        curl_close($curl);

        preg_match('/Height:<\/h4>.*\n.*\((\d,\d+)/i', $result, $match);
        $height = $match[1] ?? 0;

        $birthday = $actorData->birthday ?? null;
        if ($birthday === 0) {
            $birthday = null;
        }
        $deathday = $actorData->deathday ?? null;
        if ($deathday === 0) {
            $deathday = null;
        }
        $place_of_birth = $actorData->place_of_birth ?? null;
        $biography = $actorData->biography ?? null;
        $popularity = $actorData->popularity ?? null;

        // Format name for file
        $name_lower = $util->normalizeString($actorData->name);

        if ($actorData->profile_path === null) {
            $image_api = 'no_picture.jpg';
        } else {
            // Upload image to host
            $upload = $imageInstance->uploadImage('https://image.tmdb.org/t/p/original'. $actorData->profile_path, array(
                'use_filename' => true,
                'public_id' => $actorData->imdb_id,
                'folder' => 'movie/a',
                ));

            $image_api = $actorData->imdb_id . '.jpg';

            Log::debug('Actor.php upload image to host', $upload);
        }

        $actor = new self([
            'imdb_id' => $actorData->imdb_id,
            'api_id' => $actorData->id,
            'name' => $actorData->name,
            'biography' => $biography,
            'image_original' => null,
            'image_small' => null,
            'image_api' => $image_api,
            'place_of_birth' => $place_of_birth,
            'popularity' => $popularity,
            'height' => $height,
            'birth_date' => $birthday,
            'death_date' => $deathday,
            'gender' => $actorData->gender,
        ]);

        $actor->save();

        return $actor;
    }

    /**
     * Get details of actor
     * @param $id
     * @return string
     */
    public function getDetails($id) {
        $return = null;
        $actor = DB::table('actors')->where('id', $id)->first();

        $otherMovies = DB::table('actors')
            ->join('movie_actor', 'actors.id', '=', 'movie_actor.actor_id')
            ->join('movies', 'movie_actor.movie_id', '=', 'movies.id')
            ->where('actors.id', $id)
            ->select('movies.id', 'movies.title')
            ->get();

        $budget = DB::table('actors')
            ->join('movie_actor', 'actors.id', '=', 'movie_actor.actor_id')
            ->join('movies', 'movie_actor.movie_id', '=', 'movies.id')
            ->where('actors.id', $id)
            ->select('movies.id', 'movies.title', 'movies.budget')
            ->orderBy('movies.budget', 'desc')
            ->limit(1)
            ->first();

        $gross = DB::table('actors')
            ->join('movie_actor', 'actors.id', '=', 'movie_actor.actor_id')
            ->join('movies', 'movie_actor.movie_id', '=', 'movies.id')
            ->where('actors.id', $id)
            ->select('movies.id', 'movies.title', 'movies.gross')
            ->orderBy('movies.gross', 'desc')
            ->limit(1)
            ->first();

        $return['db_movies'] = $otherMovies;
        $return['biggest_budget'] = $budget;
        $return['biggest_gross'] = $gross;
        $return['details'] = $actor;
        return json_encode($return);
    }


    /**
     * Get movie credits
     * @param $id
     * @return Helpers\JSON|null
     */
    public function getMovieCredits($id) {
        $result = null;

        if  (HistoryQueries::where('type_id', 'a'.$id)->exists()) {
            $data = HistoryQueries::where('type_id', 'a'.$id)->first();

            // Check if we need to update data
            if (strtotime($data->updated_at) < strtotime('-1 week')) {
                $curl = new Curl();
                $result = $curl->getData("https://api.themoviedb.org/3/person/$id/movie_credits?api_key=MOVIE_KEY&language=en-US");

                // Update data
                $data->query = $result;
                $data->save();

                Log::debug('Class Actor caching data', array($result));

                $result = json_encode($result);
            } else {
                $result = $data->query;
            }
        } else {
            $curl = new Curl();
            $result = $curl->getData("https://api.themoviedb.org/3/person/$id/movie_credits?api_key=MOVIE_KEY&language=en-US");
            $result = json_encode($result);

            $history = new HistoryQueries([
                'type_id' => 'a'.$id,
                'query' => $result,
            ]);

            $history->save();
        }

        return $result;
    }

    public function movies() {
        return $this->belongsToMany('App\Movie', 'movie_actor', 'actor_id', 'movie_id');
    }
}
