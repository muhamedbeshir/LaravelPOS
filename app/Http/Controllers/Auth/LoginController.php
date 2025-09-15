<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class LoginController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        
        // استعلام لجلب المستخدمين النشطين فقط
        $users = User::where('is_active', true)
                    ->orderBy('name')
                    ->select('id', 'name', 'username')
                    ->get();
                    
        return view('auth.login', compact('users'));
    }
    
    /**
     * معالجة طلب تسجيل الدخول
     * Session will expire when browser is closed (no "Remember Me" functionality)
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        
        // البحث عن المستخدم باسم المستخدم وكلمة المرور (بدون تشفير)
        $user = User::where('username', $request->username)
                    ->where('password', $request->password)
                    ->first();
        
        // إذا تم العثور على المستخدم، قم بتسجيل الدخول
        if ($user) {
            // Force remember=false to ensure session expires when browser is closed
            Auth::login($user, false);
            
            // إعادة تكوين الجلسة
            $request->session()->regenerate();
            
            // Ensure session will expire when browser is closed
            Config::set('session.expire_on_close', true);
            
            // Apply session lifetime settings
            $request->session()->put('auth.password_confirmed_at', time());
            
            return redirect()->intended('/');
        }
        
        // إذا لم يتم العثور على المستخدم أو كانت البيانات غير صحيحة
        return back()->withErrors([
            'login_error' => 'بيانات تسجيل الدخول غير صحيحة',
        ])->onlyInput('username');
    }
    
    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }
}
