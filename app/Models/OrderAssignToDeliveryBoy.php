<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAssignToDeliveryBoy extends Model
{
    public $timestamps = false;
    protected $table = 'order_assign_to_deliveryboy';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['orderId','boyId','boyDecision','updatedOn'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}