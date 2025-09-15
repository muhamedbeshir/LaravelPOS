<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LoyaltySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'earning_method',
        'points_per_invoice',
        'points_per_amount',
        'points_per_product',
        'points_to_currency_rate',
        'max_redemption_per_transaction',
        'min_points_for_redemption',
        'allow_full_discount',
        'is_active'
    ];

    protected $casts = [
        'points_per_amount' => 'decimal:2',
        'points_per_invoice' => 'integer',
        'points_per_product' => 'integer',
        'points_to_currency_rate' => 'integer',
        'max_redemption_per_transaction' => 'integer',
        'min_points_for_redemption' => 'integer',
        'allow_full_discount' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Get the singleton loyalty settings instance
     */
    public static function getSettings(): self
    {
        return self::first() ?? self::create([
            'earning_method' => 'per_amount',
            'points_per_invoice' => 10,
            'points_per_amount' => 1.00,
            'points_per_product' => 5,
            'points_to_currency_rate' => 10,
            'max_redemption_per_transaction' => null,
            'min_points_for_redemption' => 50,
            'allow_full_discount' => true,
            'is_active' => true
        ]);
    }

    /**
     * Calculate points for an invoice based on current settings
     */
    public function calculatePoints(float $invoiceAmount, int $productCount): int
    {
        if (!$this->is_active) {
            return 0;
        }

        return match ($this->earning_method) {
            'per_invoice' => $this->points_per_invoice,
            'per_amount' => (int) floor($invoiceAmount / $this->points_per_amount),
            'per_product' => $productCount * $this->points_per_product,
            default => 0
        };
    }

    /**
     * Convert points to currency amount
     */
    public function pointsToCurrency(int $points): float
    {
        return $points / $this->points_to_currency_rate;
    }

    /**
     * Convert currency amount to points
     */
    public function currencyToPoints(float $amount): int
    {
        return (int) ceil($amount * $this->points_to_currency_rate);
    }

    /**
     * Check if redemption amount is within limits
     */
    public function isRedemptionAllowed(int $points): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($points < $this->min_points_for_redemption) {
            return false;
        }

        if ($this->max_redemption_per_transaction && $points > $this->max_redemption_per_transaction) {
            return false;
        }

        return true;
    }

    /**
     * Get earning method label in Arabic
     */
    public function getEarningMethodLabelAttribute(): string
    {
        return match ($this->earning_method) {
            'per_invoice' => 'لكل فاتورة',
            'per_amount' => 'لكل مبلغ',
            'per_product' => 'لكل منتج',
            default => 'غير محدد'
        };
    }
}
