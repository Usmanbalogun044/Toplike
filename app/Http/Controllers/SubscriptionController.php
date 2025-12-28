<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yabacon\Paystack;

class SubscriptionController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return response()->json([
            'message' => 'Subscription plans retrieved successfully',
            'plans' => $plans
        ]);
    }

    public function initialize(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // Check if user already has an active subscription
        $activeSubscription = $user->activeSubscription;
        if ($activeSubscription) {
            return response()->json([
                'message' => 'You already have an active subscription.',
            ], 400);
        }

        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            $reference = Paystack::genTranxRef();
            
            // Amount is in Kobo
            $amountKobo = $plan->price * 100;

            $tranx = $paystack->transaction->initialize([
                'amount' => $amountKobo,
                'email' => $user->email,
                'reference' => $reference,
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'type' => 'subscription_payment'
                ],
                'callback_url' => route('payment.callback') // Reuse existing or define new
            ]);

            return response()->json([
                'message' => 'Payment initialized',
                'authorization_url' => $tranx->data->authorization_url,
                'access_code' => $tranx->data->access_code,
                'reference' => $reference
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment initialization failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return response()->json(['message' => 'No reference provided'], 400);
        }

        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            $tranx = $paystack->transaction->verify([
                'reference' => $reference,
            ]);

            if ('success' === $tranx->data->status) {
                // Check if transaction already processed
                $existingTx = SubscriptionTransaction::where('reference', $reference)->first();
                if ($existingTx) {
                    return response()->json(['message' => 'Transaction already processed'], 200);
                }

                $meta = $tranx->data->metadata;
                
                // Only process if it's a subscription payment
                if (!isset($meta->type) || $meta->type !== 'subscription_payment') {
                     // Fallback or ignore if it's another type of payment
                     return response()->json(['message' => 'Not a subscription payment'], 400);
                }

                $user_id = $meta->user_id;
                $plan_id = $meta->plan_id;
                
                $user = \App\Models\User::find($user_id);
                $plan = SubscriptionPlan::find($plan_id);

                if (!$user || !$plan) {
                     return response()->json(['message' => 'User or Plan not found'], 404);
                }

                DB::transaction(function () use ($user, $plan, $tranx, $reference) {
                    // Record Transaction
                    SubscriptionTransaction::create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'reference' => $reference,
                        'amount' => $tranx->data->amount / 100,
                        'currency' => $tranx->data->currency,
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);

                    // Create/Update Subscription
                    // Expire old ones logic? For now just create new active one
                    // Calculate expiry
                    $expiresAt = now()->addDays($plan->duration_days);

                    UserSubscription::create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'starts_at' => now(),
                        'expires_at' => $expiresAt,
                        'paystack_subscription_code' => null, // If using recurrent
                    ]);

                    // Update User Verification
                    $user->is_verified = true;
                    $user->verified_expires_at = $expiresAt;
                    $user->save();
                });

                return response()->json([
                    'message' => 'Subscription successful',
                    'user' => $user,
                    'status' => 'success'
                ]);
            }

            return response()->json(['message' => 'Transaction failed'], 400);

        } catch (\Exception $e) {
             return response()->json([
                'message' => 'Verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
