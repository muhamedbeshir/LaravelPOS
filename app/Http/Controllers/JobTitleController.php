<?php

namespace App\Http\Controllers;

use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobTitleController extends Controller
{
    protected static array $middlewares = [
        'auth',
        'permission:view-job-titles' => ['only' => ['index', 'show']],
        'permission:create-job-titles' => ['only' => ['create', 'store']],
        'permission:edit-job-titles' => ['only' => ['edit', 'update']],
        'permission:delete-job-titles' => ['only' => ['destroy']],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $jobTitles = JobTitle::orderBy('name')->get();
        return view('job_titles.index', compact('jobTitles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('job_titles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_titles',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            JobTitle::create($request->all());
            return redirect()->route('job-titles.index')
                ->with('success', 'تم إضافة الوظيفة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إضافة الوظيفة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JobTitle $jobTitle)
    {
        return view('job_titles.edit', compact('jobTitle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobTitle $jobTitle)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_titles,name,' . $jobTitle->id,
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $jobTitle->update($request->all());
            return redirect()->route('job-titles.index')
                ->with('success', 'تم تحديث الوظيفة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث الوظيفة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobTitle $jobTitle)
    {
        try {
            if ($jobTitle->employees()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'لا يمكن حذف الوظيفة لأنها مرتبطة بموظفين');
            }

            $jobTitle->delete();
            return redirect()->route('job-titles.index')
                ->with('success', 'تم حذف الوظيفة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف الوظيفة: ' . $e->getMessage());
        }
    }

    public function toggleActive(JobTitle $jobTitle)
    {
        $jobTitle->is_active = !$jobTitle->is_active;
        $jobTitle->save();

        return redirect()->back()
            ->with('success', 'تم تحديث حالة الوظيفة بنجاح');
    }
}
