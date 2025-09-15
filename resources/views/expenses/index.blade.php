@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>{{ __('المصروفات') }}</h4>
            <div class="btn-group">
            @can('create-expenses')
                <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> {{ __('إضافة مصروف جديد') }}
                </a>
            @endcan
                @can('view-expense-categories')
                    <a href="{{ route('expense-categories.index') }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-folder me-1"></i> {{ __('فئات المصروفات') }}
                    </a>
                @endcan
                @can('create-expense-categories')
                    <a href="{{ route('expense-categories.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus-circle me-1"></i> {{ __('إضافة فئة جديدة') }}
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('المستخدم') }}</th>
                            <th>{{ __('المبلغ') }}</th>
                            <th>{{ __('فئة المصروف') }}</th>
                            <th>{{ __('الملاحظات') }}</th>
                            <th>{{ __('التاريخ') }}</th>
                            <th>{{ __('الإجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ $loop->iteration + $expenses->firstItem() - 1 }}</td>
                                <td>{{ $expense->user->name ?? '--' }}</td>
                                <td>{{ number_format($expense->amount, 2) }}</td>
                                <td>{{ $expense->category->name ?? '--' }}</td>
                                <td>{{ $expense->notes ?? '--' }}</td>
                                <td>{{ $expense->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    @can('edit-expenses')
                                        <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-warning btn-sm" title="{{ __('تعديل') }}"><i class="fas fa-edit"></i></a>
                                    @endcan
                                    @can('delete-expenses')
                                        <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من رغبتك في حذف هذا المصروف؟') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('حذف') }}"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('لا توجد مصروفات مسجلة بعد.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination Links --}}
            @if ($expenses->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 