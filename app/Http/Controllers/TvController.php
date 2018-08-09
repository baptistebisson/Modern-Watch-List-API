<?php

namespace App\Http\Controllers;

use App\Tv;
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
}