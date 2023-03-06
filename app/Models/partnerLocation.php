<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerLocation extends Model
{
    public $timestamps = false;
    protected $table = 'partner_locations';

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
