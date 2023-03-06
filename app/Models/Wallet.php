<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    public $timestamps = false;
    protected $table = 'wallet';

    public function boy() {
        return $this->belongsTo('App\Models\User','user_id');//->select(['id', 'first_name','last_name', 'mobile']);
    }
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','amount','createdOn','updatedOn','status','status_updated_by','is_active'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
