<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dummy extends Model
{
     protected $table = 'dummys';
    protected $fillable = [
        'data'
    ];
}
