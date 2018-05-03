<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class MovieActor extends Model
{
    protected $table = 'movie_actor';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'actor_id','movie_id','name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
