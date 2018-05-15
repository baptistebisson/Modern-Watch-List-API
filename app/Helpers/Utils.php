<?php

namespace App\Helpers;

include '../config/settings.php';

use Cloudinary\Api\GeneralError;
use Illuminate\Http\Request;
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
     * Upload image to host
     * @param string $imgUrl
     * @param array  $options
     * @return mixed
     */
    public function upload_image(string $imgUrl, array $options) {
        $result = \Cloudinary\Uploader::upload($imgUrl, $options);
        return $result;
    }


    /**
     * Correct public id of image
     */
    public function rename_api_images() {
        $api = new \Cloudinary\Api();
        try {
            $result = $api->resources([
                'type' => 'upload',
                'prefix' => 'movie/a',
            ]);
        } catch (GeneralError $e) {
        }


        while (!empty($result) && array_key_exists("next_cursor", $result)) {
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
    public function getPersonMoreDetails(string $table, int $id) {
        $response = new Response();
        $curl = new Curl();
        // Check if we already have more details
        $actor = DB::table($table)->where('id', $id)->first();
        if ($actor->height == null) {
            $data = $curl->getData('https://www.imdb.com/name/'. $actor->imdb_id .'/?ref_=tt_ov_st_sm');
            preg_match('/Height:<\/h4>.*\n.*\((\d.\d+)/i', $data, $match);
            $height = isset($match[1]) ? $match[1] : null;

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

}