<?php

namespace App\Services;

use Razorpay\Api\Api;

class RazorpayService
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    // ✅ Create Order
    // public function createOrder($amount)
    // {
    //     return $this->api->order->create([
    //         'receipt' => 'order_' . time(),
    //         'amount' => $amount * 100, // paise
    //         'currency' => 'INR'
    //     ]);
    // }


    public function createOrder($amount)
{
    $order = $this->api->order->create([
        'receipt' => 'order_' . time(),
        'amount' => (int) round($amount * 100), // 🔥 FIX 1
        'currency' => 'INR'
    ]);

    return $order->toArray(); // 🔥 FIX 2 (MOST IMPORTANT)
}

    // ✅ Verify Payment
    public function verifyPayment($attributes)
    {
        try {
            $this->api->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
