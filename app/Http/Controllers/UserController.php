<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // تعريف middleware في خاصية ثابتة بدلاً من constructor
    protected static array $middlewares = [
        'auth',
        'permission:manage-users' => ['except' => ['showProfile', 'updateProfile']],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('roles')->get();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::all();
        $employees = Employee::where('is_active', true)->whereDoesntHave('user')->get();
        return view('users.create', compact('roles', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'username' => 'required|string',
            'password' => 'required|string',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => $request->password,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        if ($request->role) {
            $user->assignRole($request->role);
        }

        return redirect()->route('users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['roles', 'employee'])->findOrFail($id);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = \Spatie\Permission\Models\Role::all();
        $userRole = $user->roles->first() ? $user->roles->first()->name : null;
        $employees = \App\Models\Employee::where('is_active', true)->get();
        return view('users.edit', compact('user', 'roles', 'userRole', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->username = $request->username;
        $user->is_active = $request->has('is_active') ? true : false;
        $user->employee_id = $request->employee_id;
        if ($request->filled('password')) {
            $user->password = $request->password;
        }
        $user->save();
        if ($request->role) {
            $user->syncRoles([$request->role]);
        }
        return redirect()->route('users.index')
            ->with('success', 'تم تحديث المستخدم بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // التحقق من أن المستخدم ليس المستخدم الحالي
        if (Auth::id() == $user->id) {
            Session::flash('error', 'لا يمكنك حذف حسابك الخاص.');
            return redirect()->route('users.index');
        }
        
        // عدم السماح بحذف المسؤول الرئيسي، فقط يمكن تعديله
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            Session::flash('error', 'لا يمكن حذف المسؤول الرئيسي الوحيد.');
            return redirect()->route('users.index');
        }
        
        $user->delete();
        Session::flash('success', 'تم حذف المستخدم بنجاح.');
        return redirect()->route('users.index');
    }
    
    /**
     * عرض صفحة الملف الشخصي للمستخدم المسجل حاليا
     */
    public function showProfile()
    {
        $user = Auth::user();
        return view('users.profile', compact('user'));
    }
    
    /**
     * تحديث الملف الشخصي للمستخدم المسجل حاليا
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|string|min:6|confirmed',
        ]);
        
        // التحقق من كلمة المرور الحالية
        if ($request->filled('current_password') && $request->current_password != $user->password) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }
        
        $userData = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
        ];
        
        // تحديث كلمة المرور فقط إذا تم تقديمها
        if ($request->filled('new_password')) {
            $userData['password'] = $request->new_password; // بدون تشفير كما طلب المستخدم
        }
        
        $user->update($userData);
        
        Session::flash('success', 'تم تحديث الملف الشخصي بنجاح.');
        return redirect('/profile');
    }
    
    /**
     * تبديل حالة المستخدم (نشط/غير نشط)
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // لا يمكن تعطيل المسؤول الرئيسي
        if ($user->hasRole('admin') && User::role('admin')->where('is_active', true)->count() <= 1 && $user->is_active) {
            Session::flash('error', 'لا يمكن تعطيل المسؤول الرئيسي الوحيد.');
            return redirect()->route('users.index');
        }
        
        // لا يمكن تعطيل المستخدم الحالي
        if (Auth::id() == $user->id) {
            Session::flash('error', 'لا يمكنك تعطيل حسابك الخاص.');
            return redirect()->route('users.index');
        }
        
        $user->is_active = !$user->is_active;
        $user->save();
        
        $status = $user->is_active ? 'تفعيل' : 'تعطيل';
        Session::flash('success', "تم {$status} المستخدم بنجاح.");
        return redirect()->route('users.index');
    }
}
