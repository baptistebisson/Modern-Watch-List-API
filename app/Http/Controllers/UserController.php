<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;

class UserController extends BaseController
{
    public function getStats(Request $request)
    {
        $user = new User();
        $movie = $user->getStats($request->get('id'), $request->get('user_id'));
        return $movie;
    }

    public function deleteData(Request $request) {
        $user = new User();
        $movie = $user->deleteData($request->get('id'), $request->get('type'));
        return $movie;
    }

    public function addMark(Request $request) {
        $user = new User();
        $movie = $user->addMark($request->get('id'), $request->get('type'), $request->get('mark'));
        return $movie;
    }

    public function test()
    {
        $user = new User();
        $movie = $user->test();
        return "";
    }
}
