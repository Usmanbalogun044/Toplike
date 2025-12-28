<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;
use Illuminate\Support\Facades\Log;

class BankController extends Controller
{
    public function getbankdetails(Request $request)
    {
       try {
            $user = $request->user();
            $bankAccount = $user->bank()->first();
            if (!$bankAccount) {
                return response()->json(['message' => 'No bank account found'], 404);
            }
            return response()->json([
                'message' => 'Bank account details retrieved successfully',
                'bank_account' => $bankAccount,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Bank account retrieval failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while retrieving bank account details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function allbanks(){

        $allbanks= Bank::all();
        return response()->json([
            'message'=>'received',
            'bank'=>$allbanks
        ]);
    }
     
}
