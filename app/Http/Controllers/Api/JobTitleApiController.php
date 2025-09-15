<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobTitleApiController extends Controller
{
    /**
     * Get all job titles
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllJobTitles()
    {
        try {
            $jobTitles = JobTitle::orderBy('name')->get();
            return response()->json([
                'status' => 'success',
                'data' => $jobTitles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve job titles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job title by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobTitle($id)
    {
        try {
            $jobTitle = JobTitle::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $jobTitle
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job title not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store a new job title
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeJobTitle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_titles',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jobTitle = JobTitle::create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Job title created successfully',
                'data' => $jobTitle
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create job title',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update job title
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateJobTitle(Request $request, $id)
    {
        try {
            $jobTitle = JobTitle::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:job_titles,name,' . $id,
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $jobTitle->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Job title updated successfully',
                'data' => $jobTitle
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update job title',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Delete job title
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteJobTitle($id)
    {
        try {
            $jobTitle = JobTitle::findOrFail($id);
            
            if ($jobTitle->employees()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete job title as it is associated with employees'
                ], 422);
            }
            
            $jobTitle->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Job title deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete job title',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Toggle job title active status
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleJobTitleStatus($id)
    {
        try {
            $jobTitle = JobTitle::findOrFail($id);
            $jobTitle->is_active = !$jobTitle->is_active;
            $jobTitle->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Job title status updated successfully',
                'data' => [
                    'id' => $jobTitle->id,
                    'is_active' => $jobTitle->is_active
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update job title status',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
} 