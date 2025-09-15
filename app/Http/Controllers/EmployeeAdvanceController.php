<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeAdvanceController extends Controller
{
    /**
     * عرض قائمة السلف
     */
    public function index()
    {
        $this->authorize('view-employee-advances');
        
        $advances = EmployeeAdvance::with(['employee', 'creator'])
            ->orderBy('date', 'desc')
            ->paginate(15);
            
        return view('employees.advances.index', compact('advances'));
    }

    /**
     * عرض نموذج إنشاء سلفة جديدة
     */
    public function create()
    {
        $this->authorize('create-employee-advances');
        
        $employees = Employee::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $autoDeductAdvances = (bool) Setting::get('auto_deduct_advances', true);
        
        return view('employees.advances.create', compact('employees', 'autoDeductAdvances'));
    }

    /**
     * تخزين سلفة جديدة
     */
    public function store(Request $request)
    {
        $this->authorize('create-employee-advances');
        
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'repayment_date' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
        ]);
        
        // معالجة حقل is_deducted_from_salary بشكل منفصل
        $validated['is_deducted_from_salary'] = $request->has('is_deducted_from_salary') ? true : false;
        $validated['created_by'] = Auth::id();
        $validated['status'] = 'pending';
        $validated['deducted_amount'] = 0; // تأكد من تعيين قيمة افتراضية
        
        DB::beginTransaction();
        
        try {
            // إنشاء السلفة
            $advance = EmployeeAdvance::create($validated);
            
            // تحديث رصيد الموظف
            $employee = Employee::findOrFail($validated['employee_id']);
            $employee->addToBalance($validated['amount']);
            
            DB::commit();
            
            return redirect()->route('employee-advances.index')
                ->with('success', 'تم تسجيل السلفة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تسجيل السلفة: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل سلفة
     */
    public function show(EmployeeAdvance $employeeAdvance)
    {
        $this->authorize('view-employee-advances');
        
        $employeeAdvance->load(['employee', 'creator', 'salaryPayment']);
        
        return view('employees.advances.show', compact('employeeAdvance'));
    }

    /**
     * عرض نموذج تعديل سلفة
     */
    public function edit(EmployeeAdvance $employeeAdvance)
    {
        $this->authorize('edit-employee-advances');
        
        if ($employeeAdvance->status === 'paid') {
            return redirect()->route('employee-advances.show', $employeeAdvance)
                ->with('warning', 'لا يمكن تعديل سلفة مدفوعة بالكامل');
        }
        
        $employees = Employee::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $autoDeductAdvances = (bool) Setting::get('auto_deduct_advances', true);
        
        return view('employees.advances.edit', compact('employeeAdvance', 'employees', 'autoDeductAdvances'));
    }

    /**
     * تحديث سلفة
     */
    public function update(Request $request, EmployeeAdvance $employeeAdvance)
    {
        $this->authorize('edit-employee-advances');
        
        if ($employeeAdvance->status === 'paid') {
            return redirect()->route('employee-advances.show', $employeeAdvance)
                ->with('warning', 'لا يمكن تعديل سلفة مدفوعة بالكامل');
        }
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'repayment_date' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
        ]);
        
        // معالجة حقل is_deducted_from_salary بشكل منفصل
        $validated['is_deducted_from_salary'] = $request->has('is_deducted_from_salary') ? true : false;
        
        DB::beginTransaction();
        
        try {
            // حساب الفرق في المبلغ
            $amountDifference = $validated['amount'] - $employeeAdvance->amount;
            
            // تحديث السلفة
            $employeeAdvance->update($validated);
            
            // إذا تغير المبلغ، قم بتحديث رصيد الموظف
            if ($amountDifference != 0) {
                $employee = $employeeAdvance->employee;
                $employee->addToBalance($amountDifference);
            }
            
            // تحديث حالة السلفة
            $employeeAdvance->updateStatus();
            
            DB::commit();
            
            return redirect()->route('employee-advances.index')
                ->with('success', 'تم تحديث السلفة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث السلفة: ' . $e->getMessage());
        }
    }

    /**
     * حذف سلفة
     */
    public function destroy(EmployeeAdvance $employeeAdvance)
    {
        $this->authorize('delete-employee-advances');
        
        if ($employeeAdvance->status !== 'pending') {
            return redirect()->route('employee-advances.index')
                ->with('warning', 'لا يمكن حذف سلفة تم خصم جزء منها أو تم دفعها بالكامل');
        }
        
        DB::beginTransaction();
        
        try {
            // استرجاع المبلغ من رصيد الموظف
            $employee = $employeeAdvance->employee;
            $employee->addToBalance(-$employeeAdvance->amount);
            
            // حذف السلفة
            $employeeAdvance->delete();
            
            DB::commit();
            
            return redirect()->route('employee-advances.index')
                ->with('success', 'تم حذف السلفة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->with('error', 'حدث خطأ أثناء حذف السلفة: ' . $e->getMessage());
        }
    }
    
    /**
     * عرض السلف الخاصة بموظف معين
     */
    public function employeeAdvances(Employee $employee)
    {
        $this->authorize('view-employee-advances');
        
        $advances = $employee->advances()
            ->with(['creator', 'salaryPayment'])
            ->orderBy('date', 'desc')
            ->get();
            
        return view('employees.advances.employee_advances', compact('employee', 'advances'));
    }
    
    /**
     * سداد السلفة يدويًا
     */
    public function repay(EmployeeAdvance $employeeAdvance)
    {
        $this->authorize('edit-employee-advances');
        
        if ($employeeAdvance->status === 'paid') {
            return redirect()->route('employee-advances.show', $employeeAdvance)
                ->with('warning', 'هذه السلفة مدفوعة بالكامل بالفعل');
        }
        
        DB::beginTransaction();
        
        try {
            // حساب المبلغ المتبقي
            $remainingAmount = $employeeAdvance->remaining_amount;
            
            // تحديث المبلغ المخصوم
            $employeeAdvance->deducted_amount = $employeeAdvance->amount;
            $employeeAdvance->status = 'paid';
            $employeeAdvance->save();
            
            // استرجاع المبلغ من رصيد الموظف (خصم المبلغ المتبقي)
            $employee = $employeeAdvance->employee;
            $employee->addToBalance(-$remainingAmount);
            
            DB::commit();
            
            return redirect()->route('employee-advances.show', $employeeAdvance)
                ->with('success', 'تم سداد السلفة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->with('error', 'حدث خطأ أثناء سداد السلفة: ' . $e->getMessage());
        }
    }
}
