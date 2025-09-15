@extends('layouts.app')

@section('title', 'سلف الموظف: ' . $employee->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2 class="mb-0">سلف الموظف: {{ $employee->name }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">الموظفين</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}">{{ $employee->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">سلف الموظف</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <div class="btn-group">
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة لبيانات الموظف
                </a>
                
                @can('create-employee-advances')
                <a href="{{ route('employee-advances.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> إضافة سلفة جديدة
                </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">ملخص السلف</h5>
                    <div class="row mt-3">
                        <div class="col-6">
                            <p class="text-muted mb-1">إجمالي السلف</p>
                            <h4>{{ number_format($advances->sum('amount'), 2) }} ج.م</h4>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">المبالغ المسددة</p>
                            <h4>{{ number_format($advances->sum('deducted_amount'), 2) }} ج.م</h4>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <p class="text-muted mb-1">المبالغ المتبقية</p>
                            <h4>{{ number_format($advances->sum('amount') - $advances->sum('deducted_amount'), 2) }} ج.م</h4>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">عدد السلف</p>
                            <h4>{{ $advances->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($advances->isEmpty())
                <div class="alert alert-info">
                    لا توجد سلف مسجلة لهذا الموظف.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المبلغ</th>
                                <th>تاريخ السلفة</th>
                                <th>تاريخ السداد المتوقع</th>
                                <th>المبلغ المخصوم</th>
                                <th>المبلغ المتبقي</th>
                                <th>الحالة</th>
                                <th>خصم من الراتب</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($advances as $advance)
                                <tr>
                                    <td>{{ $advance->id }}</td>
                                    <td>{{ number_format($advance->amount, 2) }}</td>
                                    <td>{{ $advance->date->format('Y-m-d') }}</td>
                                    <td>{{ $advance->repayment_date ? $advance->repayment_date->format('Y-m-d') : 'غير محدد' }}</td>
                                    <td>{{ number_format($advance->deducted_amount, 2) }}</td>
                                    <td>{{ number_format($advance->remaining_amount, 2) }}</td>
                                    <td>
                                        @if($advance->status === 'pending')
                                            <span class="badge bg-warning">معلقة</span>
                                        @elseif($advance->status === 'partially_paid')
                                            <span class="badge bg-info">مدفوعة جزئيًا</span>
                                        @elseif($advance->status === 'paid')
                                            <span class="badge bg-success">مدفوعة بالكامل</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($advance->is_deducted_from_salary)
                                            <i class="fas fa-check-circle text-success"></i>
                                        @else
                                            <i class="fas fa-times-circle text-danger"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('employee-advances.show', $advance) }}" class="btn btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($advance->status !== 'paid')
                                                @can('edit-employee-advances')
                                                <a href="{{ route('employee-advances.edit', $advance) }}" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <form action="{{ route('employee-advances.repay', $advance) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success" onclick="return confirm('هل أنت متأكد من سداد السلفة؟ سيتم خصم {{ number_format($advance->remaining_amount, 2) }} ج.م من رصيد السلف للموظف.')">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                                
                                                @can('delete-employee-advances')
                                                <form action="{{ route('employee-advances.destroy', $advance) }}" method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه السلفة؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 