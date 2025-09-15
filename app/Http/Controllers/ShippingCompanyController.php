<?php

namespace App\Http\Controllers;

use App\Models\ShippingCompany;
use App\Models\DeliveryTransaction;
use Illuminate\Http\Request;

class ShippingCompanyController extends Controller
{
    /**
     * إنشاء مثيل جديد من وحدة التحكم
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض قائمة شركات الشحن
     */
    public function index()
    {
        $companies = ShippingCompany::withCount('deliveryTransactions')
            ->orderBy('name')
            ->get();

        return view('shipping-companies.index', compact('companies'));
    }

    /**
     * عرض نموذج إنشاء شركة شحن جديدة
     */
    public function create()
    {
        return view('shipping-companies.create');
    }

    /**
     * تخزين شركة شحن جديدة في قاعدة البيانات
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'default_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $company = ShippingCompany::create($validated);

        return redirect()->route('shipping-companies.index')
            ->with('success', 'تم إضافة شركة الشحن بنجاح');
    }

    /**
     * عرض شركة شحن محددة
     */
    public function show(ShippingCompany $shippingCompany)
    {
        $deliveries = DeliveryTransaction::where('shipping_company_id', $shippingCompany->id)
            ->with(['customer', 'invoice', 'status'])
            ->latest()
            ->paginate(15);

        return view('shipping-companies.show', compact('shippingCompany', 'deliveries'));
    }

    /**
     * عرض نموذج تعديل شركة شحن
     */
    public function edit(ShippingCompany $shippingCompany)
    {
        return view('shipping-companies.edit', compact('shippingCompany'));
    }

    /**
     * تحديث شركة شحن محددة في قاعدة البيانات
     */
    public function update(Request $request, ShippingCompany $shippingCompany)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'default_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $shippingCompany->update($validated);

        return redirect()->route('shipping-companies.index')
            ->with('success', 'تم تحديث شركة الشحن بنجاح');
    }

    /**
     * حذف شركة شحن محددة من قاعدة البيانات
     */
    public function destroy(ShippingCompany $shippingCompany)
    {
        // التحقق من وجود معاملات توصيل مرتبطة بهذه الشركة
        $hasDeliveries = DeliveryTransaction::where('shipping_company_id', $shippingCompany->id)->exists();

        if ($hasDeliveries) {
            return redirect()->route('shipping-companies.index')
                ->with('error', 'لا يمكن حذف شركة الشحن لأنها مرتبطة بمعاملات توصيل');
        }

        $shippingCompany->delete();

        return redirect()->route('shipping-companies.index')
            ->with('success', 'تم حذف شركة الشحن بنجاح');
    }

    /**
     * تغيير حالة النشاط لشركة الشحن
     */
    public function toggleActive(ShippingCompany $shippingCompany)
    {
        $shippingCompany->is_active = !$shippingCompany->is_active;
        $shippingCompany->save();

        $status = $shippingCompany->is_active ? 'تفعيل' : 'تعطيل';

        return redirect()->route('shipping-companies.index')
            ->with('success', "تم {$status} شركة الشحن بنجاح");
    }

    /**
     * عرض تقرير عن شركات الشحن
     */
    public function report(Request $request)
    {
        $companies = ShippingCompany::withCount('deliveryTransactions')
            ->withSum('deliveryTransactions', 'shipping_cost')
            ->orderBy('name')
            ->get();

        $totalDeliveries = DeliveryTransaction::whereNotNull('shipping_company_id')->count();
        $totalShippingCost = DeliveryTransaction::whereNotNull('shipping_company_id')->sum('shipping_cost');

        return view('shipping-companies.report', compact('companies', 'totalDeliveries', 'totalShippingCost'));
    }
}
