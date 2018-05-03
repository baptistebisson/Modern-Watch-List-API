<?php

namespace App\Helpers;

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
}