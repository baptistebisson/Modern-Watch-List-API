<?php

namespace App\Helpers;

use App\Actor;
use App\Director;
use App\Genre;
use App\MovieActor;
use App\MovieDirector;
use App\MovieGenre;
use App\TvActor;
use App\TvDirector;
use App\TvGenre;
use Cloudinary\Api\GeneralError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class Utils
{
    protected $time;
    protected $executionStartTime;

    public function __construct()
    {

    }

    /**
     * Init time
     */
    public function timeInit()
    {
        $this->executionStartTime = microtime(true);
    }

    /**
     * Get function duration
     * @return string
     */
    public function timeGet()
    {
        $executionEndTime = microtime(true);
        $seconds = $executionEndTime - $this->executionStartTime;
        return number_format($seconds,3) . 's';
    }

    /**
     * Get user id from request token
     * @param Request $request
     * @return mixed
     */
    public function getUserId(Request $request)
    {
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        return $user->id;
    }

    /**
     * Save image into server
     * @param $img
     * @param $fullpath
     * @return bool|int|null
     */
    public function save_image($img, $fullpath) {
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

    /**
     * Correct public id of image
     */
    public function rename_api_images(): void
    {
        $api = new \Cloudinary\Api();
        try {
            $result = $api->resources([
                'type' => 'upload',
                'prefix' => 'movie/a',
            ]);
        } catch (GeneralError $e) {

        }

        while ($result !== null && array_key_exists('next_cursor', $result)) {
            foreach ($result['resources'] as $resource) {
                if (preg_match('/.jpg/', $resource['public_id'])) {
                    $new = str_replace('.jpg', '', $resource['public_id']);
                    \Cloudinary\Uploader::rename($resource['public_id'], $new);
                    //var_dump($result);
                }
            }

            try {

                $result = $api->resources([
                    'type' => 'upload',
                    'prefix' => 'movie/a',
                    'next_cursor' => $result['next_cursor'],
                ]);
            } catch (GeneralError $e) {
            }
        }
    }

    /**
     * Get more details of a person
     * @param string $table
     * @param int    $id
     * @return array
     */
    public function getPersonMoreDetails(string $table, int $id): array
    {
        $response = new Response();
        $curl = new Curl();
        // Check if we already have more details
        $actor = DB::table($table)->where('id', $id)->first();
        if ($actor->height === null) {
            $data = $curl->getData('https://www.imdb.com/name/'. $actor->imdb_id .'/?ref_=tt_ov_st_sm');
            preg_match('/Height:<\/h4>.*\n.*\((\d.\d+)/i', $data, $match);
            $height = $match[1] ?? null;

            if ($height !== null) {
                DB::table($table)->where('id', $id)->update(['height' => $height .'m']);
                $response->error(false, 'Details added');
            } else {
                $response->error(true, 'No details');
            }
        } else {
            $response->error(true, 'No more details');
        }

        return $response->get();
    }

    public function toArray($data) {
        return json_decode(json_encode($data), true);
    }

    /**
     * Make string ready to use as a filename
     * @param string $str
     * @return null|string|string[]
     */
    public function normalizeString ($str = '') {
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode( $str, ENT_QUOTES, 'utf-8');
        $str = htmlentities($str, ENT_QUOTES, 'utf-8');
        $str = preg_replace('/(&)([a-z])([a-z]+;)/i', '$2', $str);
        $str = str_replace(' ', '-', $str);
        $str = rawurlencode($str);
        $str = str_replace('%', '-', $str);
        return $str;
    }

    /**
     * Create or add genre to movie/tv show
     * @param $genres
     * @param $class
     * @param $idShow
     */
    public function addGenre($genres, $class, $idShow): void
    {
        foreach ($genres as $key => $value) {
            //Check if genre exist
            if (!DB::table('genres')->where('name', $value->name)->exists()) {
                $genre = new Genre();
                $genre->name = $value->name;
                $genre->save();
            } else {
                //If exist we need to get it
                $genre = DB::table('genres')->where('name', $value->name)->first();
            }

            // Save genre for movie/tv show
            if ($class === 'movie') {
                $moviesGenre = new MovieGenre([
                    'genre_id' => $genre->id,
                    'movie_id' => $idShow,
                ]);
                $moviesGenre->save();
            } else {
                $tvGenre = new TvGenre([
                    'genre_id' => $genre->id,
                    'tv_id' => $idShow,
                ]);
                $tvGenre->save();
            }
        }
    }

    /**
     * Add casting
     * @param $casting
     * @param $class
     * @param $idShow
     * @param $limit
     */
    public function addCasting($casting, $class, $idShow, $limit): void
    {
        $tmp = 0;
        foreach ($casting as $key => $value) {
            if ($tmp < $limit) {
                $curl = new Curl;

                //If actor doesn't exist
                if (!DB::table('actors')->where('name', $value->name)->exists() && !DB::table('actors')->where('api_id', $value->id)->exists()) {
                    $actorData = $curl->getData('https://api.themoviedb.org/3/person/' . $value->id . '?api_key=MOVIE_KEY&language=en-US');
                    $actor = new Actor();
                    $actor = $actor->importActor($actorData);
                } else {
                    $actor = DB::table('actors')->where('name', $value->name)->first();
                }

                if ($class === 'movie') {
                    $movieActor = new MovieActor([
                        'actor_id' => $actor->id,
                        'movie_id' => $idShow,
                        'name' => $value->character,
                    ]);
                    $movieActor->save();
                } else {
                    $tvActor = new TvActor([
                        'actor_id' => $actor->id,
                        'tv_id' => $idShow,
                        'name' => $value->character,
                    ]);
                    $tvActor->save();
                }

                $tmp++;
            } else {
                //Stop iteration if reach limit
                break;
            }
        }
    }

    /**
     * Add directors
     * @param $directors
     * @param $class
     * @param $idShow
     * @param $limit
     */
    public function addDirector($directors, $class, $idShow, $limit): void
    {
        $tmp = 0;
        //Get directors
        foreach ($directors as $key => $value) {
            if ($value->job === 'Producer' && $tmp < $limit) {
                $curl = new Curl;
                if (!DB::table('directors')->where('name', $value->name)->exists()) {
                    $directorData = $curl->getData('https://api.themoviedb.org/3/person/' . $value->id . '?api_key=MOVIE_KEY&language=en-US');
                    $director = new Director();
                    $director = $director->importDirector($directorData);
                } else {
                    $director = DB::table('directors')->where('name', $value->name)->first();
                }

                if ($class === 'movie') {
                    $movieDirector = new MovieDirector([
                        'director_id' => $director->id,
                        'movie_id' => $idShow,
                    ]);
                    $movieDirector->save();
                } else {
                    $tvDirector = new TvDirector([
                        'director_id' => $director->id,
                        'movie_id' => $idShow,
                    ]);
                    $tvDirector->save();
                }
            }
        }
    }
}