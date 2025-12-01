<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBilling extends Model
{
    protected $table = 'account_billings';
    protected $fillable = [
        'account_id',
        'plan_name',
        'plan_price',
        'plan_start_date',
        'plan_end_date',
        'billing_cycle',
        'no_of_docs_in_use',
        'no_of_docs_allowed',
        'no_of_admins_in_use',
        'no_of_admins_in_allowed',
        'no_of_staff_in_use',
        'no_of_staff_allowed',
        'no_of_clinics_in_use',
        'no_of_clinics_allowed',
    ];
}
