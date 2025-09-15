<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\SalaryPayment;
use App\Models\AttendanceRecord;
use App\Models\EmployeeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Events\EmployeeCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class EmployeeController extends Controller
{
    protected static array $middlewares = [
        'auth',
        'permission:view-employees' => ['only' => ['index', 'show']],
        'permission:create-employees' => ['only' => ['create', 'store']],
        'permission:edit-employees' => ['only' => ['edit', 'update']],
        'permission:delete-employees' => ['only' => ['destroy']],
    ];

    public function index()
    {
        $employees = Employee::with(['jobTitle', 'salaryPayments'])
            ->orderBy('name')
            ->get();
        $salaryDisplayFrequency = Setting::get('salary_display_frequency', 'monthly');
            
        return view('employees.index', compact('employees', 'salaryDisplayFrequency'));
    }

    public function create()
    {
        $jobTitles = JobTitle::where('is_active', true)->get();
        $salaryDisplayFrequency = Setting::get('salary_display_frequency', 'monthly');
        return view('employees.create', compact('jobTitles', 'salaryDisplayFrequency'));
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'employee_number' => 'required|string|max:50|unique:employees',
                'national_id' => 'nullable|string|size:14|unique:employees',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'job_title_id' => 'required|exists:job_titles,id',
                'salary' => 'required|numeric|min:0',
                'hire_date' => 'required|date',
                'role' => 'required|in:cashier,delivery,admin',
                'notes' => 'nullable|string|max:1000'
            ];

            $messages = [
                'name.required' => 'اسم الموظف مطلوب',
                'name.max' => 'اسم الموظف يجب ألا يتجاوز 255 حرف',
                'employee_number.required' => 'رقم الموظف مطلوب',
                'employee_number.unique' => 'رقم الموظف مستخدم من قبل',
                'national_id.size' => 'الرقم القومي يجب أن يتكون من 14 رقم',
                'national_id.unique' => 'الرقم القومي مستخدم من قبل',
                'job_title_id.required' => 'المسمى الوظيفي مطلوب',
                'job_title_id.exists' => 'المسمى الوظيفي غير صحيح',
                'salary.required' => 'الراتب مطلوب',
                'salary.numeric' => 'الراتب يجب أن يكون رقم',
                'salary.min' => 'الراتب يجب أن يكون أكبر من صفر',
                'hire_date.required' => 'تاريخ التعيين مطلوب',
                'hire_date.date' => 'تاريخ التعيين غير صحيح',
                'role.required' => 'الدور الوظيفي مطلوب',
                'role.in' => 'الدور الوظيفي غير صحيح'
            ];

            $validatedData = $request->validate($rules, $messages);

            DB::beginTransaction();

            // التحقق من المسمى الوظيفي
            $jobTitle = JobTitle::findOrFail($validatedData['job_title_id']);
            if (!$jobTitle->is_active) {
                throw new \Exception('المسمى الوظيفي غير نشط');
            }

            // إنشاء الموظف
            $employee = Employee::create([
                'name' => $validatedData['name'],
                'employee_number' => $validatedData['employee_number'],
                'national_id' => $validatedData['national_id'],
                'phone' => $validatedData['phone'],
                'address' => $validatedData['address'],
                'job_title_id' => $validatedData['job_title_id'],
                'salary' => $validatedData['salary'],
                'hire_date' => $validatedData['hire_date'],
                'role' => $validatedData['role'],
                'notes' => $validatedData['notes'],
                'is_active' => true
            ]);

            // إنشاء مستخدم للموظف
            $user = User::create([
                'name' => $employee->name,
                'email' => strtolower(str_replace(' ', '.', $employee->name)) . '@laravelpos.com',
                'password' => Hash::make('password123'),
                'employee_id' => $employee->id
            ]);

            DB::commit();

            return redirect()->route('employees.index')
                ->with('success', 'تم إضافة الموظف بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating employee: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء حفظ البيانات: ' . $e->getMessage());
        }
    }

    public function show(Employee $employee)
    {
        $employee->load(['jobTitle', 'salaryPayments', 'attendanceRecords']);
        
        if (request()->ajax()) {
            return response()->json([
                'employee' => $employee,
                'currentMonthPayment' => $employee->getCurrentMonthSalaryPayment(),
                'todayAttendance' => $employee->getTodayAttendance()
            ]);
        }
        
        $salaryDisplayFrequency = Setting::get('salary_display_frequency', 'monthly');
        if ($salaryDisplayFrequency === 'weekly') {
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            $employee->current_period_payment = $employee->salaryPayments()
                ->whereBetween('payment_date', [$startOfWeek, $endOfWeek])
                ->first();
        } else {
            $employee->current_period_payment = $employee->getSalaryPaymentForMonth(now()->year, now()->month);
        }
        
        return view('employees.show', compact('employee', 'salaryDisplayFrequency'));
    }

    public function edit(Employee $employee)
    {
        $jobTitles = JobTitle::where('is_active', true)->get();
        $salaryDisplayFrequency = Setting::get('salary_display_frequency', 'monthly');
        return view('employees.edit', compact('employee', 'jobTitles', 'salaryDisplayFrequency'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'employee_number' => 'required|string|unique:employees,employee_number,' . $employee->id,
            'address' => 'nullable|string',
            'national_id' => 'nullable|string',
            'salary' => 'required|numeric|min:0',
            'job_title_id' => 'required|exists:job_titles,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $oldSalary = $employee->salary;
            $employee->update($request->all());

            // إذا تغير الراتب، نضيف تنبيه
            if ($oldSalary != $employee->salary) {
                EmployeeNotification::create([
                    'employee_id' => $employee->id,
                    'title' => 'تغيير الراتب',
                    'message' => 'تم تغيير الراتب من ' . $oldSalary . ' إلى ' . $employee->salary,
                    'type' => 'info'
                ]);
            }

            DB::commit();
            return redirect()->route('employees.index')
                ->with('success', 'تم تحديث بيانات الموظف بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث بيانات الموظف: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            $employee->delete();

            DB::commit();
            return redirect()->route('employees.index')
                ->with('success', 'تم حذف الموظف بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف الموظف: ' . $e->getMessage());
        }
    }

    public function toggleActive(Employee $employee)
    {
        $employee->is_active = !$employee->is_active;
        $employee->save();

        return redirect()->back()
            ->with('success', 'تم تحديث حالة الموظف بنجاح');
    }

    public function salariesIndex()
    {
        $employees = Employee::with(['jobTitle', 'salaryPayments'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $jobTitles = JobTitle::where('is_active', true)->orderBy('name')->get();

        $settings = Setting::whereIn('key', ['salary_display_frequency', 'next_payment_date'])
            ->pluck('value', 'key');

        return view('employees.salaries', compact('employees', 'jobTitles', 'settings'));
    }

    public function getSalariesData(Request $request)
    {
        $query = Employee::with(['jobTitle', 'salaryPayments'])
            ->where('is_active', true);

        if ($request->filled('job_title_id')) {
            $query->where('job_title_id', $request->job_title_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('employee_number', 'like', "%{$searchTerm}%");
            });
        }
        
        // Clone the query for calculating total salaries before applying month/payment status filters for employees list
        $statsQuery = clone $query;

        $employees = $query->orderBy('name')->get();
        
        $month = $request->filled('month') ? \Carbon\Carbon::parse($request->month) : now();

        $filteredEmployees = $employees->map(function ($employee) use ($month) {
            $salaryDisplayFrequency = Setting::get('salary_display_frequency', 'monthly'); // Default to 'monthly'
            $employee->current_month_payment = $employee->getSalaryPaymentForMonth($month->year, $month->month);
            // Ensure deductions, bonuses, and net_salary are at least 0
            $employee->deductions = max(0, $employee->deductions ?? 0); // Assuming deductions might be a property or method
            $employee->bonuses = max(0, $employee->bonuses ?? 0); // Assuming bonuses might be a property or method

            if ($salaryDisplayFrequency === 'weekly') {
                $employee->weekly_salary = round($employee->salary / 4, 2); // Assuming 4 weeks in a month
                $employee->net_salary = max(0, $employee->weekly_salary + $employee->bonuses - $employee->deductions);
            } else {
                $employee->net_salary = max(0, $employee->salary + $employee->bonuses - $employee->deductions);
            }

            $lastPayment = $employee->salaryPayments()->latest('payment_date')->first();
            $currentDate = now();
            $nextPaymentDate = null;

            if ($salaryDisplayFrequency === 'monthly') {
                if ($lastPayment && $lastPayment->payment_date) {
                    $nextPaymentDate = (new \Carbon\Carbon($lastPayment->payment_date))->addMonth();
                } else {
                    $nextPaymentDate = $currentDate->copy()->endOfMonth();
                }
                if ($nextPaymentDate->isPast()) {
                    $nextPaymentDate = $currentDate->copy()->addMonth()->endOfMonth();
                }
            } elseif ($salaryDisplayFrequency === 'weekly') {
                if ($lastPayment && $lastPayment->payment_date) {
                    $nextPaymentDate = (new \Carbon\Carbon($lastPayment->payment_date))->addWeek();
                } else {
                    $nextPaymentDate = $currentDate->copy()->endOfWeek();
                }
                if ($nextPaymentDate->isPast()) {
                     $nextPaymentDate = $currentDate->copy()->addWeek()->endOfWeek();
                }
            }

            $employee->next_payment_date = $nextPaymentDate ? $nextPaymentDate->format('Y-m-d') : 'غير محدد';

            return $employee;
        });

        if ($request->filled('salary_status')) {
            $salaryStatus = $request->salary_status;
            $filteredEmployees = $filteredEmployees->filter(function ($employee) use ($salaryStatus) {
                return ($salaryStatus === 'paid' && $employee->current_month_payment) ||
                       ($salaryStatus === 'unpaid' && !$employee->current_month_payment);
            });
        }
        
        // Calculate stats based on the initially filtered (job_title, search) but not yet status/month filtered employees for accuracy.
        $allQueriedEmployees = $statsQuery->get()->map(function($employee) use ($month){
             $employee->current_month_payment = $employee->getSalaryPaymentForMonth($month->year, $month->month);
             return $employee;
        });
        
        $totalSalaries = $allQueriedEmployees->sum('salary');
        $paidEmployeesCount = $allQueriedEmployees->whereNotNull('current_month_payment')->count();
        $unpaidEmployeesCount = $allQueriedEmployees->whereNull('current_month_payment')->count();
        $totalQueriedEmployees = $allQueriedEmployees->count();
        $paymentRate = $totalQueriedEmployees > 0 ? round(($paidEmployeesCount / $totalQueriedEmployees) * 100) : 0;


        return response()->json([
            'employees' => $filteredEmployees->values(), // Ensure it's a simple array for DataTables
            'totalSalaries' => $totalSalaries,
            'paidEmployees' => $paidEmployeesCount,
            'unpaidEmployees' => $unpaidEmployeesCount,
            'paymentRate' => $paymentRate,
        ]);
    }

    public function paySalary(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bank_transfer,check',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // استخدام راتب الموظف الأساسي إذا لم يتم تحديد المبلغ
            $amount = $request->has('amount') && is_numeric($request->amount) ? 
                      $request->amount : $employee->salary;

            // التحقق من وجود سلف للموظف وخصمها إذا كان الإعداد مفعلاً
            $autoDeductAdvances = (bool) Setting::get('auto_deduct_advances', true);
            $deductedAmount = 0;
            $originalAmount = $amount;

            // إنشاء دفعة الراتب
            $payment = $employee->salaryPayments()->create([
                'amount' => $amount,
                'payment_date' => now(),
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes
            ]);

            // إذا كان إعداد خصم السلف مفعلاً، قم بخصم السلف المعلقة
            if ($autoDeductAdvances) {
                // الحصول على السلف المعلقة للموظف
                $pendingAdvances = $employee->advances()
                    ->where(function ($query) {
                        $query->where('status', 'pending')
                            ->orWhere('status', 'partially_paid');
                    })
                    ->where('is_deducted_from_salary', true)
                    ->orderBy('date')
                    ->get();

                // خصم السلف من الراتب
                foreach ($pendingAdvances as $advance) {
                    $remainingAdvance = $advance->remaining_amount;
                    
                    // إذا كان الراتب المتبقي أكبر من أو يساوي السلفة المتبقية، خصم السلفة بالكامل
                    if ($amount >= $remainingAdvance) {
                        $deductedFromAdvance = $remainingAdvance;
                        $amount -= $remainingAdvance;
                    } else {
                        // خصم جزء من السلفة حسب المبلغ المتبقي من الراتب
                        $deductedFromAdvance = $amount;
                        $amount = 0;
                    }
                    
                    if ($deductedFromAdvance > 0) {
                        // تحديث السلفة
                        $advance->deducted_amount += $deductedFromAdvance;
                        $advance->salary_payment_id = $payment->id;
                        $advance->updateStatus();
                        $advance->save();
                        
                        $deductedAmount += $deductedFromAdvance;
                    }
                    
                    // إذا لم يتبق مبلغ في الراتب، توقف عن الخصم
                    if ($amount <= 0) {
                        break;
                    }
                }
                
                // إذا تم خصم أي مبلغ، أضف ملاحظة إلى دفعة الراتب
                if ($deductedAmount > 0) {
                    $payment->notes = ($payment->notes ? $payment->notes . ' | ' : '') . 
                        "تم خصم {$deductedAmount} من السلف";
                    $payment->save();
                }
            }

            // إنشاء تنبيه بالدفع
            try {
                if (Schema::hasTable('employee_notifications')) {
                    EmployeeNotification::create([
                        'employee_id' => $employee->id,
                        'title' => 'دفع الراتب',
                        'message' => 'تم دفع الراتب: ' . $originalAmount . 
                            ($deductedAmount > 0 ? " (تم خصم {$deductedAmount} من السلف)" : ""),
                        'type' => 'salary_paid'
                    ]);
                }
            } catch (\Exception $e) {
                // Continue even if notification creation fails
                Log::error('Failed to create employee notification: ' . $e->getMessage());
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'تم دفع الراتب بنجاح' . 
                    ($deductedAmount > 0 ? " وخصم {$deductedAmount} من السلف" : ""),
                'payment' => $payment,
                'deducted_amount' => $deductedAmount
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء دفع الراتب: ' . $e->getMessage()
            ], 500);
        }
    }

    public function payMultipleSalaries(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'required|exists:employees,id',
            'payment_method' => 'required|string|in:cash,bank_transfer,check',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $successCount = 0;
            $errors = [];
            $totalDeducted = 0;
            
            // Default payment method if not provided
            $paymentMethod = $request->input('payment_method', 'cash');
            $referenceNumber = $request->input('reference_number');
            $notes = $request->input('notes', 'دفع راتب آلي');
            
            // التحقق من إعداد خصم السلف
            $autoDeductAdvances = (bool) Setting::get('auto_deduct_advances', true);

            foreach ($request->employee_ids as $employeeId) {
                $employee = Employee::findOrFail($employeeId);
                
                // Skip employees who already received their salary for the current month
                if ($employee->getCurrentMonthSalaryPayment()) {
                    $errors[] = "الموظف {$employee->name} تم دفع راتبه بالفعل هذا الشهر";
                    continue;
                }

                // المبلغ الأصلي للراتب
                $salaryAmount = $employee->salary;
                
                // Create the salary payment
                $payment = $employee->salaryPayments()->create([
                    'amount' => $salaryAmount, // Using the employee's base salary
                    'payment_date' => now(),
                    'payment_method' => $paymentMethod,
                    'reference_number' => $referenceNumber,
                    'notes' => $notes
                ]);
                
                // خصم السلف من الراتب إذا كان الإعداد مفعلاً
                $deductedAmount = 0;
                if ($autoDeductAdvances) {
                    // الحصول على السلف المعلقة للموظف
                    $pendingAdvances = $employee->advances()
                        ->where(function ($query) {
                            $query->where('status', 'pending')
                                ->orWhere('status', 'partially_paid');
                        })
                        ->where('is_deducted_from_salary', true)
                        ->orderBy('date')
                        ->get();
                    
                    // المبلغ المتبقي للخصم
                    $remainingSalary = $salaryAmount;
                    
                    // خصم السلف من الراتب
                    foreach ($pendingAdvances as $advance) {
                        $remainingAdvance = $advance->remaining_amount;
                        
                        // إذا كان الراتب المتبقي أكبر من أو يساوي السلفة المتبقية، خصم السلفة بالكامل
                        if ($remainingSalary >= $remainingAdvance) {
                            $deductedFromAdvance = $remainingAdvance;
                            $remainingSalary -= $remainingAdvance;
                        } else {
                            // خصم جزء من السلفة حسب المبلغ المتبقي من الراتب
                            $deductedFromAdvance = $remainingSalary;
                            $remainingSalary = 0;
                        }
                        
                        if ($deductedFromAdvance > 0) {
                            // تحديث السلفة
                            $advance->deducted_amount += $deductedFromAdvance;
                            $advance->salary_payment_id = $payment->id;
                            $advance->updateStatus();
                            $advance->save();
                            
                            $deductedAmount += $deductedFromAdvance;
                        }
                        
                        // إذا لم يتبق مبلغ في الراتب، توقف عن الخصم
                        if ($remainingSalary <= 0) {
                            break;
                        }
                    }
                    
                    // إذا تم خصم أي مبلغ، أضف ملاحظة إلى دفعة الراتب
                    if ($deductedAmount > 0) {
                        $payment->notes = ($payment->notes ? $payment->notes . ' | ' : '') . 
                            "تم خصم {$deductedAmount} من السلف";
                        $payment->save();
                        $totalDeducted += $deductedAmount;
                    }
                }

                // Create a notification for the payment
                try {
                    if (Schema::hasTable('employee_notifications')) {
                        EmployeeNotification::create([
                            'employee_id' => $employee->id,
                            'title' => 'دفع الراتب',
                            'message' => 'تم دفع الراتب: ' . $salaryAmount . 
                                ($deductedAmount > 0 ? " (تم خصم {$deductedAmount} من السلف)" : ""),
                            'type' => 'salary_paid'
                        ]);
                    }
                } catch (\Exception $e) {
                    // Continue even if notification creation fails
                    Log::error('Failed to create employee notification: ' . $e->getMessage());
                }

                $successCount++;
            }

            DB::commit();
            
            $message = "تم دفع الرواتب بنجاح لـ {$successCount} موظف";
            if ($totalDeducted > 0) {
                $message .= " وخصم إجمالي {$totalDeducted} من السلف";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'errors' => $errors,
                'total_deducted' => $totalDeducted
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء دفع الرواتب: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get salary payment history for an employee
     */
    public function getSalaryHistory(Employee $employee)
    {
        try {
            $payments = $employee->salaryPayments()
                ->orderBy('payment_date', 'desc')
                ->get()
                ->map(function ($payment) {
                    $payment->payment_method_text = $payment->getPaymentMethodText();
                    return $payment;
                });

            return response()->json([
                'success' => true,
                'payments' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل سجل المدفوعات: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkIn(Employee $employee)
    {
        try {
            \Log::info('تم استدعاء وظيفة تسجيل الحضور للموظف: ' . $employee->id);
            
            if ($employee->isCheckedIn()) {
                \Log::info('الموظف مسجل حضور بالفعل: ' . $employee->id);
                return response()->json([
                    'success' => false,
                    'message' => 'الموظف مسجل حضور بالفعل'
                ], 422);
            }

            \Log::info('جاري إنشاء سجل حضور جديد للموظف: ' . $employee->id);
            $now = now();
            $record = $employee->attendanceRecords()->create([
                'date' => $now->toDateString(),
                'check_in' => $now->toTimeString()
            ]);
            \Log::info('تم إنشاء سجل الحضور بنجاح: ' . json_encode($record));

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الحضور بنجاح',
                'record' => $record
            ]);

        } catch (\Exception $e) {
            \Log::error('Error checking in employee ID ' . $employee->id . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الحضور: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkOut(Employee $employee)
    {
        try {
            $record = $employee->getTodayAttendance();

            if (!$record || !$record->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد تسجيل حضور نشط'
                ], 422);
            }

            $record->update([
                'check_out' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الانصراف بنجاح',
                'record' => $record
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الانصراف: ' . $e->getMessage()
            ], 500);
        }
    }

    public function attendanceIndex()
    {
        $employees = Employee::with(['jobTitle', 'attendanceRecords'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $jobTitles = \App\Models\JobTitle::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return view('employees.attendance', compact('employees', 'jobTitles'));
    }

    public function reports()
    {
        $totalEmployees = Employee::where('is_active', true)->count();
        $totalSalaries = Employee::where('is_active', true)->sum('salary');
        $paidSalaries = SalaryPayment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');
        
        $employeesByJob = Employee::with('jobTitle')
            ->where('is_active', true)
            ->get()
            ->groupBy('jobTitle.name');
            
        $jobTitles = JobTitle::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $employees = Employee::with(['jobTitle', 'salaryPayments', 'attendanceRecords'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return view('employees.reports', compact(
            'totalEmployees',
            'totalSalaries',
            'paidSalaries',
            'employeesByJob',
            'jobTitles',
            'employees'
        ));
    }

    public function export()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // تعيين اتجاه الورقة من اليمين لليسار
            $sheet->setRightToLeft(true);

            // تنسيق العناوين
            $sheet->getStyle('A1:H1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);

            // إضافة العناوين
            $headers = [
                'اسم الموظف',
                'رقم الموظف',
                'الوظيفة',
                'العنوان',
                'الرقم القومي',
                'الراتب',
                'الحالة',
                'ملاحظات'
            ];
            $sheet->fromArray([$headers], NULL, 'A1');

            // جلب البيانات
            $employees = Employee::with('jobTitle')->get();
            $row = 2;
            foreach ($employees as $employee) {
                $data = [
                    $employee->name,
                    $employee->employee_number,
                    $employee->jobTitle->name,
                    $employee->address ?? '-',
                    $employee->national_id ?? '-',
                    number_format($employee->salary, 2),
                    $employee->is_active ? 'نشط' : 'غير نشط',
                    $employee->notes ?? '-'
                ];
                
                $sheet->fromArray([$data], NULL, 'A' . $row);
                
                // تنسيق الصف
                $sheet->getStyle('A'.$row.':H'.$row)->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $row++;
            }

            // تعديل عرض الأعمدة
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // إنشاء ملف Excel
            $writer = new Xlsx($spreadsheet);
            $fileName = 'employees_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تصدير البيانات: ' . $e->getMessage());
        }
    }

    public function getNotifications()
    {
        $notifications = EmployeeNotification::with('employee')
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }
    
    /**
     * Get reports data for AJAX request
     */
    public function getReportsData(Request $request)
    {
        $query = Employee::with(['jobTitle', 'salaryPayments', 'attendanceRecords'])
            ->where('is_active', true);
            
        // Filter by job title
        if ($request->filled('job_title_id')) {
            $query->where('job_title_id', $request->job_title_id);
        }
        
        // Filter by date range
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        if ($request->date_range === 'today') {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($request->date_range === 'week') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } elseif ($request->date_range === 'month') {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        } elseif ($request->date_range === 'year') {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
        } elseif ($request->date_range === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
        }
        
        $employees = $query->get();
        
        // Calculate total salaries
        $totalSalaries = $employees->sum('salary');
        
        // Calculate average salary
        $averageSalary = $employees->count() > 0 ? $totalSalaries / $employees->count() : 0;
        
        // Calculate attendance rate
        $totalWorkDays = \Carbon\Carbon::parse($startDate)->diffInDaysFiltered(function(\Carbon\Carbon $date) {
            return $date->isWeekday();
        }, \Carbon\Carbon::parse($endDate));
        
        $totalExpectedAttendance = $employees->count() * $totalWorkDays;
        $actualAttendance = AttendanceRecord::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('check_in', [$startDate, $endDate])
            ->count();
            
        $attendanceRate = $totalExpectedAttendance > 0 ? round(($actualAttendance / $totalExpectedAttendance) * 100) : 0;
        
        // Employees by job title
        $employeesByJobTitle = $employees->groupBy('jobTitle.name')->map->count();
        
        // Daily attendance
        $dailyAttendance = [];
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $count = AttendanceRecord::whereIn('employee_id', $employees->pluck('id'))
                ->whereDate('check_in', $date)
                ->count();
                
            $dailyAttendance[$date->format('Y-m-d')] = $count;
        }
        
        // Salary distribution
        $salaryRanges = [
            '< 1000' => [0, 999],
            '1000-2000' => [1000, 1999],
            '2000-3000' => [2000, 2999],
            '3000-4000' => [3000, 3999],
            '4000-5000' => [4000, 4999],
            '> 5000' => [5000, PHP_INT_MAX]
        ];
        
        $salaryDistribution = [];
        foreach ($salaryRanges as $label => [$min, $max]) {
            $salaryDistribution[$label] = $employees->filter(function ($employee) use ($min, $max) {
                return $employee->salary >= $min && $employee->salary <= $max;
            })->count();
        }
        
        // Monthly salary payments
        $monthlySalaryPayments = [];
        $currentMonth = \Carbon\Carbon::parse($startDate)->startOfMonth();
        $lastMonth = \Carbon\Carbon::parse($endDate)->startOfMonth();
        
        while ($currentMonth->lte($lastMonth)) {
            $amount = SalaryPayment::whereIn('employee_id', $employees->pluck('id'))
                ->whereYear('payment_date', $currentMonth->year)
                ->whereMonth('payment_date', $currentMonth->month)
                ->sum('amount');
                
            $monthlySalaryPayments[$currentMonth->format('Y-m')] = $amount;
            $currentMonth->addMonth();
        }
        
        return response()->json([
            'totalEmployees' => $employees->count(),
            'totalSalaries' => $totalSalaries,
            'averageSalary' => $averageSalary,
            'attendanceRate' => $attendanceRate,
            'employeesByJobTitle' => [
                'labels' => $employeesByJobTitle->keys()->toArray(),
                'values' => $employeesByJobTitle->values()->toArray()
            ],
            'dailyAttendance' => [
                'labels' => array_keys($dailyAttendance),
                'values' => array_values($dailyAttendance)
            ],
            'salaryDistribution' => [
                'labels' => array_keys($salaryDistribution),
                'values' => array_values($salaryDistribution)
            ],
            'monthlySalaryPayments' => [
                'labels' => array_keys($monthlySalaryPayments),
                'values' => array_values($monthlySalaryPayments)
            ]
        ]);
    }
} 