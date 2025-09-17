<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BankController extends Controller
{
    public function getbankdetails(){
        $user = auth()->user();
         $bankAccount = $user->bankaccount()->first();
        if (!$bankAccount) {
            return response()->json(['message' => 'No bank account found'], 404);
        }
        return response()->json([
            'message' => 'Bank account details retrieved successfully',
            'bank_account' => $bankAccount,
        ])->setStatusCode(200, 'Bank account details retrieved successfully');
    }
}
