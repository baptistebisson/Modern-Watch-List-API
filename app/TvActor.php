<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class TvActor extends Model
{
    protected $table = 'tv_actor';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'actor_id','tv_id','name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
