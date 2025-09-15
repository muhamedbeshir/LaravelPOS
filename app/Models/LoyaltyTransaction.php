<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'points',
        'source_type',
        'reference_id',
        'description',
        'redeemed_amount',
        'balance_after',
        'user_id'
    ];

    protected $casts = [
        'points' => 'integer',
        'redeemed_amount' => 'decimal:2',
        'balance_after' => 'integer',
        'customer_id' => 'integer',
        'user_id' => 'integer'
    ];

    /**
     * Get the customer that owns the transaction
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user that created the transaction (for manual transactions)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the invoice if this transaction is related to an invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'reference_id');
    }

    /**
     * Scope for earned points
     */
    public function scopeEarned($query)
    {
        return $query->whereIn('type', ['earned', 'manual_add']);
    }

    /**
     * Scope for redeemed points
     */
    public function scopeRedeemed($query)
    {
        return $query->whereIn('type', ['redeemed', 'manual_subtract']);
    }

    /**
     * Scope for manual transactions
     */
    public function scopeManual($query)
    {
        return $query->whereIn('type', ['manual_add', 'manual_subtract']);
    }

    /**
     * Scope for automatic transactions (from invoices)
     */
    public function scopeAutomatic($query)
    {
        return $query->whereIn('type', ['earned', 'redeemed']);
    }

    /**
     * Get transaction type label in Arabic
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'earned' => 'مكتسبة',
            'redeemed' => 'مستبدلة',
            'manual_add' => 'إضافة يدوية',
            'manual_subtract' => 'خصم يدوي',
            default => 'غير محدد'
        };
    }

    /**
     * Get source type label in Arabic
     */
    public function getSourceTypeLabelAttribute(): string
    {
        return match ($this->source_type) {
            'invoice' => 'فاتورة',
            'manual' => 'يدوي',
            'redemption_balance' => 'تحويل إلى رصيد',
            'redemption_discount' => 'خصم على الفاتورة',
            default => 'غير محدد'
        };
    }

    /**
     * Get formatted points with sign
     */
    public function getFormattedPointsAttribute(): string
    {
        $sign = $this->points >= 0 ? '+' : '';
        return $sign . number_format($this->points);
    }

    /**
     * Check if transaction is a points earning transaction
     */
    public function isEarning(): bool
    {
        return in_array($this->type, ['earned', 'manual_add']);
    }

    /**
     * Check if transaction is a points redemption transaction
     */
    public function isRedemption(): bool
    {
        return in_array($this->type, ['redeemed', 'manual_subtract']);
    }

    /**
     * Check if transaction was created manually
     */
    public function isManual(): bool
    {
        return in_array($this->type, ['manual_add', 'manual_subtract']);
    }

    /**
     * Create a new earned points transaction
     */
    public static function createEarned(
        int $customerId,
        int $points,
        string $sourceType,
        ?string $referenceId = null,
        ?string $description = null
    ): self {
        $customer = Customer::findOrFail($customerId);
        $newBalance = $customer->total_loyalty_points + $points;
        
        $transaction = self::create([
            'customer_id' => $customerId,
            'type' => 'earned',
            'points' => $points,
            'source_type' => $sourceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'balance_after' => $newBalance
        ]);

        // Update customer's total points
        $customer->update(['total_loyalty_points' => $newBalance]);

        return $transaction;
    }

    /**
     * Create a new redeemed points transaction
     */
    public static function createRedeemed(
        int $customerId,
        int $points,
        string $sourceType,
        ?float $redeemedAmount = null,
        ?string $referenceId = null,
        ?string $description = null
    ): self {
        $customer = Customer::findOrFail($customerId);
        $newBalance = $customer->total_loyalty_points - $points;
        
        $transaction = self::create([
            'customer_id' => $customerId,
            'type' => 'redeemed',
            'points' => -$points, // Store as negative for redemption
            'source_type' => $sourceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'redeemed_amount' => $redeemedAmount,
            'balance_after' => $newBalance
        ]);

        // Update customer's total points
        $customer->update(['total_loyalty_points' => $newBalance]);

        return $transaction;
    }

    /**
     * Create a manual transaction (add or subtract)
     */
    public static function createManual(
        int $customerId,
        int $points,
        int $userId,
        string $description
    ): self {
        $customer = Customer::findOrFail($customerId);
        $type = $points >= 0 ? 'manual_add' : 'manual_subtract';
        $newBalance = $customer->total_loyalty_points + $points;
        
        $transaction = self::create([
            'customer_id' => $customerId,
            'type' => $type,
            'points' => $points,
            'source_type' => 'manual',
            'description' => $description,
            'balance_after' => $newBalance,
            'user_id' => $userId
        ]);

        // Update customer's total points
        $customer->update(['total_loyalty_points' => $newBalance]);

        return $transaction;
    }
}
