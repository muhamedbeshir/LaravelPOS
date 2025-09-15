<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Make final and extend base Controller
final class ExpenseCategoryController extends Controller 
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Authorize action
        $this->authorize('view-expense-categories');
        
        // Fetch paginated categories
        $categories = ExpenseCategory::latest()->paginate(15);
        
        // Return view with data
        return view('expense_categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Authorize action
        $this->authorize('create-expense-categories');
        
        // Return create form view
        return view('expense_categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Authorize action
        $this->authorize('create-expense-categories');

        // Validate input data
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Handle checkbox value (set to false if not present)
        $validated['is_active'] = $request->has('is_active');

        // Create the category
        ExpenseCategory::create($validated);

        // Redirect with success message
        return redirect()->route('expense-categories.index')
            ->with('success', __('تمت إضافة فئة المصروف بنجاح.'));
    }

    /**
     * Display the specified resource.
     * Not used as per route definition (except(['show']))
     */
    // public function show(ExpenseCategory $expenseCategory)
    // {
    //     // ... implementation if needed ...
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenseCategory $expenseCategory)
    {
        // Authorize action
        $this->authorize('edit-expense-categories');
        
        // Return edit form view with the category data
        // Renaming variable to match view expectations
        return view('expense_categories.edit', ['category' => $expenseCategory]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        // Authorize action
        $this->authorize('edit-expense-categories');

        // Validate input data, ignoring unique rule for the current category
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($expenseCategory->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        
        // Handle checkbox value
        $validated['is_active'] = $request->has('is_active');

        // Update the category
        $expenseCategory->update($validated);

        // Redirect with success message
        return redirect()->route('expense-categories.index')
            ->with('success', __('تم تحديث فئة المصروف بنجاح.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        // Authorize action
        $this->authorize('delete-expense-categories');

        // Optional: Check if the category is associated with expenses before deleting
        if ($expenseCategory->expenses()->exists()) {
            return redirect()->route('expense-categories.index')
                ->with('error', __('لا يمكن حذف الفئة لأنها مرتبطة بمصروفات حالية.'));
        }

        // Delete the category
        $expenseCategory->delete();

        // Redirect with success message
        return redirect()->route('expense-categories.index')
            ->with('success', __('تم حذف فئة المصروف بنجاح.'));
    }
    
    /**
     * Toggle the active status of the specified resource.
     */
    public function toggleActive(ExpenseCategory $expenseCategory)
    {
        // Authorize action (uses edit permission)
        $this->authorize('edit-expense-categories');
        
        // Toggle the is_active status
        $expenseCategory->is_active = !$expenseCategory->is_active;
        $expenseCategory->save();
        
        // Determine success message based on new status
        $message = $expenseCategory->is_active ? __('تم تفعيل الفئة بنجاح.') : __('تم تعطيل الفئة بنجاح.');
        
        // Redirect with success message
        return redirect()->route('expense-categories.index')->with('success', $message);
    }
}
