<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignToAccount extends Model
{
    protected $table = 'assign_to_accounts';
    protected $fillable = [
        'user_id',
        'account_id',    
    ];

    public function account()
    {
        // This assumes the AssignToAccount table has an 'account_id' column
        // that matches the 'id' column on the Account model (App\Models\Account).
        return $this->belongsTo(Account::class); 
    }

    public function user()
    {
        // This assumes the AssignToAccount table has a 'user_id' column
        // that matches the 'id' column on the User model (App\Models\User).
        return $this->belongsTo(User::class); 
    }
    
}

