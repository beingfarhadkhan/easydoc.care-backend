<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    public $table = 'medical_records';
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'record_type',
        'document_url',
        'investigation_date'
    ];
}
