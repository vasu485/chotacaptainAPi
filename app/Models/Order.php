<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['orderBy','vendorId','is_active','createdOn','status','updatedOn','price','deliveryBoy','paymentMode','address','lat','lng','locationId'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function vendor() {
        return $this->belongsTo('App\Models\Vendor','vendorId')->select(['id', 'name','address', 'description','media','rating','categoryId','lat','lng']);
    }

    public function orderBy() {
        return $this->belongsTo('App\Models\User','orderBy')->select(['id', 'first_name','last_name', 'mobile']);
    }

    public function deliveryBoy() {
        return $this->belongsTo('App\Models\User','deliveryBoy');//->select(['id', 'first_name','last_name', 'mobile']);
    }

    public function status() {
        return $this->belongsTo('App\Models\Status','status')->select(['id', 'name']);
    }

    public function location() {
        return $this->belongsTo('App\Models\Location','locationId');
    }

    public function updates() {
        return $this->hasMany('App\Models\OrderUpdates','orderId')->orderBy('createdOn','desc');
    }

    public function items() {
        return $this->hasMany('App\Models\OrderItems','orderId');
    }
}
