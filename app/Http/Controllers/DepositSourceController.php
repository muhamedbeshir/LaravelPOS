<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DepositSource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DepositSourceController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view-deposit-sources');
        $sources = DepositSource::latest()->paginate(15);
        return view('deposit_sources.index', compact('sources'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create-deposit-sources');
        return view('deposit_sources.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create-deposit-sources');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('deposit_sources', 'name')],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        DepositSource::create($validated);

        return redirect()->route('deposit-sources.index')
            ->with('success', __('تمت إضافة مصدر الإيداع بنجاح.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(DepositSource $depositSource)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DepositSource $depositSource)
    {
        $this->authorize('edit-deposit-sources');
        return view('deposit_sources.edit', ['source' => $depositSource]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DepositSource $depositSource)
    {
        $this->authorize('edit-deposit-sources');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('deposit_sources', 'name')->ignore($depositSource->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        
        $validated['is_active'] = $request->has('is_active');

        $depositSource->update($validated);

        return redirect()->route('deposit-sources.index')
            ->with('success', __('تم تحديث مصدر الإيداع بنجاح.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DepositSource $depositSource)
    {
        $this->authorize('delete-deposit-sources');

        // Optional: Check if the source is used by any deposits
        if ($depositSource->deposits()->exists()) {
            return redirect()->route('deposit-sources.index')
                ->with('error', __('لا يمكن حذف المصدر لأنه مستخدم في إيداعات حالية.'));
        }

        $depositSource->delete();

        return redirect()->route('deposit-sources.index')
            ->with('success', __('تم حذف مصدر الإيداع بنجاح.'));
    }
    
    /**
     * Toggle the active status of the specified resource.
     */
    public function toggleActive(DepositSource $depositSource)
    {
        $this->authorize('edit-deposit-sources');
        
        $depositSource->is_active = !$depositSource->is_active;
        $depositSource->save();
        
        $message = $depositSource->is_active ? __('تم تفعيل المصدر بنجاح.') : __('تم تعطيل المصدر بنجاح.');
        
        return redirect()->route('deposit-sources.index')->with('success', $message);
    }
}
