<?php

namespace App\Http\Controllers;

use App\Helpers\Utils;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;

class UserController extends BaseController
{
    public function getStats(Request $request)
    {
        $util = new Utils();
        $user = new User();
        $movie = $user->getStats($util->getUserId($request));
        return $movie;
    }

    public function deleteData(Request $request) {
        $util = new Utils();
        $user = new User();
        $movie = $user->deleteMovie(
            $request->get('id'),
            $request->get('type'),
            $util->getUserId($request)
        );
        return $movie;
    }

    public function addMark(Request $request) {
        $util = new Utils();
        $user = new User();
        $movie = $user->addMark(
            $request->get('id'),
            $request->get('type'),
            $request->get('mark'),
            $util->getUserId($request)
        );
        return $movie;
    }

    public function test()
    {
        $user = new User();
        $movie = $user->test();
        return "";
    }
}
