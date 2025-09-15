<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class ExpenseController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view-expenses');
        $expenses = Expense::with(['user', 'category'])
                      ->latest()
                      ->paginate(15);
        return view('expenses.index', compact('expenses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create-expenses');
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        return view('expenses.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create-expenses');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $validated['user_id'] = $validated['user_id'] ?? Auth::id(); 

        Expense::create($validated);

        return redirect()->route('expenses.index')
            ->with('success', __('تمت إضافة المصروف بنجاح.'));
    }

    /**
     * Display the specified resource.
     * Not typically needed for basic CRUD, implement if required.
     */
    // public function show(Expense $expense)
    // {
    //     // ...
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        $this->authorize('edit-expenses');
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        return view('expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $this->authorize('edit-expenses');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')
            ->with('success', __('تم تحديث المصروف بنجاح.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        $this->authorize('delete-expenses');
        $expense->delete();
        return redirect()->route('expenses.index')
            ->with('success', __('تم حذف المصروف بنجاح.'));
    }
}
