<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UnitApiController extends Controller
{
    /**
     * Get all units
     */
    public function getAllUnits(Request $request)
    {
        try {
            $query = Unit::with(['parentUnit', 'childUnits']);
            
            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            // Filter by unit type
            if ($request->has('is_base_unit')) {
                $query->where('is_base_unit', $request->boolean('is_base_unit'));
            }
            
            // Sort units
            $query->orderBy('is_base_unit', 'desc')->orderBy('name');
            
            $units = $query->get();
            
            return response()->json([
                'success' => true,
                'units' => $units
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching units: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching units',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific unit
     */
    public function getUnit($id)
    {
        try {
            $unit = Unit::with(['parentUnit', 'childUnits'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'unit' => $unit
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching unit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unit not found or error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Create a new unit
     */
    public function storeUnit(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Prepare unit data
            $data = $request->all();
            $data['is_base_unit'] = $request->boolean('is_base_unit', false);
            $data['is_active'] = true;
            
            // Generate unique code
            $data['code'] = $this->generateUniqueCode($data['name']);
            
            if ($data['is_base_unit']) {
                $data['parent_unit_id'] = null;
                $data['conversion_factor'] = 1;
            }
            
            // Validate data
            $rules = [
                'name' => 'required|string|max:255|unique:units',
                'code' => 'required|string|max:50|unique:units',
                'is_base_unit' => 'boolean',
                'is_active' => 'boolean'
            ];
            
            if (!$data['is_base_unit']) {
                $rules['parent_unit_id'] = 'required|exists:units,id';
                $rules['conversion_factor'] = 'required|numeric|min:0.01';
            }
            
            $validator = Validator::make($data, $rules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verify parent unit if it's a sub-unit
            if (!$data['is_base_unit']) {
                $parentUnit = Unit::findOrFail($data['parent_unit_id']);
                
                if (!$parentUnit->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent unit is inactive'
                    ], 422);
                }
                
                if (!isset($data['conversion_factor']) || $data['conversion_factor'] <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversion factor must be greater than zero'
                    ], 422);
                }
            }
            
            // Create unit
            $unit = Unit::create($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Unit created successfully',
                'unit' => $unit->fresh(['parentUnit', 'childUnits'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating unit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a unit
     */
    public function updateUnit(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $unit = Unit::findOrFail($id);
            
            // Prepare unit data
            $data = $request->all();
            $data['is_base_unit'] = $request->boolean('is_base_unit');
            
            if ($data['is_base_unit']) {
                $data['parent_unit_id'] = null;
                $data['conversion_factor'] = 1;
            }
            
            // Validate data
            $rules = [
                'name' => 'required|string|max:255|unique:units,name,' . $unit->id,
                'is_base_unit' => 'boolean'
            ];
            
            if (!$data['is_base_unit']) {
                $rules['parent_unit_id'] = 'required|exists:units,id';
                $rules['conversion_factor'] = 'required|numeric|min:0.01';
            }
            
            $validator = Validator::make($data, $rules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Additional validations for non-base units
            if (!$data['is_base_unit']) {
                // Check if unit has child units
                if ($unit->childUnits()->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot convert to sub-unit because it has child units'
                    ], 422);
                }
                
                $parentUnit = Unit::findOrFail($data['parent_unit_id']);
                
                if (!$parentUnit->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent unit is inactive'
                    ], 422);
                }
                
                // Check for circular relationship
                if ($this->wouldCreateCycle($unit, $data['parent_unit_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot create circular relationship between units'
                    ], 422);
                }
                
                if (!isset($data['conversion_factor']) || $data['conversion_factor'] <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversion factor must be greater than zero'
                    ], 422);
                }
            }
            
            // Update unit
            $unit->update($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully',
                'unit' => $unit->fresh(['parentUnit', 'childUnits'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating unit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a unit
     */
    public function deleteUnit($id)
    {
        try {
            DB::beginTransaction();
            
            $unit = Unit::findOrFail($id);
            
            // Check if unit has child units
            if ($unit->childUnits()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete unit with child units'
                ], 422);
            }
            
            // Check if unit is used in products
            if ($unit->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete unit used in products'
                ], 422);
            }
            
            $unit->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting unit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle unit status
     */
    public function toggleUnitStatus($id)
    {
        try {
            DB::beginTransaction();
            
            $unit = Unit::findOrFail($id);
            
            // Check if unit has active child units
            if ($unit->is_active && $unit->childUnits()->where('is_active', true)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate unit with active child units'
                ], 422);
            }
            
            $unit->is_active = !$unit->is_active;
            $unit->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Unit status updated successfully',
                'is_active' => $unit->is_active
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error toggling unit status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error toggling unit status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if updating parent would create a cycle
     */
    private function wouldCreateCycle(Unit $unit, $newParentId)
    {
        $parent = Unit::find($newParentId);
        while ($parent) {
            if ($parent->id === $unit->id) {
                return true;
            }
            $parent = $parent->parentUnit;
        }
        return false;
    }
    
    /**
     * Generate a unique code for the unit
     */
    private function generateUniqueCode($name)
    {
        // Convert name to latin chars and remove spaces
        $code = preg_replace('/[^A-Za-z0-9]/', '', $this->arabicToEnglish($name));
        $code = strtoupper(substr($code, 0, 3));
        
        // Add random number to ensure uniqueness
        $randomNumber = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $code .= $randomNumber;
        
        // Check if code exists
        while (Unit::where('code', $code)->exists()) {
            $randomNumber = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $code = strtoupper(substr($code, 0, 3)) . $randomNumber;
        }
        
        return $code;
    }
    
    /**
     * Convert Arabic text to English equivalent
     */
    private function arabicToEnglish($string)
    {
        $arabic = ['ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي'];
        $english = ['A', 'B', 'T', 'TH', 'J', 'H', 'KH', 'D', 'TH', 'R', 'Z', 'S', 'SH', 'S', 'D', 'T', 'TH', 'A', 'GH', 'F', 'Q', 'K', 'L', 'M', 'N', 'H', 'W', 'Y'];
        
        return str_replace($arabic, $english, $string);
    }
} 