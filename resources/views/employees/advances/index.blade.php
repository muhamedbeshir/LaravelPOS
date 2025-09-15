@extends('layouts.app')

@section('title', 'إدارة سلف الموظفين')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2 class="mb-0">إدارة سلف الموظفين</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">الموظفين</a></li>
                    <li class="breadcrumb-item active" aria-current="page">سلف الموظفين</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            @can('create-employee-advances')
            <a href="{{ route('employee-advances.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> سلفة جديدة
            </a>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning mb-4">
                    {{ session('warning') }}
                </div>
            @endif
            
            @if($advances->isEmpty())
                <div class="alert alert-info">
                    لا توجد سلف مسجلة حتى الآن.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الموظف</th>
                                <th>المبلغ</th>
                                <th>تاريخ السلفة</th>
                                <th>تاريخ السداد المتوقع</th>
                                <th>المبلغ المخصوم</th>
                                <th>الحالة</th>
                                <th>خصم من الراتب</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($advances as $advance)
                                <tr>
                                    <td>{{ $advance->id }}</td>
                                    <td>
                                        <a href="{{ route('employees.show', $advance->employee) }}">
                                            {{ $advance->employee->name }}
                                        </a>
                                    </td>
                                    <td>{{ number_format($advance->amount, 2) }}</td>
                                    <td>{{ $advance->date->format('Y-m-d') }}</td>
                                    <td>{{ $advance->repayment_date ? $advance->repayment_date->format('Y-m-d') : 'غير محدد' }}</td>
                                    <td>{{ number_format($advance->deducted_amount, 2) }}</td>
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
                
                <div class="mt-4">
                    {{ $advances->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 