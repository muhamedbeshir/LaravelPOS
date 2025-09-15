@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة الوحدات</h2>
        <div>
            <a href="{{ route('units.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة وحدة جديدة
            </a>
            <a href="{{ route('units.export') }}" class="btn btn-secondary">
                <i class="fas fa-file-export me-1"></i>
                تصدير الوحدات
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>النوع</th>
                            <th>الوحدة الأم</th>
                            <th>معامل التحويل المباشر</th>
                            <th>معامل التحويل الكلي</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($units as $unit)
                        <tr>
                            <td>{{ $unit->name }}</td>
                            <td>
                                @if($unit->is_base_unit)
                                <span class="badge bg-primary">وحدة أساسية</span>
                                @else
                                <span class="badge bg-info">وحدة فرعية</span>
                                @endif
                            </td>
                            <td>
                                {{ $unit->parentUnit ? $unit->parentUnit->name : '-' }}
                            </td>
                            <td>
                                @if($unit->is_base_unit)
                                -
                                @else
                                {{ $unit->getDirectConversionText() }}
                                @endif
                            </td>
                            <td>
                                @if($unit->is_base_unit)
                                -
                                @else
                                <span class="text-primary" data-bs-toggle="tooltip" data-bs-placement="top" 
                                      title="{{ implode("\n", $unit->getFullConversionChain()['conversions']) }}">
                                    {{ $unit->conversion_text }}
                                    <i class="fas fa-info-circle ms-1"></i>
                                </span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('units.toggle-active', $unit) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $unit->is_active ? 'btn-success' : 'btn-danger' }}">
                                        @if($unit->is_active)
                                        <i class="fas fa-check-circle me-1"></i> نشط
                                        @else
                                        <i class="fas fa-times-circle me-1"></i> غير نشط
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-info text-white">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if(!$unit->childUnits()->exists())
                                    <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوحدة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-box fa-2x text-muted mb-3 d-block"></i>
                                <p class="text-muted">لا توجد وحدات مضافة</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            html: true,
            template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner text-start" style="text-align: right !important;"></div></div>'
        });
    });
});
</script>
@endpush
@endsection 