<?php
namespace App\Helpers;

class Crawler
{
    protected $page;
    
    function __construct($url)
    {        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //Only english page
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept-Language: en']);
        $this->page = curl_exec($curl);
        curl_close($curl);
    }
    
    public function find(string $regex, bool $flag) {
        if ($flag !== false) {
            preg_match_all($regex, $this->page, $match);
        } else {
            preg_match($regex, $this->page, $match);
        }
        return $match;
    }
    
    public function findIn(string $data, string $regex, bool $flag) {
        if ($flag !== false) {
            preg_match_all($regex, $data, $match);
        } else {
            preg_match($regex, $data, $match);
        }
        return $match;
    }
}
