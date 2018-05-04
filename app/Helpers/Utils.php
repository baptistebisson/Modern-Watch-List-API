<?php

namespace App\Helpers;

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
}