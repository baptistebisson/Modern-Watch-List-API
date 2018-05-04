<?php

namespace App;

use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;


class Director extends Model
{

    protected $fillable = ['imdb_id','api_id','name','biography','place_of_birth','popularity',
    'height','birth_date','death_date','gender','image_original', 'image_small'];
    public $timestamps = false;

    public static function getDirector($directorData)
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

        if ($directorData->profile_path == null) {
            $no_picture = true;
            $image_original = 'no_picture.jpg';
            $image_small = 'no_picture.jpg';
        } else {
            $image_original = str_replace(" ", "_", $directorData->name). '.jpg';
            $image_small = str_replace(" ", "_", $directorData->name). '_small.jpg';
        }

        $director = new Director([
            'imdb_id' => $directorData->imdb_id,
            'api_id' => $directorData->id,
            'name' => $directorData->name,
            'biography' => $biography,
            'image_original' => $image_original,
            'image_small' => $image_small,
            'place_of_birth' => $place_of_birth,
            'popularity' => $popularity,
            'height' => null,
            'birth_date' => $birthday,
            'death_date' => $deathday,
            'gender' => $directorData->gender,
        ]);

        if (!$no_picture) {
            if (!file_exists('/var/www/api/public/img/d/'. str_replace(" ", "_", $directorData->name). '.jpg')) {
                $util->save_image('https://image.tmdb.org/t/p/w185'. $directorData->profile_path,
                    '/var/www/api/public/img/d/'. str_replace(" ", "_", $directorData->name). '_small.jpg');

                $util->save_image('https://image.tmdb.org/t/p/original'. $directorData->profile_path,
                    '/var/www/api/public/img/d/'. str_replace(" ", "_", $directorData->name). '.jpg');
            }
        }

        $director->save();

        return $director;
    }

    /**
     * Save image to public folder
     * @param  String $img      Image url
     * @param  String $fullpath Path to save the picture
     * @return Boolean          Success or not
     */
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
