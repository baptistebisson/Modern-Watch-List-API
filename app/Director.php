<?php

namespace App;

use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class Director extends Model
{

    protected $fillable = ['imdb_id','api_id','name','biography','place_of_birth','popularity',
    'height','birth_date','death_date','gender','image_original', 'image_small', 'image_api'];
    public $timestamps = false;

    public static function importDirector($directorData)
    {
        $util = new Utils();
        $no_picture = false;
        $curl = curl_init();
        $url = 'http://www.imdb.com/name/'. $directorData->imdb_id .'/?ref_=tt_ov_st_sm';
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //Only english page
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept-Language: en']);

        $result = curl_exec($curl);
        curl_close($curl);

        preg_match('/Height:<\/h4>.*\n.*\((\d,\d+)/i', $result, $match);
        $height = isset($match[1]) ? $match[1] : 0;

        $birthday = isset($directorData->birthday) ? $directorData->birthday : null;
        if ($birthday == 0) {
            $birthday = null;
        }
        $deathday = isset($directorData->deathday) ? $directorData->deathday : null;
        if ($deathday == 0) {
            $deathday = null;
        }
        $place_of_birth = isset($directorData->place_of_birth) ? $directorData->place_of_birth : null;
        $biography = isset($directorData->biography) ? $directorData->biography : null;
        $popularity = isset($directorData->popularity) ? $directorData->popularity : null;

        // Format name for file
        $name_lower = preg_replace("/[\p{P}\p{Zs}]+/u", '_', strtolower($directorData->name));

        if ($directorData->profile_path == null) {
            $image_api = 'no_picture.jpg';
        } else {
            $image_api = $name_lower;
            // Upload image to host
            $upload = $util->upload_image('https://image.tmdb.org/t/p/original'. $directorData->profile_path, array(                    'folder' => "movie/d",
                'use_filename' => true,
                'public_id' => $name_lower,
            ));

            Log::debug('Director.php upload image to host', $upload);
        }

        $director = new Director([
            'imdb_id' => $directorData->imdb_id,
            'api_id' => $directorData->id,
            'name' => $directorData->name,
            'biography' => $biography,
            'image_original' => null,
            'image_small' => null,
            'image_api' => $image_api,
            'place_of_birth' => $place_of_birth,
            'popularity' => $popularity,
            'height' => null,
            'birth_date' => $birthday,
            'death_date' => $deathday,
            'gender' => $directorData->gender,
        ]);

        $director->save();

        return $director;
    }
}
