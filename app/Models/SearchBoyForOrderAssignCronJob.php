<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchBoyForOrderAssignCronJob extends Model
{
    public $timestamps = false;
    protected $table = 'prepared_orders_cronjob_boy_assign';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['orderId','VendorId','type','searching_status'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}