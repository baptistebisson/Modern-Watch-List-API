<?php

namespace App\Helpers;

include '../config/settings.php';

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

    public function upload_image(string $imgUrl, array $options) {
        \Cloudinary\Uploader::upload($imgUrl, $options);
    }
}