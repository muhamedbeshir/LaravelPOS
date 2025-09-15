@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i>{{ __('الإيداعات') }}</h4>
            <div class="btn-group">
            @can('create-deposits')
                <a href="{{ route('deposits.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> {{ __('إضافة إيداع جديد') }}
                </a>
            @endcan
                @can('view-deposit-sources')
                    <a href="{{ route('deposit-sources.index') }}" class="btn btn-info btn-sm">
                        <i class="fas fa-handshake me-1"></i> {{ __('مصادر الإيداعات') }}
                    </a>
                @endcan
                @can('create-deposit-sources')
                    <a href="{{ route('deposit-sources.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle me-1"></i> {{ __('إضافة مصدر جديد') }}
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
                            <th>{{ __('مصدر الإيداع') }}</th>
                            <th>{{ __('الملاحظات') }}</th>
                            <th>{{ __('التاريخ') }}</th>
                            <th>{{ __('الإجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deposits as $deposit)
                            <tr>
                                <td>{{ $loop->iteration + $deposits->firstItem() - 1 }}</td>
                                <td>{{ $deposit->user->name ?? '--' }}</td>
                                <td>{{ number_format($deposit->amount, 2) }}</td>
                                <td>{{ $deposit->source->name ?? '--' }}</td>
                                <td>{{ $deposit->notes ?? '--' }}</td>
                                <td>{{ $deposit->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    @can('edit-deposits')
                                        <a href="{{ route('deposits.edit', $deposit) }}" class="btn btn-warning btn-sm" title="{{ __('تعديل') }}"><i class="fas fa-edit"></i></a>
                                    @endcan
                                    @can('delete-deposits')
                                        <form action="{{ route('deposits.destroy', $deposit) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من رغبتك في حذف هذا الإيداع؟') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('حذف') }}"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('لا توجد إيداعات مسجلة بعد.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination Links --}}
            @if ($deposits->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $deposits->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 