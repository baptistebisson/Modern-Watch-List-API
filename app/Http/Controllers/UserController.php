<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;

class UserController extends BaseController
{
    public function getStats(Request $request)
    {
        $movie = User::getStats($request->get('id'), $request->get('user_id'));
        return $movie;
    }

    public function deleteData(Request $request) {
        $movie = User::deleteData($request->get('id'), $request->get('type'));
        return $movie;
    }

    public function addMark(Request $request) {
        $movie = User::addMark($request->get('id'), $request->get('type'), $request->get('mark'));
        return $movie;
    }

    public function test()
    {
        $movie = User::test();
        return "";
    }
}
