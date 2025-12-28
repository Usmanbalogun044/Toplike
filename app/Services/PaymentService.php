<?php

namespace App\Services;

use Yabacon\Paystack;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $paystack;

    public function __construct()
    {
        $this->paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
    }

    /**
     * Initialize a Paystack transaction.
     */
    public function initializeTransaction($amountKobo, $email, $reference, $metadata, $callbackUrl = null)
    {
        try {
            return $this->paystack->transaction->initialize([
                'amount' => $amountKobo,
                'email' => $email,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => json_encode($metadata),
            ]);
        } catch (\Exception $e) {
            Log::error('Paystack Initialization Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify a Paystack transaction.
     */
    public function verifyTransaction($reference)
    {
        try {
            $response = $this->paystack->transaction->verify([
                'reference' => $reference,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Paystack Verification Error: ' . $e->getMessage());
            return null;
        }
    }
}
