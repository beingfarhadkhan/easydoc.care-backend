<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
     protected $table = 'accounts';
        protected $fillable = [
            'legal_name',
            'display_name',
            'address',
            'city',
            'state',
            'zip',
            'country',
            'gst',
            'pan',
            'primary_user'
        ];


    public function clinics()
    {
        return $this->hasMany(Clinic::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'assign_to_accounts', 'account_id', 'user_id');
    }
            
}
