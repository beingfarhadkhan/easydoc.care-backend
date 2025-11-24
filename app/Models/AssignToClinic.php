<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignToClinic extends Model
{
    protected $table = 'assign_to_clinics';
    protected $fillable = [
        'user_id',
        'clinic_id',    
    ];


public function clinic()
    {
        // This assumes the AssignToClinic table has a 'clinic_id' column
        // that matches the 'id' column on the Clinic model (App\Models\Clinic).
        return $this->belongsTo(Clinic::class); 
    }

    public function user()
    {
        // This assumes the AssignToClinic table has a 'user_id' column
        // that matches the 'id' column on the User model (App\Models\User).
        return $this->belongsTo(User::class); 
    }
}