<?php
/**
 * Created by PhpStorm.
 * User: Eustache
 * Date: 11/05/2018
 * Time: 18:33
 */

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class HistoryQueries extends Model
{
    protected $fillable = ['type_id','query'];
}