<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plans';
    protected $fillable = [
        'plan_name',
        'validity',
        'Current_pricing',
        'old_pricing',
        'doctor_limit',
        'staff_limit',
        'clinic_limit',
        'admin_limit'
    ];

    public $timestamps = true;
}
