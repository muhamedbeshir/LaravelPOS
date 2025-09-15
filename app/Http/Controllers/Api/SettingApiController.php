<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingApiController extends Controller
{
    /**
     * Get all public settings
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSettings()
    {
        try {
            $settings = Setting::where('is_public', true)
                ->orderBy('group')
                ->orderBy('key')
                ->get()
                ->groupBy('group');
                
            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get settings by group
     * 
     * @param string $group
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettingsByGroup($group)
    {
        try {
            $settings = Setting::where('is_public', true)
                ->where('group', $group)
                ->orderBy('key')
                ->get();
                
            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve settings for group',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get setting by key
     * 
     * @param string $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettingByKey($key)
    {
        try {
            $setting = Setting::where('key', $key)
                ->where('is_public', true)
                ->firstOrFail();
                
            return response()->json([
                'status' => 'success',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Setting not found or is not public',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Update settings
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string|exists:settings,key',
                'settings.*.value' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            foreach ($request->settings as $settingData) {
                $setting = Setting::where('key', $settingData['key'])->first();
                if ($setting && $setting->is_public) {
                    $setting->value = $settingData['value'];
                    $setting->save();
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update delete password
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDeletePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'delete_password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            Setting::set('delete_password', Hash::make($request->delete_password));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Delete password updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update delete password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verify delete password
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyDeletePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $storedPassword = Setting::get('delete_password');
            
            if (!$storedPassword) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Delete password has not been set yet',
                    'valid' => false
                ], 400);
            }

            $isValid = Hash::check($request->password, $storedPassword);
            
            return response()->json([
                'status' => 'success',
                'valid' => $isValid,
                'message' => $isValid ? 'Password is valid' : 'Password is invalid'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify delete password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 