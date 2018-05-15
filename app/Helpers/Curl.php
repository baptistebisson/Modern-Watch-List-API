<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class Curl
{
    protected $curl;

    function __construct()
    {
        $this->curl = curl_init();
    }


    /**
     * Get data from URL
     * @param string $url
     * @return mixed|null
     */
    public function getData(string $url)
    {
        // If url is for themoviedb
        if (strpos($url, 'MOVIE_KEY') !== false) {
            $url = str_replace('MOVIE_KEY', env('API_MOVIE_KEY'), $url);
        }

        $return = null;

        curl_setopt_array($this->curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_POSTFIELDS => "{}",
        ));

        $response = curl_exec($this->curl);
        $err = curl_error($this->curl);

        if ($err) {
            Log::debug('Curl.php error', array($err));
        } else {
            if ($this->isJson($response)) {
                $return = json_decode($response);
            } else {
                $return = $response;
            }

            if (isset($return->status_code)) {
                Log::debug('Curl.php request', (array)$url);
                Log::debug('Curl.php', (array)$return);
                abort(404, 'API Problem');
            }
        }

        $this->closeCurl();
        return $return;
    }

    private function closeCurl()
    {
        curl_close($this->curl);
    }

    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
