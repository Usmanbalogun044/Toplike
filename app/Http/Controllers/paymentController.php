<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class paymentController extends Controller
{
    //service for paystack initilize
    
    // public function initializePayment($email,$amount,$reference)
    // {
    //     $user = auth()->user();
    //     $amount = $request->input('amount');
    //     $email = $user->email;

    //     // Initialize payment with Paystack
    //     $paystack = new \Yabacon\Paystack(config('services.paystack.secret'));
    //     $response = $paystack->transaction->initialize([
    //         'amount' => $amount * 100, // Amount in kobo
    //         'email' => $email,
    //         'callback_url' => route('payment.callback'),
    //     ]);

    //     return response()->json($response);
    // }
}
