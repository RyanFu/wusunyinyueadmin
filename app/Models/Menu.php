<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    //
    use SoftDeletes;
    protected $table="menus";
    
    public $dates = ['deleted_at'];
    public $timestamps = true;
}