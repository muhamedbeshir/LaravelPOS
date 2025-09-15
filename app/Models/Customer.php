<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'notes',
        'payment_type',
        'credit_balance',
        'credit_limit',
        'due_days',
        'is_active',
        'is_unlimited_credit',
        'default_price_type_id',
        'total_loyalty_points'
    ];

    protected $casts = [
        'credit_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'due_days' => 'integer',
        'is_active' => 'boolean',
        'is_unlimited_credit' => 'boolean',
        'total_loyalty_points' => 'integer'
    ];

    // Relationships
    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function defaultPriceType()
    {
        return $this->belongsTo(PriceType::class, 'default_price_type_id');
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCredit($query)
    {
        return $query->where('payment_type', 'credit');
    }

    public function scopeWithDueBalance($query)
    {
        // Customers with debt will have negative balances
        return $query->where('credit_balance', '<', 0);
    }

    // Helper methods
    public function addPayment($amount, $paymentMethod = 'cash', $notes = null, $overrideBalanceCheck = false)
    {
        try {
            // When customer makes a payment, we subtract from the debt (add to balance)
            // If balance is -1000 and payment is 200, new balance should be -800
            $remainingBalance = $this->credit_balance + $amount;
            
            $payment = $this->payments()->create([
                'amount' => $amount,
                'payment_date' => now(),
                'payment_method' => $paymentMethod,
                'notes' => $notes
            ]);

            $this->update(['credit_balance' => $remainingBalance]);

            return $payment;
        } catch (\Exception $e) {
            throw new \Exception('خطأ في إضافة الدفعة: ' . $e->getMessage());
        }
    }

    public function addToBalance($amount)
    {
        try {
            $oldBalance = $this->credit_balance;
            
            // Important: For credit invoices, we ADD to balance, but this should increase DEBT
            // So negative values indicate the customer OWES money (مديونية عليه)
            // Positive values indicate the customer HAS credit (مديونية له)
            $this->credit_balance += $amount;
            
            // For credit limit checking, we need to work with absolute values
            // Since higher negative numbers mean more debt
            if (!$this->is_unlimited_credit && abs($this->credit_balance) > $this->credit_limit) {
                throw new \Exception('تجاوز حد الائتمان المسموح به');
            }

            $this->save();

            return true;
        } catch (\Exception $e) {
            throw new \Exception('خطأ في تحديث الرصيد: ' . $e->getMessage());
        }
    }

    public function getDueInvoices()
    {
        $dueDate = now()->subDays($this->due_days);
        return $this->invoices()
            ->where('type', 'credit')
            ->where('payment_status', '!=', 'paid')
            ->where('created_at', '<=', $dueDate)
            ->get();
    }

    public function getTotalDueAmount()
    {
        return $this->getDueInvoices()->sum('remaining_amount');
    }

    public function checkCreditLimit($amount)
    {
        // العملاء ذوي الائتمان غير المحدود يمكنهم تجاوز الحد دائمًا
        if ($this->is_unlimited_credit) {
            return true;
        }
        
        // Since credit_balance is negative for debt, we need to check absolute values
        // A more negative number means more debt
        return abs($this->credit_balance + $amount) <= $this->credit_limit;
    }

    public function getStatusClass()
    {
        // Negative balance means debt (مديونية عليه)
        // Positive balance means credit (مديونية له)
        if ($this->credit_balance >= 0) {
            return 'text-success';
        } elseif (abs($this->credit_balance) >= $this->credit_limit) {
            return 'text-danger';
        }
        return 'text-warning';
    }

    public function getStatusText()
    {
        // Negative balance means debt (مديونية عليه)
        // Positive balance means credit (مديونية له)
        if ($this->credit_balance >= 0) {
            return 'لا يوجد مديونية';
        } elseif (abs($this->credit_balance) >= $this->credit_limit) {
            return 'تجاوز حد الائتمان';
        }
        return 'عليه مديونية';
    }

    /**
     * Get customer's default price type code
     * Returns customer's specific price type if set, otherwise returns null
     */
    public function getDefaultPriceTypeCode()
    {
        if ($this->defaultPriceType) {
            return $this->defaultPriceType->code;
        }
        return null;
    }

    /**
     * Check if customer has a specific default price type
     */
    public function hasDefaultPriceType()
    {
        return !is_null($this->default_price_type_id);
    }

    // Loyalty Points Methods

    /**
     * Get earned loyalty transactions
     */
    public function earnedLoyaltyTransactions()
    {
        return $this->loyaltyTransactions()->earned();
    }

    /**
     * Get redeemed loyalty transactions
     */
    public function redeemedLoyaltyTransactions()
    {
        return $this->loyaltyTransactions()->redeemed();
    }

    /**
     * Get total earned points
     */
    public function getTotalEarnedPoints(): int
    {
        return (int) $this->loyaltyTransactions()->earned()->sum('points');
    }

    /**
     * Get total redeemed points (absolute value)
     */
    public function getTotalRedeemedPoints(): int
    {
        return abs((int) $this->loyaltyTransactions()->redeemed()->sum('points'));
    }

    /**
     * Award loyalty points for an invoice
     */
    public function awardLoyaltyPoints(float $invoiceAmount, int $productCount, string $invoiceNumber): int
    {
        $settings = LoyaltySetting::getSettings();
        
        if (!$settings->is_active) {
            return 0;
        }

        $points = $settings->calculatePoints($invoiceAmount, $productCount);
        
        if ($points > 0) {
            LoyaltyTransaction::createEarned(
                $this->id,
                $points,
                'invoice',
                $invoiceNumber,
                "نقاط مكتسبة من الفاتورة رقم {$invoiceNumber}"
            );
        }

        return $points;
    }

    /**
     * Redeem points to balance
     */
    public function redeemPointsToBalance(int $points): float
    {
        $settings = LoyaltySetting::getSettings();
        
        if (!$settings->isRedemptionAllowed($points)) {
            throw new \Exception('لا يمكن استبدال هذا العدد من النقاط');
        }

        if ($this->total_loyalty_points < $points) {
            throw new \Exception('عدد النقاط المتاحة غير كافي');
        }

        $amount = $settings->pointsToCurrency($points);
        
        // Create redemption transaction
        LoyaltyTransaction::createRedeemed(
            $this->id,
            $points,
            'redemption_balance',
            $amount,
            null,
            "تحويل {$points} نقطة إلى رصيد بقيمة {$amount} جنيه"
        );

        // Add amount to customer balance (credit)
        $this->increment('credit_balance', $amount);

        return $amount;
    }

    /**
     * Calculate discount amount from points
     */
    public function calculateDiscountFromPoints(int $points): float
    {
        $settings = LoyaltySetting::getSettings();
        return $settings->pointsToCurrency($points);
    }

    /**
     * Calculate points needed for specific discount amount
     */
    public function calculatePointsForDiscount(float $discountAmount): int
    {
        $settings = LoyaltySetting::getSettings();
        return $settings->currencyToPoints($discountAmount);
    }

    /**
     * Apply points discount to invoice
     */
    public function applyPointsDiscount(int $points, string $invoiceNumber): float
    {
        $settings = LoyaltySetting::getSettings();
        
        if (!$settings->isRedemptionAllowed($points)) {
            throw new \Exception('لا يمكن استبدال هذا العدد من النقاط');
        }

        if ($this->total_loyalty_points < $points) {
            throw new \Exception('عدد النقاط المتاحة غير كافي');
        }

        $discountAmount = $settings->pointsToCurrency($points);
        
        // Create redemption transaction
        LoyaltyTransaction::createRedeemed(
            $this->id,
            $points,
            'redemption_discount',
            $discountAmount,
            $invoiceNumber,
            "خصم بقيمة {$discountAmount} جنيه من الفاتورة رقم {$invoiceNumber}"
        );

        return $discountAmount;
    }

    /**
     * Add or subtract points manually
     */
    public function adjustLoyaltyPoints(int $points, int $userId, string $reason): void
    {
        if ($points == 0) {
            throw new \Exception('يجب أن تكون النقاط أكبر أو أقل من صفر');
        }

        if ($points < 0 && $this->total_loyalty_points < abs($points)) {
            throw new \Exception('عدد النقاط المتاحة غير كافي للخصم');
        }

        LoyaltyTransaction::createManual($this->id, $points, $userId, $reason);
    }

    /**
     * Get available points for redemption (considering minimum requirements)
     */
    public function getAvailablePointsForRedemption(): int
    {
        $settings = LoyaltySetting::getSettings();
        
        if (!$settings->is_active || $this->total_loyalty_points < $settings->min_points_for_redemption) {
            return 0;
        }

        return $this->total_loyalty_points;
    }

    /**
     * Check if customer can redeem specific number of points
     */
    public function canRedeemPoints(int $points): bool
    {
        $settings = LoyaltySetting::getSettings();
        
        return $settings->is_active && 
               $this->total_loyalty_points >= $points && 
               $settings->isRedemptionAllowed($points);
    }

    /**
     * Get loyalty points status label
     */
    public function getLoyaltyStatusLabel(): string
    {
        $settings = LoyaltySetting::getSettings();
        
        if (!$settings->is_active) {
            return 'نظام النقاط غير مفعل';
        }

        if ($this->total_loyalty_points == 0) {
            return 'لا توجد نقاط';
        }

        if ($this->total_loyalty_points < $settings->min_points_for_redemption) {
            return 'نقاط غير كافية للاستبدال';
        }

        return 'نقاط قابلة للاستبدال';
    }
}
