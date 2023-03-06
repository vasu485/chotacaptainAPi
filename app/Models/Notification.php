<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;
    protected $table = 'notifications';

    public function initiator() {
        return $this->belongsTo('App\Models\User','initiator')->select(['id', 'first_name', 'last_name','image','is_active','is_online','user_role','business_name','image_ratio','cover_pic_ratio','cover_pic']);
    }

    public function receiver() {
        return $this->belongsTo('App\Models\User','receiver')->select(['id', 'first_name', 'last_name','image','is_active','is_online','user_role','business_name','image_ratio','cover_pic_ratio','cover_pic']);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['type','receiver','initiator','is_read','msgStatus','msgResponse','title','createdOn'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
