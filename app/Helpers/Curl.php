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
     * @param  String $url URL to get
     * @return JSON        Return data
     */
    public function getData($url)
    {
        $url = str_replace('MOVIE_KEY', env('API_MOVIE_KEY'), $url);
        $return = array(
            "error" => false,
        );

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
            $return["error"] = $err;
        } else {
            $return = json_decode($response);
            if (isset($return->status_code)) {
                Log::debug('Curl class request', (array)$url);
                Log::debug('Curl class', (array)$return);
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
}
