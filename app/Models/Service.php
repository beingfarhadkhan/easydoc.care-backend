<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public $table = 'services';
    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'clinic_name',
        'service_name',        
        'amount',
        ];
}
