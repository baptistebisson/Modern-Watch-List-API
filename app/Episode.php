<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Episode extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'api_id','season_id','episode_number','title','description',
        'release_date','popular','show_id','still_path'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
