<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileOTP extends Model
{
    public $timestamps = false;
    protected $table = 'mobile_otp';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['mobileno','type','createdOn','otp','status'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
