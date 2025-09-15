<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EmployeeApiController extends Controller
{
    /**
     * Get all employees
     */
    public function getAllEmployees(Request $request)
    {
        try {
            $query = Employee::with(['jobTitle']);
            
            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            // Filter by job title
            if ($request->has('job_title_id')) {
                $query->where('job_title_id', $request->input('job_title_id'));
            }
            
            // Filter by delivery role
            if ($request->has('is_delivery')) {
                $query->where('is_delivery', $request->boolean('is_delivery'));
            }
            
            // Search by name or phone
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Order by name
            $query->orderBy('name');
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $employees = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'employees' => $employees
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific employee
     */
    public function getEmployee($id)
    {
        try {
            $employee = Employee::with(['jobTitle', 'attendances' => function($query) {
                $query->orderBy('date', 'desc')->limit(30);
            }, 'salaries' => function($query) {
                $query->orderBy('payment_date', 'desc')->limit(12);
            }])->findOrFail($id);
            
            // Add attendance summary for current month
            $currentMonth = Carbon::now()->format('Y-m');
            $attendanceSummary = [
                'month' => $currentMonth,
                'present_days' => $employee->attendances()
                    ->whereYear('date', Carbon::now()->year)
                    ->whereMonth('date', Carbon::now()->month)
                    ->where('status', 'present')
                    ->count(),
                'absent_days' => $employee->attendances()
                    ->whereYear('date', Carbon::now()->year)
                    ->whereMonth('date', Carbon::now()->month)
                    ->where('status', 'absent')
                    ->count(),
                'late_days' => $employee->attendances()
                    ->whereYear('date', Carbon::now()->year)
                    ->whereMonth('date', Carbon::now()->month)
                    ->where('status', 'late')
                    ->count(),
                'total_working_hours' => $employee->attendances()
                    ->whereYear('date', Carbon::now()->year)
                    ->whereMonth('date', Carbon::now()->month)
                    ->whereNotNull('check_in')
                    ->whereNotNull('check_out')
                    ->sum(DB::raw('TIMESTAMPDIFF(HOUR, check_in, check_out)'))
            ];
            
            return response()->json([
                'success' => true,
                'employee' => $employee,
                'attendance_summary' => $attendanceSummary
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Employee not found or error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Create a new employee
     */
    public function storeEmployee(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'job_title_id' => 'required|exists:job_titles,id',
                'phone' => 'required|string|max:20',
                'address' => 'nullable|string',
                'national_id' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'hire_date' => 'required|date',
                'salary' => 'required|numeric|min:0',
                'is_delivery' => 'boolean',
                'commission_percentage' => 'nullable|numeric|min:0|max:100',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $data = $request->all();
            $data['is_active'] = true;
            $data['is_delivery'] = $request->boolean('is_delivery', false);
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                
                // Ensure directory exists
                if (!Storage::disk('public')->exists('employees')) {
                    Storage::disk('public')->makeDirectory('employees');
                }
                
                // Store image
                $path = $image->storeAs('employees', $filename, 'public');
                if (!$path) {
                    throw new \Exception('Failed to save image');
                }
                
                $data['image'] = $filename;
            }
            
            $employee = Employee::create($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully',
                'employee' => $employee->fresh(['jobTitle'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete image if there was an error
            if (isset($filename) && Storage::disk('public')->exists('employees/' . $filename)) {
                Storage::disk('public')->delete('employees/' . $filename);
            }
            
            Log::error('Error creating employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update an employee
     */
    public function updateEmployee(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'job_title_id' => 'required|exists:job_titles,id',
                'phone' => 'required|string|max:20',
                'address' => 'nullable|string',
                'national_id' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'hire_date' => 'required|date',
                'salary' => 'required|numeric|min:0',
                'is_delivery' => 'boolean',
                'commission_percentage' => 'nullable|numeric|min:0|max:100',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $data = $request->all();
            $data['is_delivery'] = $request->boolean('is_delivery', false);
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($employee->image && Storage::disk('public')->exists('employees/' . $employee->image)) {
                    Storage::disk('public')->delete('employees/' . $employee->image);
                }
                
                // Upload new image
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                
                // Ensure directory exists
                if (!Storage::disk('public')->exists('employees')) {
                    Storage::disk('public')->makeDirectory('employees');
                }
                
                // Store image
                $path = $image->storeAs('employees', $filename, 'public');
                if (!$path) {
                    throw new \Exception('Failed to save image');
                }
                
                $data['image'] = $filename;
            }
            
            $employee->update($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully',
                'employee' => $employee->fresh(['jobTitle'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete image if there was an error
            if (isset($filename) && Storage::disk('public')->exists('employees/' . $filename)) {
                Storage::disk('public')->delete('employees/' . $filename);
            }
            
            Log::error('Error updating employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle employee active status
     */
    public function toggleEmployeeStatus($id)
    {
        try {
            DB::beginTransaction();
            
            $employee = Employee::findOrFail($id);
            $employee->is_active = !$employee->is_active;
            $employee->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee status updated successfully',
                'is_active' => $employee->is_active
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error toggling employee status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error toggling employee status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get job titles
     */
    public function getJobTitles()
    {
        try {
            $jobTitles = JobTitle::all();
            
            return response()->json([
                'success' => true,
                'job_titles' => $jobTitles
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching job titles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching job titles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Record attendance check-in
     */
    public function checkIn(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $employee = Employee::findOrFail($id);
            $now = Carbon::now();
            $today = $now->format('Y-m-d');
            
            // Check if already checked in today
            $existingAttendance = EmployeeAttendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();
                
            if ($existingAttendance && $existingAttendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee already checked in today'
                ], 422);
            }
            
            // Create or update attendance record
            if (!$existingAttendance) {
                $attendance = new EmployeeAttendance();
                $attendance->employee_id = $employee->id;
                $attendance->date = $today;
            } else {
                $attendance = $existingAttendance;
            }
            
            $attendance->check_in = $now;
            $attendance->notes = $request->input('notes');
            
            // Check if late
            $scheduledTime = Carbon::parse($today . ' 09:00:00'); // Assuming work starts at 9AM
            $attendance->status = $now->gt($scheduledTime->addMinutes(15)) ? 'late' : 'present';
            
            $attendance->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Check-in recorded successfully',
                'attendance' => $attendance
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording check-in: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error recording check-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Record attendance check-out
     */
    public function checkOut(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $employee = Employee::findOrFail($id);
            $now = Carbon::now();
            $today = $now->format('Y-m-d');
            
            // Get today's attendance record
            $attendance = EmployeeAttendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();
                
            if (!$attendance || !$attendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'No check-in record found for today'
                ], 422);
            }
            
            if ($attendance->check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee already checked out today'
                ], 422);
            }
            
            // Update check-out time
            $attendance->check_out = $now;
            
            // Calculate working hours
            $checkIn = Carbon::parse($attendance->check_in);
            $workingHours = $checkIn->diffInHours($now);
            $attendance->working_hours = $workingHours;
            
            // Update notes if provided
            if ($request->has('notes')) {
                $attendance->notes = $attendance->notes 
                    ? $attendance->notes . ' | ' . $request->input('notes')
                    : $request->input('notes');
            }
            
            $attendance->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Check-out recorded successfully',
                'attendance' => $attendance,
                'working_hours' => $workingHours
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording check-out: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error recording check-out',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Record salary payment
     */
    public function paySalary(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date',
                'payment_month' => 'required|date_format:Y-m',
                'payment_method' => 'required|string|in:cash,bank_transfer,check',
                'reference_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $employee = Employee::findOrFail($id);
            
            // Check if salary for this month already paid
            $existingPayment = EmployeeSalary::where('employee_id', $employee->id)
                ->where('payment_month', $request->input('payment_month'))
                ->first();
                
            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salary for this month already paid',
                    'existing_payment' => $existingPayment
                ], 422);
            }
            
            // Create salary payment
            $payment = new EmployeeSalary();
            $payment->employee_id = $employee->id;
            $payment->amount = $request->input('amount');
            $payment->payment_date = $request->input('payment_date');
            $payment->payment_month = $request->input('payment_month');
            $payment->payment_method = $request->input('payment_method');
            $payment->reference_number = $request->input('reference_number');
            $payment->notes = $request->input('notes');
            $payment->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Salary payment recorded successfully',
                'payment' => $payment
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording salary payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error recording salary payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get employee attendance history
     */
    public function getAttendanceHistory(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            
            $query = EmployeeAttendance::where('employee_id', $employee->id);
            
            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('date', '>=', $request->input('start_date'));
            }
            
            if ($request->has('end_date')) {
                $query->whereDate('date', '<=', $request->input('end_date'));
            }
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }
            
            // Order by date (most recent first)
            $query->orderBy('date', 'desc');
            
            $attendance = $query->get();
            
            return response()->json([
                'success' => true,
                'employee' => $employee->only(['id', 'name']),
                'attendance' => $attendance
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching attendance history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching attendance history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get employee salary payment history
     */
    public function getSalaryHistory(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            
            $query = EmployeeSalary::where('employee_id', $employee->id);
            
            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('payment_date', '>=', $request->input('start_date'));
            }
            
            if ($request->has('end_date')) {
                $query->whereDate('payment_date', '<=', $request->input('end_date'));
            }
            
            // Order by payment date (most recent first)
            $query->orderBy('payment_date', 'desc');
            
            $salaries = $query->get();
            
            // Calculate total payments
            $totalPayments = $salaries->sum('amount');
            
            return response()->json([
                'success' => true,
                'employee' => $employee->only(['id', 'name', 'salary']),
                'total_payments' => $totalPayments,
                'salary_history' => $salaries
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching salary history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching salary history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 