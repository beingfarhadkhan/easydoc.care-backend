<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EasydocBillingPayment extends Model
{
    protected $table = 'easydoc_billing_payments';
    protected $fillable = [
        'invoice_id',
        'user_id',
        'account_id',
        'razorpay_order_id',
        'rozarpay_payment_id',
        'amount',
        'status',
        'captured',
        'invoice_data',
        'invoice_date',
        'webhook_data'
    ];
}
