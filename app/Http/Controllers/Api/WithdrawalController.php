<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class WithdrawalController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawalService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:100'],
            'bank_name' => ['required', 'string'],
            'account_number' => ['required', 'string', 'min:10', 'max:10'],
            'account_name' => ['nullable', 'string'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $w = $this->withdrawalService->request(
                $user,
                (float) $request->input('amount'),
                $request->input('bank_name'),
                $request->input('account_number'),
                $request->input('account_name')
            );

            return response()->json([
                'message' => 'Withdrawal initiated',
                'data' => [
                    'id' => $w->id,
                    'status' => $w->status,
                    'processed_at' => $w->processed_at,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $withdrawals = $user->withdrawals()->orderByDesc('created_at')->paginate(20);
        return response()->json([
            'data' => $withdrawals->items(),
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
        ]);
    }
}
