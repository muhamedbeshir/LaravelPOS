<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\CustomerInvoicesExport;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExportController extends Controller
{
    /**
     * Export customer invoices to Excel
     * 
     * @param Request $request
     * @param int $customerId
     * @return \Illuminate\Http\Response
     */
    public function exportCustomerInvoices(Request $request, $customerId)
    {
        try {
            $validator = Validator::make(['customer_id' => $customerId], [
                'customer_id' => 'required|exists:customers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Customer not found',
                    'errors' => $validator->errors()
                ], 404);
            }

            $customer = Customer::findOrFail($customerId);
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $paymentStatus = $request->input('payment_status');
            
            $exporter = new CustomerInvoicesExport(
                $customer, 
                $startDate, 
                $endDate, 
                $paymentStatus
            );
            
            $filename = 'customer_' . $customer->id . '_invoices.xlsx';
            
            return $exporter->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export customer invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
