<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltySetting;
use App\Models\LoyaltyTransaction;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class LoyaltyService
{
    /**
     * Award loyalty points to customer for completed invoice
     */
    public function awardPointsForInvoice(Invoice $invoice): int
    {
        try {
            DB::beginTransaction();

            $settings = LoyaltySetting::getSettings();
            
            if (!$settings->is_active) {
                Log::info('Loyalty system is inactive, no points awarded', ['invoice_id' => $invoice->id]);
                DB::rollBack();
                return 0;
            }

            // Skip cash customer (ID: 1)
            if ($invoice->customer_id == 1) {
                Log::info('Cash customer invoice, no loyalty points awarded', ['invoice_id' => $invoice->id]);
                DB::rollBack();
                return 0;
            }

            $customer = $invoice->customer;
            if (!$customer) {
                Log::warning('Invoice has no customer, cannot award points', ['invoice_id' => $invoice->id]);
                DB::rollBack();
                return 0;
            }

            // Calculate points based on invoice
            $productCount = (int) $invoice->items->sum('quantity');
            $invoiceAmount = (float) $invoice->total;
            
            $points = $settings->calculatePoints($invoiceAmount, $productCount);
            
            if ($points <= 0) {
                Log::info('No points calculated for invoice', [
                    'invoice_id' => $invoice->id,
                    'amount' => $invoiceAmount,
                    'product_count' => $productCount,
                    'earning_method' => $settings->earning_method
                ]);
                DB::rollBack();
                return 0;
            }

            // Award points to customer
            $pointsAwarded = $customer->awardLoyaltyPoints(
                $invoiceAmount,
                $productCount,
                $invoice->invoice_number ?? (string) $invoice->id
            );

            DB::commit();

            Log::info('Loyalty points awarded successfully', [
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'points_awarded' => $pointsAwarded
            ]);

            return $pointsAwarded;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error awarding loyalty points', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Redeem customer points to balance
     */
    public function redeemPointsToBalance(Customer $customer, int $points): float
    {
        try {
            DB::beginTransaction();

            $settings = LoyaltySetting::getSettings();
            
            if (!$settings->is_active) {
                throw new \Exception('نظام نقاط الولاء غير مفعل');
            }

            // Validate redemption
            if (!$settings->isRedemptionAllowed($points)) {
                throw new \Exception('لا يمكن استبدال هذا العدد من النقاط');
            }

            if ($customer->total_loyalty_points < $points) {
                throw new \Exception('عدد النقاط المتاحة غير كافي');
            }

            $amount = $customer->redeemPointsToBalance($points);

            DB::commit();

            Log::info('Points redeemed to balance successfully', [
                'customer_id' => $customer->id,
                'points_redeemed' => $points,
                'amount_credited' => $amount
            ]);

            return $amount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error redeeming points to balance', [
                'customer_id' => $customer->id,
                'points' => $points,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Apply points discount to invoice
     */
    public function applyPointsDiscountToInvoice(Customer $customer, int $points, string $invoiceNumber): float
    {
        try {
            DB::beginTransaction();

            $settings = LoyaltySetting::getSettings();
            
            if (!$settings->is_active) {
                throw new \Exception('نظام نقاط الولاء غير مفعل');
            }

            $discountAmount = $customer->applyPointsDiscount($points, $invoiceNumber);

            DB::commit();

            Log::info('Points discount applied to invoice successfully', [
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber,
                'points_used' => $points,
                'discount_amount' => $discountAmount
            ]);

            return $discountAmount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error applying points discount to invoice', [
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber,
                'points' => $points,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Manually adjust customer loyalty points
     */
    public function adjustCustomerPoints(Customer $customer, int $points, int $userId, string $reason): void
    {
        try {
            DB::beginTransaction();

            $customer->adjustLoyaltyPoints($points, $userId, $reason);

            DB::commit();

            Log::info('Customer points adjusted manually', [
                'customer_id' => $customer->id,
                'points_adjustment' => $points,
                'user_id' => $userId,
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adjusting customer points', [
                'customer_id' => $customer->id,
                'points' => $points,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get customer loyalty summary
     */
    public function getCustomerLoyaltySummary(Customer $customer): array
    {
        $settings = LoyaltySetting::getSettings();
        
        return [
            'total_points' => $customer->total_loyalty_points,
            'earned_points' => $customer->getTotalEarnedPoints(),
            'redeemed_points' => $customer->getTotalRedeemedPoints(),
            'available_for_redemption' => $customer->getAvailablePointsForRedemption(),
            'points_value_in_currency' => $settings->pointsToCurrency($customer->total_loyalty_points),
            'status_label' => $customer->getLoyaltyStatusLabel(),
            'can_redeem' => $customer->total_loyalty_points >= $settings->min_points_for_redemption,
            'min_points_for_redemption' => $settings->min_points_for_redemption,
            'max_redemption_per_transaction' => $settings->max_redemption_per_transaction,
            'points_to_currency_rate' => $settings->points_to_currency_rate,
            'earning_method' => $settings->earning_method_label
        ];
    }

    /**
     * Get customer loyalty transactions history
     */
    public function getCustomerLoyaltyHistory(Customer $customer, int $limit = 50): array
    {
        $transactions = $customer->loyaltyTransactions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'type_label' => $transaction->type_label,
                'source_type' => $transaction->source_type,
                'source_type_label' => $transaction->source_type_label,
                'points' => $transaction->points,
                'formatted_points' => $transaction->formatted_points,
                'redeemed_amount' => $transaction->redeemed_amount,
                'balance_after' => $transaction->balance_after,
                'reference_id' => $transaction->reference_id,
                'description' => $transaction->description,
                'user_name' => $transaction->user?->name,
                'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $transaction->created_at->diffForHumans()
            ];
        })->toArray();
    }

    /**
     * Calculate maximum discount possible with available points
     */
    public function getMaximumDiscountAvailable(Customer $customer): array
    {
        $settings = LoyaltySetting::getSettings();
        
        if (!$settings->is_active || $customer->total_loyalty_points < $settings->min_points_for_redemption) {
            return [
                'max_points' => 0,
                'max_discount' => 0.0,
                'can_discount' => false
            ];
        }

        $maxPoints = $customer->total_loyalty_points;
        
        // Apply transaction limit if set
        if ($settings->max_redemption_per_transaction) {
            $maxPoints = min($maxPoints, $settings->max_redemption_per_transaction);
        }

        $maxDiscount = $settings->pointsToCurrency($maxPoints);

        return [
            'max_points' => $maxPoints,
            'max_discount' => $maxDiscount,
            'can_discount' => true
        ];
    }

    /**
     * Update loyalty settings
     */
    public function updateLoyaltySettings(array $data): LoyaltySetting
    {
        try {
            DB::beginTransaction();

            $settings = LoyaltySetting::getSettings();
            $settings->update($data);

            DB::commit();

            Log::info('Loyalty settings updated', [
                'updated_data' => $data,
                'updated_by' => auth()->id()
            ]);

            return $settings;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating loyalty settings', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get loyalty statistics for dashboard
     */
    public function getLoyaltyStatistics(): array
    {
        $settings = LoyaltySetting::getSettings();
        
        return [
            'is_active' => $settings->is_active,
            'total_customers_with_points' => Customer::where('total_loyalty_points', '>', 0)->count(),
            'total_points_awarded' => (int) LoyaltyTransaction::earned()->sum('points'),
            'total_points_redeemed' => abs((int) LoyaltyTransaction::redeemed()->sum('points')),
            'total_amount_redeemed' => (float) LoyaltyTransaction::redeemed()->sum('redeemed_amount'),
            'transactions_this_month' => LoyaltyTransaction::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'top_customers_by_points' => Customer::where('total_loyalty_points', '>', 0)
                ->orderBy('total_loyalty_points', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'total_loyalty_points'])
                ->toArray(),
            'earning_method' => $settings->earning_method,
            'earning_method_label' => $settings->earning_method_label
        ];
    }

    /**
     * Reset customer loyalty points
     */
    public function resetCustomerPoints(Customer $customer, int $userId, string $reason): void
    {
        try {
            DB::beginTransaction();

            $currentPoints = $customer->total_loyalty_points;
            
            if ($currentPoints > 0) {
                // Create transaction to record the reset
                LoyaltyTransaction::createManual(
                    $customer->id,
                    -$currentPoints,
                    $userId,
                    "إعادة تعيين النقاط: {$reason}"
                );
            }

            DB::commit();

            Log::info('Customer loyalty points reset', [
                'customer_id' => $customer->id,
                'points_removed' => $currentPoints,
                'user_id' => $userId,
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resetting customer points', [
                'customer_id' => $customer->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 