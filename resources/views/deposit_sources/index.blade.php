@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-handshake me-2"></i>{{ __('مصادر الإيداعات') }}</h4>
            @can('create-deposit-sources')
                <a href="{{ route('deposit-sources.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> {{ __('إضافة مصدر جديد') }}
                </a>
            @endcan
        </div>
        <div class="card-body">
            @include('partials.flash_messages')

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
                        @forelse($sources as $source)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $source->name }}</td>
                                <td>{{ $source->description ?? '--' }}</td>
                                <td>
                                    @if($source->is_active)
                                        <span class="badge bg-success">{{ __('نشط') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('غير نشط') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @can('edit-deposit-sources')
                                        <a href="{{ route('deposit-sources.edit', $source) }}" class="btn btn-warning btn-sm" title="{{ __('تعديل') }}"><i class="fas fa-edit"></i></a>
                                        {{-- Toggle Active Button --}}
                                        <form action="{{ route('deposit-sources.toggle-active', $source) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-{{ $source->is_active ? 'secondary' : 'success' }} btn-sm"
                                                    title="{{ $source->is_active ? __('تعطيل') : __('تفعيل') }}">
                                                <i class="fas fa-{{ $source->is_active ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    @can('delete-deposit-sources')
                                        <form action="{{ route('deposit-sources.destroy', $source) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد؟ سيؤدي حذف المصدر إلى مشاكل إذا كان مستخدماً في إيداعات سابقة.') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('حذف') }}"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('لا توجد مصادر إيداعات معرفة.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination Links --}}
            @if ($sources->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $sources->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 