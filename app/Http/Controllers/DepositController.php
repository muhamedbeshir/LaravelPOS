<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\DepositSource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class DepositController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view-deposits');
        $deposits = Deposit::with(['user', 'source'])
                       ->latest()
                       ->paginate(15);
        return view('deposits.index', compact('deposits'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create-deposits');
        $sources = DepositSource::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        return view('deposits.create', compact('sources'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create-deposits');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'deposit_source_id' => ['required', 'integer', Rule::exists('deposit_sources', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $validated['user_id'] = $validated['user_id'] ?? Auth::id(); 

        Deposit::create($validated);

        return redirect()->route('deposits.index')
            ->with('success', __('تمت إضافة الإيداع بنجاح.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Deposit $deposit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Deposit $deposit)
    {
        $this->authorize('edit-deposits');
        $sources = DepositSource::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        return view('deposits.edit', compact('deposit', 'sources'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deposit $deposit)
    {
        $this->authorize('edit-deposits');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'deposit_source_id' => ['required', 'integer', Rule::exists('deposit_sources', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $deposit->update($validated);

        return redirect()->route('deposits.index')
            ->with('success', __('تم تحديث الإيداع بنجاح.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deposit $deposit)
    {
        $this->authorize('delete-deposits');
        $deposit->delete();
        return redirect()->route('deposits.index')
            ->with('success', __('تم حذف الإيداع بنجاح.'));
    }
}
