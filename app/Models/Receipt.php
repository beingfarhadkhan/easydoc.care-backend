<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Receipt extends Model
{
    protected $table = 'receipts';
    protected $fillable = [
        'receipt_no',
        'clinic_id',
        'patient_id',
        'appointment_id',
        'status',
        'particulars',
        'payment_mode',
        'adv_payment_mode',
        'advance_amount',
        'pdf_url',
        'additional_discount',
        'remarks',
    ];

    public $timestamps = true;

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
    
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
    