<?php

namespace App\Models;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use Notifiable;
    public $timestamps = false;
    protected $table = 'vendors';

    public function postedUser() {
        return $this->belongsTo('App\Models\User','postedBy')->select(['id', 'first_name', 'last_name','image','is_active','user_role']);
    }

/*    public function postMedia() {
        return $this->hasMany('App\Models\PostMedia','postId')->where('is_active', 1);
    }

    public function likes() {
        return $this->hasMany('App\Models\PostLike','postId')->where('is_liked', 1);
    }

    public function dislikes() {
        return $this->hasMany('App\Models\PostLike','postId')->where('is_liked', 0);
    }

    public function comments() {
        return $this->hasMany('App\Models\PostComment','postId');//->where('parentId', null);
    }
*/
    public function category() {
        return $this->belongsTo('App\Models\Category','categoryId');
    }

    public function subCategory() {
        return $this->belongsTo('App\Models\SubCategory','sub_categoryId');
    }

    public function offer() {
        return $this->hasMany('App\Models\VendorOffer','vendorId')->where('is_active', 1);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'categoryId', 'sub_categoryId', 'address','postedOn', 'updatedOn', 'lat','lng', 'tags','website','media','open_time','is_active','close_time','rating','payable_item_tax','original_item_tax'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
