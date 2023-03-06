<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayUTxnDetails extends Model
{
    public $timestamps = false;
    protected $table = 'payu_txn_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['txnid','phone','net_amount_debit','hash','status','payu_addedon','payuMoneyId','createdOn'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}