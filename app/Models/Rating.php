<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    public $timestamps = false;
    protected $table = 'business_rating';

    public function user() {
        return $this->belongsTo('App\Models\User','userId')->select(['id', 'first_name', 'last_name','image','is_active','is_online','user_role','business_name','image_ratio','cover_pic_ratio','cover_pic']);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['userId','businessId','rating'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
