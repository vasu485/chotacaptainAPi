<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorTags extends Model
{
    public $timestamps = false;
    protected $table = 'vendor_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
