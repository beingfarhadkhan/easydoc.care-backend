<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    public $table = 'prescriptions';
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'clinic_id',
        'prescription_data',
        'prescription_url',
    ];
}
