<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransactionCreateRequest;
use App\Http\Requests\Transaction\TransactionUpdateStatusRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Get all transactions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'merchant_id',
                'status',
                'payment_method',
                'start_date',
                'end_date'
            ]);

            $transactions = $this->transactionService->getAll($filters);

            return ResponseHelpers::jsonResponse(
                true,
                'Transactions retrieved successfully',
                TransactionResource::collection($transactions),
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve transactions',
                null,
                500
            );
        }
    }

    /**
     * Get transaction detail by ID
     */
    public function show(string $id): JsonResponse
    {
        try {
            $transaction = $this->transactionService->getById($id);

            return ResponseHelpers::jsonResponse(
                true,
                'Transaction detail retrieved',
                new TransactionResource($transaction),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Transaction not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve transaction', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve transaction',
                null,
                500
            );
        }
    }

    /**
     * Get transaction by invoice code
     */
    public function showByInvoice(string $invoiceCode): JsonResponse
    {
        try {
            $transaction = $this->transactionService->getByInvoice($invoiceCode);

            return ResponseHelpers::jsonResponse(
                true,
                'Transaction detail retrieved',
                new TransactionResource($transaction),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Transaction not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve transaction by invoice', [
                'invoice_code' => $invoiceCode,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve transaction',
                null,
                500
            );
        }
    }

    /**
     * Create new transaction (checkout)
     */
    public function store(TransactionCreateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $transaction = $this->transactionService->create($data);

            return ResponseHelpers::jsonResponse(
                true,
                'Transaction created successfully',
                new TransactionResource($transaction),
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseHelpers::jsonResponse(
                false,
                $e->getMessage(),
                $e->errors(),
                422
            );
        } catch (Exception $e) {
            Log::error('Failed to create transaction', [
                'data' => $request->except(['products']),
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to create transaction',
                null,
                500
            );
        }
    }

    /**
     * Update transaction status
     */
    public function updateStatus(TransactionUpdateStatusRequest $request, string $id): JsonResponse
    {
        try {
            $status = $request->validated()['status'];
            $transaction = $this->transactionService->updateStatus($id, $status);

            return ResponseHelpers::jsonResponse(
                true,
                'Transaction status updated successfully',
                new TransactionResource($transaction),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Transaction not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to update transaction status', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to update transaction status',
                null,
                500
            );
        }
    }

    /**
     * Mark transaction as paid
     */
    public function markAsPaid(Request $request, string $id): JsonResponse
    {
        try {
            $paymentData = $request->only(['payment_method', 'payment_reference']);
            $transaction = $this->transactionService->markAsPaid($id, $paymentData);

            return ResponseHelpers::jsonResponse(
                true,
                'Transaction marked as paid',
                new TransactionResource($transaction),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Transaction not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to mark transaction as paid', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to mark transaction as paid',
                null,
                500
            );
        }
    }

    /**
     * Cancel transaction
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $transaction = $this->transactionService->cancel($id);

            return ResponseHelpers::jsonResponse(
                true,
                'Transaction cancelled successfully',
                new TransactionResource($transaction),
                200
            );
        } catch (ModelNotFoundException) {
            return ResponseHelpers::jsonResponse(
                false,
                'Transaction not found',
                null,
                404
            );
        } catch (Exception $e) {
            Log::error('Failed to cancel transaction', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to cancel transaction',
                null,
                500
            );
        }
    }

    /**
     * Get transaction summary by merchant
     */
    public function summary(Request $request, string $merchantId): JsonResponse
    {
        try {
            $dateRange = $request->only(['start_date', 'end_date']);
            $summary = $this->transactionService->getSummaryByMerchant($merchantId, $dateRange);

            return ResponseHelpers::jsonResponse(
                true,
                'Transaction summary retrieved successfully',
                $summary,
                200
            );
        } catch (Exception $e) {
            Log::error('Failed to retrieve transaction summary', [
                'merchant_id' => $merchantId,
                'error' => $e->getMessage()
            ]);

            return ResponseHelpers::jsonResponse(
                false,
                'Failed to retrieve transaction summary',
                null,
                500
            );
        }
    }
}
