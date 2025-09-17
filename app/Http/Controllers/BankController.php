<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BankController extends Controller
{
    public function getbankdetails(){
        try {
            $user = auth()->user();
            $bankAccount = $user->bankaccount()->first();

            if (!$bankAccount) {
            return response()->json(['message' => 'No bank account found'], 404);
            }

            return response()->json([
            'message' => 'Bank account details retrieved successfully',
            'bank_account' => $bankAccount,
            ])->setStatusCode(200, 'Bank account details retrieved successfully');
        } catch (\Throwable $e) {
            $errorId = (string) \Illuminate\Support\Str::uuid();

            \Illuminate\Support\Facades\Log::channel('errorlog')->error('Bank account retrieval failed', [
            'error_id' => $errorId,
            'user_id' => optional(auth()->user())->id,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            ]);

            report($e);

            return response()->json([
            'message' => 'An error occurred while retrieving bank account details',
            'error_id' => $errorId,
            ], 500);
        }
     
    }
}
