<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    public $timestamps = false;
    protected $table = 'users';

    public function getJWTIdentifier() 
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims() 
    {
        return [];
    }

    public function posts() {
        return $this->hasMany('App\Post');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'email', 'password','last_name', 'image', 'mobile','user_role', 'createdOn', 'dob','updatedOn', 'is_active', 'address', 'gender', 'last_login', 'is_online', 'source', 'ip_address','aboutMe','current_location','hometown','nickName','languagesKnown','relationStatus','messageBtn','followBtn','callBtn','emailBtn'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','pwdString','loginHistoryId'
    ];
}
