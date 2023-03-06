<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorOffer extends Model
{
    public $timestamps = false;
    protected $table = 'vendor_offers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['vendorId','minOrder','offerPercentage','maxOfferAmount'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
