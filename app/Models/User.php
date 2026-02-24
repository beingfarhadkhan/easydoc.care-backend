<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'is_admin',
        'selected_clinic',
        'selected_account',
        'pad_configuration',
        'vital_config',
        'template_config',
        'education',
        'specialization',
        'working_since',
        'profile_picture',
        'signature_image',
        'social_links',
        'invite_link',
        'invite_code',
        'google_review',
        'advice',
        'pad_suggestion',
        'app_type'      
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'assign_to_clinics', 'user_id', 'clinic_id');
    }


    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'assign_to_accounts', 'user_id', 'account_id');
    }


}
