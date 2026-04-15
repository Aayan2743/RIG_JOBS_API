<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\RazorpayService;
use App\Services\CurrencyService;
class RozarpayPaymentController extends Controller
{
     protected $razorpay;
     protected $currency;


  public function __construct(RazorpayService $razorpay, CurrencyService $currency)
    {
        $this->razorpay = $razorpay;
        $this->currency = $currency;
    }


  public function createOrder(Request $request)
    {
        try {
            $usdAmount = $request->amount;

            // 🔥 LIVE RATE
            $rate = $this->currency->getUsdToInr();

            $inrAmount = $usdAmount * $rate;

            $order = $this->razorpay->createOrder($inrAmount);

            return response()->json([
                'success' => true,
                'order' => $order,
                'conversion' => [
                    'usd' => $usdAmount,
                    'rate' => $rate,
                    'inr' => $inrAmount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    // ✅ Verify Payment
    // public function verify(Request $request)
    // {
    //     $isValid = $this->razorpay->verifyPayment([
    //         'razorpay_order_id' => $request->razorpay_order_id,
    //         'razorpay_payment_id' => $request->razorpay_payment_id,
    //         'razorpay_signature' => $request->razorpay_signature,
    //     ]);

    //     if ($isValid) {
    //         // ✅ Save to DB here
    //         return response()->json(['success' => true]);
    //     }

    //     return response()->json(['success' => false], 400);
    // }


    public function verify(Request $request)
{
    $isValid = $this->razorpay->verifyPayment([
        'razorpay_order_id' => $request->razorpay_order_id,
        'razorpay_payment_id' => $request->razorpay_payment_id,
        'razorpay_signature' => $request->razorpay_signature,
    ]);

    if ($isValid) {

        // 🔥 GET USER
        $user = auth()->user();

        // 🔥 OPTIONAL: get conversion again (or pass from frontend)
        $usdAmount = 5; // your fixed fee
        $rate = app(CurrencyService::class)->getUsdToInr();
        $inrAmount = $usdAmount * $rate;

        // ✅ SAVE PAYMENT
        Payment::create([
            'user_id' => $user->id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_order_id' => $request->razorpay_order_id,
            'usd_amount' => $usdAmount,
            'inr_amount' => $inrAmount,
            'exchange_rate' => $rate,
            'status' => 'paid',
        ]);

        return response()->json(['success' => true]);
    }

    return response()->json(['success' => false], 400);
}


public function checkPayment()
{
    $userId = auth()->id();

    $hasPaid = Payment::where('user_id', $userId)
        ->where('status', 'paid')
        ->exists();

    return response()->json([
        'success' => true,
        'has_paid' => $hasPaid
    ]);
}



public function index(Request $request)
{
    $perPage = $request->get('per_page', 10);
    $search  = $request->get('search');

    $query = Payment::with('user'); // 🔥 relation needed

    // 🔍 Search (user name/email + payment id)
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {

            $q->where('razorpay_payment_id', 'like', "%$search%")
              ->orWhereHas('user', function ($q2) use ($search) {
                  $q2->where('name', 'like', "%$search%")
                     ->orWhere('email', 'like', "%$search%");
              });
        });
    }

    $payments = $query->latest()->paginate($perPage);

    // 🎯 FORMAT FOR YOUR UI (IMPORTANT)
    $data = collect($payments->items())->map(function ($p) {

        return [
            'id' => $p->razorpay_payment_id, // 👈 important for UI
            'amount' => $p->usd_amount,
            'payerEmail' => $p->user->email ?? '',
            'payerName' => $p->user->name ?? '',
            'paymentFor' => 'Job Application',
            'feeLabel' => 'Application Fee',
            'status' => $p->status,
            'createdAt' => $p->created_at,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'total' => $payments->total(),
        ]
    ]);
}
}
