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

    public function timeInit()
    {
        $this->executionStartTime = microtime(true);
    }

    public function timeGet()
    {
        $executionEndTime = microtime(true);
        $seconds = $executionEndTime - $this->executionStartTime;
        return number_format($seconds,3) . 's';
    }

    public function getUserId(Request $request)
    {
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        return $user->id;
    }
}