@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">بيانات العميل - {{ $customer->name }}</h5>
                    <div>
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-right"></i> رجوع
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <th class="bg-light" width="30%">اسم العميل</th>
                                    <td>{{ $customer->name }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">رقم الهاتف</th>
                                    <td>
                                        <a href="tel:{{ $customer->phone }}" class="text-decoration-none">
                                            {{ $customer->phone }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">العنوان</th>
                                    <td>{{ $customer->address ?: 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">الرصيد الحالي</th>
                                    <td class="{{ $customer->credit_balance < 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                        {{ number_format($customer->credit_balance, 2) }}
                                        @if($customer->credit_balance < 0)
                                            <small class="d-block text-muted">مديونية عليه (دين)</small>
                                        @elseif($customer->credit_balance > 0)
                                            <small class="d-block text-muted">مديونية له (دائن)</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">حد الائتمان</th>
                                    <td>
                                        @if($customer->is_unlimited_credit)
                                            <span class="badge bg-primary">غير محدود</span>
                                        @else
                                            {{ number_format($customer->credit_limit, 2) }}
                                            @if($customer->credit_limit > 0 && $customer->credit_balance < 0)
                                                <div class="mt-1 progress" style="height: 8px;">
                                                    @php 
                                                        $percentage = min(100, (abs($customer->credit_balance) / $customer->credit_limit) * 100);
                                                        $colorClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                                                    @endphp
                                                    <div class="progress-bar {{ $colorClass }}" role="progressbar" 
                                                         style="width: {{ $percentage }}%;" 
                                                         aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted">استخدام {{ round($percentage) }}% من حد الائتمان</small>
                                            @elseif($customer->credit_limit == 0)
                                                <span class="badge bg-secondary">غير محدد</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">معلومات إضافية</th>
                                    <td>{{ $customer->notes ?: 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">السعر الافتراضي</th>
                                    <td>
                                        @if($customer->defaultPriceType)
                                            <span class="badge bg-info">{{ $customer->defaultPriceType->name }}</span>
                                            <small class="d-block text-muted">يتجاهل الإعدادات العامة</small>
                                        @else
                                            <span class="text-muted">يستخدم الإعدادات العامة</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card bg-info text-white h-100">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">عدد الفواتير</h6>
                                            <h3 class="mb-0">{{ $stats['total_invoices'] }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-success text-white h-100">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">إجمالي المبيعات</h6>
                                            <h3 class="mb-0">{{ number_format($stats['total_amount'], 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-primary text-white h-100">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">إجمالي المدفوعات</h6>
                                            <h3 class="mb-0">{{ number_format($stats['total_paid'], 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card {{ $stats['remaining_balance'] > 0 ? 'bg-danger' : 'bg-success' }} text-white h-100">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">الرصيد المتبقي</h6>
                                            <h3 class="mb-0">{{ number_format($stats['remaining_balance'], 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">الفواتير المرتبطة</h5>
                </div>
                <div class="card-body">
                    @if($customer->invoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>التاريخ</th>
                                    <th>المبلغ</th>
                                    <th>حالة الدفع</th>
                                    <th>العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $invoice->payment_status == 'paid' ? 'bg-success' : 'bg-warning' }}">
                                            {{ $invoice->payment_status == 'paid' ? 'مدفوع' : 'غير مدفوع' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                        <a href="{{ route('invoices.print', $invoice->id) }}" class="btn btn-sm btn-secondary" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <p>لا توجد فواتير مسجلة لهذا العميل</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">سجل المدفوعات</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الدفع</th>
                                    <th>الرصيد المتبقي</th>
                                    <th>التاريخ</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customer->payments as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-success">{{ number_format($payment->amount, 2) }}</td>
                                    <td>
                                        @switch($payment->payment_method)
                                            @case('cash')
                                                <span class="badge bg-success">نقدي</span>
                                                @break
                                            @case('bank')
                                                <span class="badge bg-primary">تحويل بنكي</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $payment->payment_method }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-danger">{{ number_format($payment->remaining_balance, 2) }}</td>
                                    <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $payment->notes ?: '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> لا توجد مدفوعات مسجلة
                                    </td>
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