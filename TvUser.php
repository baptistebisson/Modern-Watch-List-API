<?php
/**
 * Created by PhpStorm.
 * User: baptiste
 * Date: 09/08/18
 * Time: 15:53
 */

class TvUser
{
    protected $fillable = [
        'user_id','tv_id', 'position', 'rating',
    ];

    public function tv() {
        return $this->hasMany('App\Tv', 'api_id');
    }
}