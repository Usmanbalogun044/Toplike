<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WalletController extends Controller
{
       //wallet in json
       public function wallet( Request $request){
        $user = $request->user();
        // Check if the user has a wallet
        if (!$user->wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }
        $wallet = $user->wallet;
        return response()->json([
           'wallet'=>$wallet
        ])->setStatusCode(200,'Wallet retrieved successfully');
    }
    //wallet transactions in json
    public function walletTransactions( Request $request){
        $user = $request->user();
        // Check if the user has a wallet
        $transactions= $user->transactions;
        return response()->json([
           'transacton'=>  $transactions,
        ])->setStatusCode(200,'Wallet transactions retrieved successfully');
    }   
}
