<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    public $table = 'vendors';
    protected $fillable = [
        'clinic_id',
        'vendor_name',
        'phone',        
        'email',
        'address',
        ];
}
