@extends('layouts.app')

@section('title', 'تفاصيل السلفة')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2 class="mb-0">تفاصيل السلفة #{{ $employeeAdvance->id }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">الموظفين</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employee-advances.index') }}">سلف الموظفين</a></li>
                    <li class="breadcrumb-item active" aria-current="page">تفاصيل السلفة #{{ $employeeAdvance->id }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <div class="btn-group">
                <a href="{{ route('employee-advances.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                </a>
                
                @if($employeeAdvance->status !== 'paid')
                    @can('edit-employee-advances')
                    <a href="{{ route('employee-advances.edit', $employeeAdvance) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> تعديل
                    </a>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">بيانات السلفة</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">الموظف</p>
                            <p class="mb-3 fs-5">
                                <a href="{{ route('employees.show', $employeeAdvance->employee) }}">
                                    {{ $employeeAdvance->employee->name }}
                                </a>
                            </p>
                            
                            <p class="mb-1 text-muted">مبلغ السلفة</p>
                            <p class="mb-3 fs-5">{{ number_format($employeeAdvance->amount, 2) }} ج.م</p>
                            
                            <p class="mb-1 text-muted">المبلغ المخصوم</p>
                            <p class="mb-3 fs-5">{{ number_format($employeeAdvance->deducted_amount, 2) }} ج.م</p>
                            
                            <p class="mb-1 text-muted">المبلغ المتبقي</p>
                            <p class="mb-3 fs-5">{{ number_format($employeeAdvance->remaining_amount, 2) }} ج.م</p>
                        </div>
                        
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">تاريخ السلفة</p>
                            <p class="mb-3 fs-5">{{ $employeeAdvance->date->format('Y-m-d') }}</p>
                            
                            <p class="mb-1 text-muted">تاريخ السداد المتوقع</p>
                            <p class="mb-3 fs-5">{{ $employeeAdvance->repayment_date ? $employeeAdvance->repayment_date->format('Y-m-d') : 'غير محدد' }}</p>
                            
                            <p class="mb-1 text-muted">الحالة</p>
                            <p class="mb-3">
                                @if($employeeAdvance->status === 'pending')
                                    <span class="badge bg-warning">معلقة</span>
                                @elseif($employeeAdvance->status === 'partially_paid')
                                    <span class="badge bg-info">مدفوعة جزئيًا</span>
                                @elseif($employeeAdvance->status === 'paid')
                                    <span class="badge bg-success">مدفوعة بالكامل</span>
                                @endif
                            </p>
                            
                            <p class="mb-1 text-muted">خصم من الراتب</p>
                            <p class="mb-3">
                                @if($employeeAdvance->is_deducted_from_salary)
                                    <span class="badge bg-success">نعم</span>
                                @else
                                    <span class="badge bg-danger">لا</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    @if($employeeAdvance->notes)
                    <div class="row mb-3">
                        <div class="col-12">
                            <p class="mb-1 text-muted">ملاحظات</p>
                            <p class="mb-0">{{ $employeeAdvance->notes }}</p>
                        </div>
                    </div>
                    @endif
                    
                    <div class="row">
                        <div class="col-12">
                            <p class="mb-1 text-muted">تم إنشاؤها بواسطة</p>
                            <p class="mb-0">{{ $employeeAdvance->creator->name ?? 'غير معروف' }} - {{ $employeeAdvance->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">معلومات السداد</h5>
                </div>
                <div class="card-body">
                    @if($employeeAdvance->salary_payment)
                        <div class="alert alert-success">
                            <h6 class="alert-heading">تم خصم السلفة من الراتب</h6>
                            <p class="mb-0">تاريخ الدفع: {{ $employeeAdvance->salary_payment->payment_date->format('Y-m-d') }}</p>
                            <p class="mb-0">مبلغ الراتب: {{ number_format($employeeAdvance->salary_payment->amount, 2) }} ج.م</p>
                            <p class="mb-0">طريقة الدفع: {{ $employeeAdvance->salary_payment->getPaymentMethodText() }}</p>
                        </div>
                    @else
                        @if($employeeAdvance->status === 'paid')
                            <div class="alert alert-success">
                                <h6 class="alert-heading">تم سداد السلفة بالكامل</h6>
                                <p class="mb-0">المبلغ المدفوع: {{ number_format($employeeAdvance->deducted_amount, 2) }} ج.م</p>
                            </div>
                        @elseif($employeeAdvance->status === 'partially_paid')
                            <div class="alert alert-info">
                                <h6 class="alert-heading">تم سداد جزء من السلفة</h6>
                                <p class="mb-0">المبلغ المدفوع: {{ number_format($employeeAdvance->deducted_amount, 2) }} ج.م</p>
                                <p class="mb-0">المبلغ المتبقي: {{ number_format($employeeAdvance->remaining_amount, 2) }} ج.م</p>
                                
                                @can('edit-employee-advances')
                                <div class="mt-3">
                                    <form action="{{ route('employee-advances.repay', $employeeAdvance) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من سداد المبلغ المتبقي؟ سيتم خصم {{ number_format($employeeAdvance->remaining_amount, 2) }} ج.م من رصيد السلف للموظف.')">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-money-bill-wave me-1"></i> سداد المبلغ المتبقي
                                        </button>
                                    </form>
                                </div>
                                @endcan
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">لم يتم سداد السلفة بعد</h6>
                                <p class="mb-0">المبلغ المطلوب: {{ number_format($employeeAdvance->amount, 2) }} ج.م</p>
                                
                                @if($employeeAdvance->is_deducted_from_salary)
                                    <p class="mb-0 mt-2">
                                        <i class="fas fa-info-circle"></i>
                                        سيتم خصم هذه السلفة تلقائيًا من الراتب القادم للموظف.
                                    </p>
                                @endif
                                
                                @can('edit-employee-advances')
                                <div class="mt-3">
                                    <form action="{{ route('employee-advances.repay', $employeeAdvance) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من سداد السلفة؟ سيتم خصم {{ number_format($employeeAdvance->amount, 2) }} ج.م من رصيد السلف للموظف.')">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-money-bill-wave me-1"></i> سداد السلفة
                                        </button>
                                    </form>
                                </div>
                                @endcan
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 