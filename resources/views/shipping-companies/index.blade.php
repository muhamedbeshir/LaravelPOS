@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-shipping-fast text-primary"></i>
            إدارة شركات الشحن
        </h4>
        <div>
            <a href="{{ route('shipping-companies.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> إضافة شركة شحن
            </a>
            <a href="{{ route('shipping-companies.report') }}" class="btn btn-info text-white">
                <i class="fas fa-chart-bar"></i> تقرير الشحن
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
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">قائمة شركات الشحن</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>اسم الشركة</th>
                            <th>جهة الاتصال</th>
                            <th>رقم الهاتف</th>
                            <th>البريد الإلكتروني</th>
                            <th>تكلفة الشحن الافتراضية</th>
                            <th>عدد الشحنات</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('shipping-companies.show', $company) }}">
                                    {{ $company->name }}
                                </a>
                            </td>
                            <td>{{ $company->contact_person ?? '-' }}</td>
                            <td>{{ $company->phone ?? '-' }}</td>
                            <td>{{ $company->email ?? '-' }}</td>
                            <td>{{ $company->default_cost > 0 ? number_format($company->default_cost, 2) : '-' }}</td>
                            <td>{{ $company->delivery_transactions_count }}</td>
                            <td>
                                @if($company->is_active)
                                <span class="badge bg-success">نشط</span>
                                @else
                                <span class="badge bg-danger">غير نشط</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('shipping-companies.show', $company) }}" class="btn btn-sm btn-info text-white" title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('shipping-companies.edit', $company) }}" class="btn btn-sm btn-warning text-white" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('shipping-companies.toggle-active', $company) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $company->is_active ? 'btn-secondary' : 'btn-success' }}" title="{{ $company->is_active ? 'تعطيل' : 'تفعيل' }}">
                                            <i class="fas fa-{{ $company->is_active ? 'ban' : 'check' }}"></i>
                                        </button>
                                    </form>
                                    @if($company->delivery_transactions_count == 0)
                                    <form action="{{ route('shipping-companies.destroy', $company) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">لا توجد شركات شحن</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تأكيد الحذف
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (confirm('هل أنت متأكد من حذف شركة الشحن؟')) {
                    this.submit();
                }
            });
        });
    });
</script>
@endpush 