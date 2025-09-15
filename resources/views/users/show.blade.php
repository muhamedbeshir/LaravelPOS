@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">بيانات المستخدم: {{ $user->name }}</h6>
            <div>
                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> تعديل
                </a>
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right"></i> العودة للقائمة
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold">البيانات الشخصية</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <th style="width: 200px">الاسم الكامل</th>
                                        <td>{{ $user->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>اسم المستخدم</th>
                                        <td>{{ $user->username }}</td>
                                    </tr>
                                    <tr>
                                        <th>البريد الإلكتروني</th>
                                        <td>{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <th>تاريخ الإنشاء</th>
                                        <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th>آخر تحديث</th>
                                        <td>{{ $user->updated_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th>الحالة</th>
                                        <td>
                                            @if($user->is_active)
                                                <span class="badge bg-success text-white">نشط</span>
                                            @else
                                                <span class="badge bg-danger text-white">معطل</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold">الصلاحيات والأدوار</h6>
                        </div>
                        <div class="card-body">
                            <h6>الأدوار:</h6>
                            <div class="mb-3">
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary text-white p-2 mb-1">{{ $role->name }}</span>
                                @endforeach
                            </div>
                            
                            <h6>الصلاحيات:</h6>
                            <div>
                                @foreach($user->getAllPermissions() as $permission)
                                    <span class="badge bg-info text-dark p-1 mb-1">{{ $permission->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    @if($user->employee)
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold">بيانات الموظف المرتبط</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm">
                                <tbody>
                                    <tr>
                                        <th>اسم الموظف</th>
                                        <td>{{ $user->employee->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>الوظيفة</th>
                                        <td>{{ $user->employee->job_title->name ?? 'غير محدد' }}</td>
                                    </tr>
                                    <tr>
                                        <th>رقم الهاتف</th>
                                        <td>{{ $user->employee->phone ?? 'غير محدد' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <a href="{{ route('employees.show', $user->employee_id) }}" class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-user"></i> عرض بيانات الموظف
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 