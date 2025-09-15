@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">الملف الشخصي</h6>
            <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-right"></i> عودة
            </a>
        </div>
        <div class="card-body">
            @if(Session::has('success'))
                <div class="alert alert-success">
                    {{ Session::get('success') }}
                </div>
            @endif
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="font-weight-bold m-0">معلومات المستخدم</h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            </div>
                            <h5 class="mb-1">{{ $user->name }}</h5>
                            <p class="text-muted">{{ $user->username }}</p>
                            <p>{{ $user->email }}</p>
                            
                            <hr>
                            
                            <div class="text-start">
                                <p><strong>الدور:</strong>
                                    @foreach($user->roles as $role)
                                        <span class="badge bg-primary text-white">{{ $role->name }}</span>
                                    @endforeach
                                </p>
                                <p><strong>تاريخ التسجيل:</strong> {{ $user->created_at->format('Y-m-d') }}</p>
                                <p><strong>آخر تحديث:</strong> {{ $user->updated_at->format('Y-m-d') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="font-weight-bold m-0">تعديل البيانات الشخصية</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('profile.update') }}" method="POST">
                                @csrf
                                @method('PATCH')
                                
                                <div class="mb-3">
                                    <label for="name">الاسم الكامل</label>
                                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username">اسم المستخدم</label>
                                    <input type="text" name="username" id="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email">البريد الإلكتروني</label>
                                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                </div>
                                
                                <hr>
                                <h6 class="mb-3">تغيير كلمة المرور</h6>
                                
                                <div class="mb-3">
                                    <label for="current_password">كلمة المرور الحالية</label>
                                    <input type="password" name="current_password" id="current_password" class="form-control">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password">كلمة المرور الجديدة</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password_confirmation">تأكيد كلمة المرور الجديدة</label>
                                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control">
                                </div>
                                
                                <div class="form-group text-center mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> حفظ التغييرات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 