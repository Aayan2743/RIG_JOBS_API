<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
    'user_id',
    'razorpay_payment_id',
    'razorpay_order_id',
    'usd_amount',
    'inr_amount',
    'exchange_rate',
    'status'
];


public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}
}
