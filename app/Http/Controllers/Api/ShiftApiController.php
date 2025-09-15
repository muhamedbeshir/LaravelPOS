<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Invoice;
use Illuminate\Http\Request;

class ShiftApiController extends Controller
{
    /**
     * Get multiple payment invoices for a shift
     *
     * @param Shift $shift
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMultiplePaymentInvoices(Shift $shift)
    {
        $invoices = Invoice::where('shift_id', $shift->id)
            ->whereIn('type', ['mixed', 'multiple_payment'])
            ->whereIn('status', ['paid', 'completed'])
            ->with(['customer', 'payments'])
            ->get();
            
        return response()->json([
            'success' => true,
            'invoices' => $invoices
        ]);
    }
} 