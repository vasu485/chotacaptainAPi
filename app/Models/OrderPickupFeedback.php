<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPickupFeedback extends Model
{
    public $timestamps = false;
    protected $table = 'order_pickup_feedback';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['qId','orderId','feedback','boyId','createdOn'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
