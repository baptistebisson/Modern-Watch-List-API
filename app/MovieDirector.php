<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class MovieDirector extends Model
{
    protected $table = 'movie_director';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'director_id','movie_id','name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
