<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class TvDirector extends Model
{
    protected $table = 'tv_director';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'director_id','tv_id','name',
    ];
}
