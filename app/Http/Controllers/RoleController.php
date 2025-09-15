<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * عرض قائمة الأدوار
     */
    public function index()
    {
        // التحقق من أن المستخدم لديه دور المسؤول
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }
        
        $roles = Role::orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    /**
     * عرض صفحة إنشاء دور جديد
     */
    public function create()
    {
        // التحقق من أن المستخدم لديه دور المسؤول
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }
        
        $permissions = $this->getGroupedPermissions();
        $translations = $this->getPermissionTranslations();
        return view('roles.create', compact('permissions', 'translations'));
    }

    /**
     * حفظ الدور الجديد
     */
    public function store(Request $request)
    {
        // التحقق من أن المستخدم لديه دور المسؤول
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }
        
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'permissions' => ['required', 'array']
        ]);

        try {
            DB::beginTransaction();
            
            // إنشاء الدور
            $role = Role::create(['name' => $request->name]);
            
            // تعيين الصلاحيات للدور
            $role->syncPermissions($request->permissions);
            
            DB::commit();
            
            return redirect()->route('roles.index')
                ->with('success', 'تم إنشاء الدور بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إنشاء الدور: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل الدور المحدد
     */
    public function show(Role $role)
    {
        // التحقق من أن المستخدم لديه دور المسؤول
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }
        
        return view('roles.show', compact('role'));
    }

    /**
     * عرض صفحة تعديل دور
     */
    public function edit(Role $role)
    {
        // التحقق من أن المستخدم لديه دور المسؤول
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }
        
        // منع تعديل دور المسؤول
        if ($role->name === 'admin') {
            return redirect()->route('roles.index')
                ->with('error', 'لا يمكن تعديل دور المسؤول');
        }
        
        $permissions = $this->getGroupedPermissions();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $translations = $this->getPermissionTranslations();
        
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'translations'));
    }

    /**
     * تحديث بيانات الدور
     */
    public function update(Request $request, Role $role)
    {
        // التحقق من أن المستخدم لديه دور المسؤول
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }
        
        // منع تعديل دور المسؤول
        if ($role->name === 'admin') {
            return redirect()->route('roles.index')
                ->with('error', 'لا يمكن تعديل دور المسؤول');
        }
        
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role)],
            'permissions' => ['required', 'array']
        ]);

        try {
            DB::beginTransaction();
            
            // تحديث اسم الدور
            $role->update(['name' => $request->name]);
            
            // تحديث صلاحيات الدور
            $role->syncPermissions($request->permissions);
            
            DB::commit();
            
            return redirect()->route('roles.index')
                ->with('success', 'تم تحديث الدور بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث الدور: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * حذف الدور المحدد
     */
    public function destroy(Role $role)
    {
        // التحقق من أن المستخدم لديه دور المسؤول
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }
        
        // منع حذف دور المسؤول
        if ($role->name === 'admin') {
            return redirect()->route('roles.index')
                ->with('error', 'لا يمكن حذف دور المسؤول');
        }
        
        // التحقق من عدم وجود مستخدمين مرتبطين بهذا الدور
        if ($role->users->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'لا يمكن حذف هذا الدور لأنه مرتبط بـ ' . $role->users->count() . ' مستخدم');
        }
        
        try {
            $role->delete();
            return redirect()->route('roles.index')
                ->with('success', 'تم حذف الدور بنجاح');
        } catch (\Exception $e) {
            return redirect()->route('roles.index')
                ->with('error', 'حدث خطأ أثناء حذف الدور: ' . $e->getMessage());
        }
    }

    /**
     * تنظيم الصلاحيات في مجموعات
     */
    private function getGroupedPermissions()
    {
        $allPermissions = Permission::all();
        
        $grouped = [
            'إدارة المستخدمين' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-users', 'create-users', 'edit-users', 'delete-users', 'manage-users', 'assign-roles'
                ]);
            }),
            
            'المنتجات' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-products', 'create-products', 'edit-products', 'delete-products', 
                    'import-products', 'export-products', 'print-barcode'
                ]);
            }),
            
            'الفئات' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-categories', 'create-categories', 'edit-categories', 'delete-categories'
                ]);
            }),
            
            'الوحدات' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-units', 'create-units', 'edit-units', 'delete-units'
                ]);
            }),
            
            'المبيعات' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-sales', 'create-sales', 'edit-sales', 'delete-sales', 
                    'manage-sale-payments', 'pos'
                ]);
            }),
            
            'المشتريات' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-purchases', 'create-purchases', 'edit-purchases', 'delete-purchases', 
                    'manage-purchase-payments'
                ]);
            }),
            
            'المخزون' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-inventory', 'manage-inventory', 'adjust-inventory'
                ]);
            }),
            
            'العملاء' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-customers', 'create-customers', 'edit-customers', 'delete-customers'
                ]);
            }),
            
            'الموردين' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers'
                ]);
            }),
            
            'الموظفين' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-employees', 'create-employees', 'edit-employees', 'delete-employees'
                ]);
            }),
            
            'المسميات الوظيفية' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-job-titles', 'create-job-titles', 'edit-job-titles', 'delete-job-titles'
                ]);
            }),
            
            'التقارير' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-reports', 'view-sales-report', 'view-purchases-report', 
                    'view-inventory-report', 'view-customers-report'
                ]);
            }),
            
            'الإعدادات' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'manage-settings', 'manage-roles'
                ]);
            }),
            
            'النسخ الاحتياطي' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-backups', 'create-backups', 'download-backups', 'delete-backups', 'manage-backups'
                ]);
            }),

            'لوحة التحكم' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'access-dashboard'
                ]);
            }),

            'إدارة الورديات' => $allPermissions->filter(function ($permission) {
                return in_array($permission->name, [
                    'view-shifts', 'create-shifts', 'edit-shifts', 'delete-shifts'
                ]);
            }),
        ];
        
        // إزالة المجموعات الفارغة
        return array_filter($grouped, function ($group) {
            return !$group->isEmpty();
        });
    }

    /**
     * مصفوفة ترجمة الصلاحيات
     */
    private function getPermissionTranslations()
    {
        return [
            // Users
            'view-users' => 'عرض المستخدمين',
            'create-users' => 'إنشاء مستخدمين',
            'edit-users' => 'تعديل المستخدمين',
            'delete-users' => 'حذف المستخدمين',
            'manage-users' => 'إدارة المستخدمين',
            'assign-roles' => 'إسناد الأدوار',
            // Products
            'view-products' => 'عرض المنتجات',
            'create-products' => 'إنشاء المنتجات',
            'edit-products' => 'تعديل المنتجات',
            'delete-products' => 'حذف المنتجات',
            'import-products' => 'استيراد المنتجات',
            'export-products' => 'تصدير المنتجات',
            'print-barcode' => 'طباعة باركود',
            // Categories
            'view-categories' => 'عرض الفئات',
            'create-categories' => 'إنشاء الفئات',
            'edit-categories' => 'تعديل الفئات',
            'delete-categories' => 'حذف الفئات',
            // Units
            'view-units' => 'عرض الوحدات',
            'create-units' => 'إنشاء الوحدات',
            'edit-units' => 'تعديل الوحدات',
            'delete-units' => 'حذف الوحدات',
            // Sales
            'view-sales' => 'عرض المبيعات',
            'create-sales' => 'إنشاء المبيعات',
            'edit-sales' => 'تعديل المبيعات',
            'delete-sales' => 'حذف المبيعات',
            'manage-sale-payments' => 'إدارة مدفوعات المبيعات',
            'pos' => 'نقطة البيع',
            // Purchases
            'view-purchases' => 'عرض المشتريات',
            'create-purchases' => 'إنشاء المشتريات',
            'edit-purchases' => 'تعديل المشتريات',
            'delete-purchases' => 'حذف المشتريات',
            'manage-purchase-payments' => 'إدارة مدفوعات المشتريات',
            // Inventory
            'view-inventory' => 'عرض المخزون',
            'manage-inventory' => 'إدارة المخزون',
            'adjust-inventory' => 'تعديل المخزون',
            // Customers
            'view-customers' => 'عرض العملاء',
            'create-customers' => 'إنشاء العملاء',
            'edit-customers' => 'تعديل العملاء',
            'delete-customers' => 'حذف العملاء',
            // Suppliers
            'view-suppliers' => 'عرض الموردين',
            'create-suppliers' => 'إنشاء الموردين',
            'edit-suppliers' => 'تعديل الموردين',
            'delete-suppliers' => 'حذف الموردين',
            // Employees
            'view-employees' => 'عرض الموظفين',
            'create-employees' => 'إنشاء الموظفين',
            'edit-employees' => 'تعديل الموظفين',
            'delete-employees' => 'حذف الموظفين',
            // Job Titles
            'view-job-titles' => 'عرض المسميات الوظيفية',
            'create-job-titles' => 'إنشاء المسميات الوظيفية',
            'edit-job-titles' => 'تعديل المسميات الوظيفية',
            'delete-job-titles' => 'حذف المسميات الوظيفية',
            // Reports
            'view-reports' => 'عرض التقارير',
            'view-sales-report' => 'عرض تقارير المبيعات',
            'view-purchases-report' => 'عرض تقارير المشتريات',
            'view-inventory-report' => 'عرض تقارير المخزون',
            'view-customers-report' => 'عرض تقارير العملاء',
            // Settings
            'manage-settings' => 'إدارة الإعدادات',
            'manage-roles' => 'إدارة الأدوار',
            // Backups
            'manage-backups' => 'إدارة النسخ الاحتياطي',
            'view-backups' => 'عرض النسخ الاحتياطي',
            'create-backups' => 'إنشاء نسخ احتياطي',
            'download-backups' => 'تنزيل النسخ الاحتياطي',
            'delete-backups' => 'حذف النسخ الاحتياطي',
            // Dashboard
            'access-dashboard' => 'الوصول إلى لوحة التحكم',

            'view-shifts' => 'عرض الورديات',
            'create-shifts' => 'إنشاء الورديات',
            'edit-shifts' => 'تعديل الورديات',
            'delete-shifts' => 'حذف الورديات',
        ];
    }
} 