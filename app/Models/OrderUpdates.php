<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderUpdates extends Model
{
    public $timestamps = false;
    protected $table = 'order_status_updates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['updatedBy','orderId','is_active','createdOn','status','statusId','updatedById'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
