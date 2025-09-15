@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تنبيهات الصلاحية</h3>
                </div>
                <div class="card-body">
                    @if($nearExpiryItems->isEmpty())
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> لا توجد منتجات تقترب من انتهاء الصلاحية
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>تاريخ الإنتاج</th>
                                        <th>تاريخ الصلاحية</th>
                                        <th>الأيام المتبقية</th>
                                        <th>رقم الفاتورة</th>
                                        <th>المورد</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($nearExpiryItems as $item)
                                    <tr class="{{ $item->expiry_date->diffInDays(now()) <= 7 ? 'table-danger' : 'table-warning' }}">
                                        <td>{{ $item->product->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->production_date ? $item->production_date->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $item->expiry_date->format('Y-m-d') }}</td>
                                        <td>
                                            {{ $item->expiry_date->diffInDays(now()) }}
                                            @if($item->expiry_date->diffInDays(now()) <= 7)
                                                <span class="badge badge-danger">تحذير</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('purchases.show', $item->purchase) }}">
                                                {{ $item->purchase->invoice_number }}
                                            </a>
                                        </td>
                                        <td>{{ $item->purchase->supplier->name }}</td>
                                        <td>
                                            @if($item->expiry_date->isPast())
                                                <span class="badge badge-danger">منتهي الصلاحية</span>
                                            @else
                                                <span class="badge badge-warning">يقترب من انتهاء الصلاحية</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- ملخص التنبيهات -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="info-box bg-danger">
                                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">منتجات في خطر</span>
                                        <span class="info-box-number">
                                            {{ $nearExpiryItems->where('expiry_date', '<=', now()->addDays(7))->count() }}
                                        </span>
                                        <span class="progress-description">
                                            تنتهي خلال 7 أيام
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box bg-warning">
                                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">منتجات تحت المراقبة</span>
                                        <span class="info-box-number">
                                            {{ $nearExpiryItems->where('expiry_date', '>', now()->addDays(7))->count() }}
                                        </span>
                                        <span class="progress-description">
                                            تنتهي بعد 7 أيام
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-boxes"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">إجمالي المنتجات</span>
                                        <span class="info-box-number">{{ $nearExpiryItems->sum('quantity') }}</span>
                                        <span class="progress-description">
                                            تحتاج إلى مراجعة
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // تحديث الصفحة كل 5 دقائق
        setInterval(function() {
            location.reload();
        }, 300000);
    });
</script>
@endpush 