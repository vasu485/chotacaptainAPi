<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrimeCustomerData extends Model
{
    public $timestamps = false;
    protected $table = 'prime_customer_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','createdOn','months','category','expiredOn','is_active'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}