<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sattlements extends Model
{
    public $timestamps = false;
    protected $table = 'sattlements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['orders','date','sattlements','paymentMode','sattlementFor','sattlementForId','status'];

    public function vendor() {
        return $this->belongsTo('App\Models\Vendor','sattlementForId')->select(['id', 'name','address', 'description','media','rating','categoryId']);
    }

    public function deliveryBoy() {
        return $this->belongsTo('App\Models\User','sattlementForId')->select(['id', 'first_name','last_name', 'mobile']);
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}