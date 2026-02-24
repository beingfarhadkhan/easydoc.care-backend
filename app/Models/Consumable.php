<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consumable extends Model
{
    protected $table = 'consumables';
    protected $fillable = [
        'patient_id',
        'clinic_id',
        'date_of_appointment',
        'procedure',
        'patient_name',
        'product_used',
    ];
}
