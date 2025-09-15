@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">تفاصيل فاتورة الشراء #{{ $purchase->invoice_number }}</h3>
                    <div>
                        <a href="{{ route('purchases.pdf', $purchase) }}" class="btn btn-secondary">
                            <i class="fas fa-file-pdf"></i> تصدير PDF
                        </a>
                        <a href="{{ route('purchases.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> عودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- معلومات المورد -->
                    <div class="col-md-4">
                        <div class="card bg-light h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-user-tie me-2"></i>معلومات المورد</h5>
                            </div>
                            <div class="card-body">
                                @if($purchase->supplier)
                                    <p class="mb-1"><strong>اسم المورد:</strong> {{ $purchase->supplier->name }}</p>
                                    <p class="mb-1"><strong>الشركة:</strong> {{ $purchase->supplier->company_name ?: 'غير محدد' }}</p>
                                    <p class="mb-1"><strong>رقم الهاتف:</strong> {{ $purchase->supplier->phone }}</p>
                                    <p class="mb-0"><strong>الرصيد الحالي:</strong> 
                                        <span class="{{ $purchase->supplier->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($purchase->supplier->remaining_amount, 2) }}
                                        </span>
                                    </p>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        لم يتم تحديد مورد لهذه الفاتورة
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- معلومات الفاتورة -->
                    <div class="col-md-4">
                        <div class="card bg-light h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-file-invoice me-2"></i>معلومات الفاتورة</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>رقم الفاتورة:</strong> {{ $purchase->invoice_number }}</p>
                                <p class="mb-1"><strong>تاريخ الشراء:</strong> 
                                    @if($purchase->purchase_date instanceof \Carbon\Carbon)
                                        {{ $purchase->purchase_date->format('Y-m-d') }}
                                    @elseif(is_string($purchase->purchase_date))
                                        {{ $purchase->purchase_date }}
                                    @else
                                        غير محدد
                                    @endif
                                </p>
                                <p class="mb-1"><strong>الموظف المستلم:</strong> 
                                    @if($purchase->employee)
                                        {{ $purchase->employee->name }}
                                    @else
                                        <span class="text-muted">غير محدد</span>
                                    @endif
                                </p>
                                <p class="mb-1"><strong>الحالة:</strong> <span class="badge bg-{{ $purchase->status == 'completed' ? 'success' : 'warning' }}">{{ __($purchase->status) }}</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- تفاصيل المنتجات -->
                    <h5>المنتجات المشتراة</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المنتج</th>
                                    <th>الوحدة</th>
                                    <th>الكمية</th>
                                    <th>سعر الشراء</th>
                                    <th>سعر البيع</th>
                                    <th>الإجمالي</th>
                                    <th>الربح المتوقع</th>
                                    <th>نسبة الربح</th>
                                    <th>تاريخ الصلاحية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product->name }}</td>
                                    <td>{{ $item->unit_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->purchase_price, 2) }}</td>
                                    <td>{{ number_format($item->selling_price, 2) }}</td>
                                    <td>{{ number_format($item->quantity * $item->purchase_price, 2) }}</td>
                                    <td>{{ number_format($item->expected_profit, 2) }}</td>
                                    <td>{{ number_format($item->profit_percentage, 2) }}%</td>
                                    <td>
                                        @if($item->expiry_date)
                                            @if($item->expiry_date instanceof \Carbon\Carbon)
                                                {{ $item->expiry_date->format('Y-m-d') }}
                                                @if($item->expiry_date->isPast())
                                                    <span class="badge badge-danger">منتهي الصلاحية</span>
                                                @elseif($item->expiry_date->diffInDays(now()) <= $item->alert_days_before_expiry)
                                                    <span class="badge badge-warning">يقترب من انتهاء الصلاحية</span>
                                                @endif
                                            @else
                                                {{ $item->expiry_date }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6">الإجمالي</th>
                                    <th>{{ number_format($purchase->total_amount, 2) }}</th>
                                    <th>{{ number_format($purchase->items->sum('expected_profit'), 2) }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- معلومات الدفع -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>ملاحظات</h5>
                            <p>{{ $purchase->notes ?: 'لا توجد ملاحظات' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>تفاصيل الدفع</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th>إجمالي الفاتورة:</th>
                                    <td>{{ number_format($purchase->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>المبلغ المدفوع:</th>
                                    <td>{{ number_format($purchase->paid_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>المبلغ المتبقي:</th>
                                    <td>{{ number_format($purchase->remaining_amount, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 