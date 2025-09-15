@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-tags me-2"></i>{{ __('فئات المصروفات') }}</h4>
            @can('create-expense-categories')
                <a href="{{ route('expense-categories.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> {{ __('إضافة فئة جديدة') }}
                </a>
            @endcan
        </div>
        <div class="card-body">
            @include('partials.flash_messages') {{-- Assuming you have a partial for flash messages --}}

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('الاسم') }}</th>
                            <th>{{ __('الوصف') }}</th>
                            <th>{{ __('الحالة') }}</th>
                            <th>{{ __('الإجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->description ?? '--' }}</td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success">{{ __('نشط') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('غير نشط') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @can('edit-expense-categories')
                                        <a href="{{ route('expense-categories.edit', $category) }}" class="btn btn-warning btn-sm" title="{{ __('تعديل') }}"><i class="fas fa-edit"></i></a>
                                        {{-- Toggle Active Button --}}
                                        <form action="{{ route('expense-categories.toggle-active', $category) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-{{ $category->is_active ? 'secondary' : 'success' }} btn-sm"
                                                    title="{{ $category->is_active ? __('تعطيل') : __('تفعيل') }}">
                                                <i class="fas fa-{{ $category->is_active ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    @can('delete-expense-categories')
                                        {{-- Prevent deleting if category is used? Add check later in controller --}}
                                        <form action="{{ route('expense-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد؟ سيؤدي حذف الفئة إلى مشاكل إذا كانت مستخدمة في مصروفات سابقة.') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('حذف') }}"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('لا توجد فئات مصروفات معرفة.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination Links --}}
            @if ($categories->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 