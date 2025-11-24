<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vital extends Model
{
    public $table = 'vitals';
    protected $fillable = [
        'patient_id',
        'appointment_id',       
        'vitals',
        'lab_results'
    ];
}
