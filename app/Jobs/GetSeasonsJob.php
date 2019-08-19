<?php

namespace App\Jobs;

use App\Episode;
use App\Helpers\Curl;
use App\Helpers\Image;
use App\Helpers\Utils;
use App\Season;
use App\TvUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetSeasonsJob extends Job
{

    protected $tv_id;
    protected $user_id;
    protected $data;
    protected $show_id;

    /**
     * Create a new job instance.
     * @param    $tv_id
     * @param    $user_id
     * @param    $data
     */
    public function __construct($tv_id, $user_id, $data, $show_id)
    {
        $this->tv_id = $tv_id;
        $this->user_id = $user_id;
        $this->data = $data;
        $this->show_id = $show_id;
    }

    /**
     * Execute the job.
     * @return void
     */
    public function handle(): void
    {
        $imageInstance = new Image();

        foreach ($this->data as $item) {
            // Check if seasons already exist
            if (!DB::table('seasons')->where('api_id', $item->id)->exists()) {
                $curl = new Curl;
                $details = $curl->getData('https://api.themoviedb.org/3/tv/' . $this->show_id . '/season/'. $item->season_number .'?api_key=MOVIE_KEY');

                // Create season
                $season = new Season([
                    'api_id' => $item->id,
                    'tv_id' => $this->tv_id,
                    'episode_count' => $item->episode_count,
                    'season_number' => $item->season_number,
                    'title' => $item->name,
                    'description' => $details->overview,
                    'release_date' => $item->air_date,
                    'poster_path' => $item->poster_path
                ]);
                $season->save();

                $upload = $imageInstance->uploadImage('https://image.tmdb.org/t/p/original'. $item->poster_path, array(
                    'use_filename' => true,
                    'public_id' => $item->id,
                    'folder' => 'tv/s/p',
                ));

                // Create episodes
                foreach ($details->episodes as $detail) {
                    if (!DB::table('episodes')->where('api_id', $detail->id)->exists()) {
                        $episode = new Episode([
                            'api_id' => $detail->id,
                            'season_id' => $item->id,
                            'episode_number' => $detail->episode_number,
                            'title' => $detail->name,
                            'description' => $detail->overview,
                            'release_date' => $detail->air_date,
                            'popular' => $detail->vote_average,
                            'still_path' => $detail->still_path,
                            'show_id' => $detail->show_id,
                        ]);

                        $episode->save();

                        $upload = $imageInstance->uploadImage('https://image.tmdb.org/t/p/original'. $detail->still_path, array(
                            'use_filename' => true,
                            'public_id' => $detail->id,
                            'folder' => 'tv/e/p',
                        ));
                    }
                }
            }
        }
    }
}
