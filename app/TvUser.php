<?php

namespace App;

use App\Events\ExampleEvent;
use Illuminate\Database\Eloquent\Model;

class TvUser extends Model
{
    protected $table = 'tv_user';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','tv_id', 'position', 'rating',
    ];

    public function tv() {
        return $this->hasMany('App\Tv', 'api_id');
    }
}
