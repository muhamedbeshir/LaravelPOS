<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ReturnItem; // To calculate previously returned quantities
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load([
            'items.product:id,name,main_unit_id',
            'items.product.main_unit:id,name',
            'items.unit:id,name',
            'customer:id,name',
            'user:id,name'
        ]);

        foreach ($invoice->items as $item) {
            if (!$item->unit && $item->product && $item->product->main_unit_id) {
                $item->setRelation('unit', $item->product->main_unit);
                $item->unit_id = $item->product->main_unit_id;
            }

            $previouslyReturnedQuantity = ReturnItem::whereHas('salesReturn', function ($query) use ($invoice) {
                $query->where('invoice_id', $invoice->id);
            })
            ->where('product_id', $item->product_id)
            ->where('unit_id', $item->unit_id)
            ->sum('quantity_returned');
            $item->previously_returned_quantity = (float) $previouslyReturnedQuantity;
            $item->available_for_return = $item->quantity - $item->previously_returned_quantity;
        }
        return response()->json(['success' => true, 'invoice' => $invoice]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Search for invoices by reference number.
     * Optionally includes items with unit details and calculates previously returned quantities.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'reference_no' => 'required|string|min:1',
            'with_items' => 'sometimes|boolean',
            'with_units' => 'sometimes|boolean',
            'calculate_returnable' => 'sometimes|boolean',
        ]);

        $referenceNo = $request->input('reference_no');
        $withItems = filter_var($request->input('with_items', true), FILTER_VALIDATE_BOOLEAN);
        $withUnits = filter_var($request->input('with_units', false), FILTER_VALIDATE_BOOLEAN);
        $calculateReturnable = filter_var($request->input('calculate_returnable', false), FILTER_VALIDATE_BOOLEAN);

        $query = Invoice::where('reference_no', $referenceNo);

        if ($withItems) {
            $relationsToLoad = ['customer:id,name'];
            $itemRelations = 'items';

            if ($withUnits) {
                $itemRelations .= '.product:id,name,main_unit_id';
                $itemRelations .= '.product.main_unit:id,name';
                $itemRelations .= '.unit:id,name';
            } else {
                $itemRelations .= '.product:id,name';
            }
            $relationsToLoad[] = $itemRelations;
            $query->with($relationsToLoad);
        } else {
            $query->with('customer:id,name');
        }

        $invoice = $query->first();

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'لم يتم العثور على الفاتورة.'], 404);
        }

        if ($withItems && $calculateReturnable && $invoice->items) {
            foreach ($invoice->items as $item) {
                if ($withUnits && !$item->unit_id && $item->product && $item->product->main_unit_id) {
                    $item->unit_id = $item->product->main_unit_id;
                }
                if ($withUnits && !$item->relationLoaded('unit') && $item->unit_id && $item->product && $item->product->relationLoaded('main_unit') && $item->product->main_unit_id === $item->unit_id) {
                    $item->setRelation('unit', $item->product->main_unit);
                }

                $previouslyReturnedQuantity = ReturnItem::whereHas('salesReturn', function ($query) use ($invoice) {
                    $query->where('invoice_id', $invoice->id);
                })
                ->where('product_id', $item->product_id);

                if ($item->unit_id) {
                    $previouslyReturnedQuantity->where('unit_id', $item->unit_id);
                }
                
                $sumReturned = $previouslyReturnedQuantity->sum('quantity_returned');
                $item->previously_returned_quantity = (float) $sumReturned;
                $item->available_for_return = (float) $item->quantity - $item->previously_returned_quantity;
            }
        }

        return response()->json(['success' => true, 'invoice' => $invoice]);
    }
}
