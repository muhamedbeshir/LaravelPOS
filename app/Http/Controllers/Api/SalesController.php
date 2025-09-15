<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesController extends Controller
{
    /**
     * Get today's sales settlement data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodaySettlement()
    {
        try {
            // Get today's date range
            $today = Carbon::today();
            $tomorrow = Carbon::tomorrow();
            
            // Query for today's sales data
            $data = DB::table('invoices')
                ->where('created_at', '>=', $today)
                ->where('created_at', '<', $tomorrow)
                ->select([
                    DB::raw('SUM(total_amount) as total_sales'),
                    DB::raw('COUNT(*) as invoice_count'),
                    DB::raw('SUM(CASE WHEN type = "cash" THEN paid_amount ELSE 0 END) as total_received'),
                    DB::raw('SUM(CASE WHEN type = "credit" THEN total_amount ELSE 0 END) as total_credit')
                ])
                ->first();
            
            // Format response
            return response()->json([
                'success' => true,
                'total_sales' => $data->total_sales ?? 0,
                'invoice_count' => $data->invoice_count ?? 0,
                'total_received' => $data->total_received ?? 0,
                'total_credit' => $data->total_credit ?? 0
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getting settlement data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settlement data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 