<?php

namespace App\Http\Controllers;

use App\Helpers\Utils;
use App\Tv;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class TvController extends BaseController
{
    public function searchSerie(Request $request) {
        $tv = new Tv();
        $title = $request->get('title');
        $find_title = str_replace(' ', '%20', $title);
        if (\strlen($find_title) > 3) {
            return json_encode($tv->findShow($find_title));
        }

        return null;
    }

    /**
     * Refresh tv shows list
     * @param Request $request
     * @return string
     */
    public function refresh(Request $request) {
        $tv = new Tv();
        $util = new Utils();

        $return = $tv->upToDate(
            array_reverse($request->get('tv')),
            $util->getUserId($request));
        return json_encode($return);
    }

    public function createShow(Request $request)
    {
        $util = new Utils();
        $movie = new Tv();
        $movie = $movie->getShow($request->get('id'), $util->getUserId($request));
        return json_encode($movie);
    }

    public function getDetails(Request $request)
    {
        $util = new Utils();
        $rate = DB::table('tv_user')
            ->where('tv_id', $request->get('id'))
            ->where('user_id', $util->getUserId($request))
            ->select('rating')->first();

        $tv = Tv::with('genres', 'actors', 'directors')
            ->where('id', $request->get('id'))
            ->first();

        $tv['seasons'] = DB::table('seasons')->where('tv_id', $request->get('id'))->get();

        if ($rate) {
            $tv['user_rate'] = $rate->rating;
        } else {
            // This is a new tv show
            $tv['user_rate'] = 404;
        }
        return json_encode($tv);
    }
}