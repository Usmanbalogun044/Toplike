<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankAccount;

class BankController extends Controller
{
    public function getbankdetails(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $bankAccount = $user->bankaccount()->first();

            if (!$bankAccount) {
                return response()->json(['message' => 'No bank account found'], 404);
            }

            return response()->json([
                'message' => 'Bank account details retrieved successfully',
                'bank_account' => $bankAccount,
            ], 200);
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
    public function allbanks(){

        $allbanks= BankAccount::all();
        return response()->json([
            'message'=>'received',
            'bank'=>$allbanks
        ]);
    }
     
}
