@extends('layouts.app')

@section('title', 'إدارة أنواع الأسعار')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">أنواع الأسعار</h5>
                    @can('create-price-types')
                    <a href="{{ route('price-types.create') }}" class="btn btn-sm btn-light">
                        <i class="fas fa-plus me-1"></i> إضافة نوع جديد
                    </a>
                    @endcan
                </div>
                <div class="card-body">
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

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th>الترتيب</th>
                                    <th>افتراضي</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($priceTypes as $priceType)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $priceType->name }}</td>
                                    <td>{{ $priceType->sort_order }}</td>
                                    <td>
                                        @if($priceType->is_default)
                                            <span class="badge bg-success">نعم</span>
                                        @else
                                            <span class="badge bg-secondary">لا</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($priceType->is_active)
                                            <span class="badge bg-success">نشط</span>
                                        @else
                                            <span class="badge bg-danger">غير نشط</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @can('edit-price-types')
                                            <a href="{{ route('price-types.edit', $priceType) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <form action="{{ route('price-types.toggle-active', $priceType) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm {{ $priceType->is_active ? 'btn-warning' : 'btn-success' }}" {{ $priceType->is_default && $priceType->is_active ? 'disabled' : '' }}>
                                                    <i class="fas {{ $priceType->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                </button>
                                            </form>
                                            @endcan
                                            
                                            @can('delete-price-types')
                                            <form action="{{ route('price-types.destroy', $priceType) }}" method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" {{ $priceType->is_default ? 'disabled' : '' }}>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3">لا توجد أنواع أسعار.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            if (confirm('هل أنت متأكد من عملية الحذف؟')) {
                this.submit();
            }
        });
    });
</script>
@endsection 