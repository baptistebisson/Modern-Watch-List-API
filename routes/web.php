<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/auth/login', 'AuthController@postLogin');
$router->post('/auth/refresh', 'AuthController@refresh');
$router->post('/auth/me', 'AuthController@me');
$router->get('/auth/valid', 'AuthController@valid');
$router->get('/test', 'UserController@test');

$router->group(['middleware' => 'LoginMiddleware'], function($router)
{
    $router->post('/auth/test', 'AuthController@test');
});

$router->group(['middleware' => 'auth:api'], function($router)
{
    $router->get('/user/stats', 'UserController@getStats');
    $router->post('/user/delete', 'UserController@deleteData');
    $router->post('/user/mark', 'UserController@addMark');
    $router->post('/user/add', 'UserController@addToList');
    $router->get('/movie/get', 'UserController@getUserMovies');

    $router->post('/actor/details', 'ActorController@getDetails');
    $router->get('/actor/credits', 'ActorController@getMovieCredits');

    $router->post('/movie/search', 'MovieController@searchMovie');
    $router->post('/movie/create', 'MovieController@createMovie');
    $router->post('/movie/details', 'MovieController@getDetailsMovie');
    $router->post('/movie/move', 'MovieController@moveMovie');
    $router->post('/movie/refresh', 'MovieController@refresh');
    $router->get('/movie/popular', 'MovieController@getPopularMovies');

});
