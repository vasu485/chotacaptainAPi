<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorLikes extends Model
{
    public $timestamps = false;
    protected $table = 'vendor_likes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['vendorId','userId','is_liked','updatedOn'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
