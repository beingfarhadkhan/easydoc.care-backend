<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dictionary extends Model
{
    protected $table =  'dictionaries';
    protected $fillable = ['type', 'name'];
}
