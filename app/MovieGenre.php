<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class MovieGenre extends Model
{
    protected $table = 'movie_genre';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'genre_id','movie_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
