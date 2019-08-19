<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Season extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'api_id','tv_id','episode_count','season_number','title','description',
        'release_date','poster_path'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
