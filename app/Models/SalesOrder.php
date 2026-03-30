<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $table ='sales_orders';
    protected $fillable = [
        'saleorder_no',
        'clinic_id',
        'patient_id',
        'height',
        'weight',
        'products', 
        'status',
        'received_logs',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
