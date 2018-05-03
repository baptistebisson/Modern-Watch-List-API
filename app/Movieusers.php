<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Movieusers extends Model
{
    protected $table = 'movie_user';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','movie_id','date_added', 'position', 'rating',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function movies() {
        return $this->hasMany('App\Movies', 'imdb_id');
    }
}
