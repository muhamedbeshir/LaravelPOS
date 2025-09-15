@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-shipping-fast text-primary"></i>
            تفاصيل شركة الشحن: {{ $shippingCompany->name }}
        </h4>
        <div>
            <a href="{{ route('shipping-companies.edit', $shippingCompany) }}" class="btn btn-warning text-white">
                <i class="fas fa-edit"></i> تعديل
            </a>
            <a href="{{ route('shipping-companies.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> رجوع
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">معلومات الشركة</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>اسم الشركة:</span>
                            <span class="fw-bold">{{ $shippingCompany->name }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>جهة الاتصال:</span>
                            <span>{{ $shippingCompany->contact_person ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>رقم الهاتف:</span>
                            <span>{{ $shippingCompany->phone ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>البريد الإلكتروني:</span>
                            <span>{{ $shippingCompany->email ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>العنوان:</span>
                            <span>{{ $shippingCompany->address ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>تكلفة الشحن الافتراضية:</span>
                            <span>{{ $shippingCompany->default_cost > 0 ? number_format($shippingCompany->default_cost, 2) : 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>الحالة:</span>
                            @if($shippingCompany->is_active)
                            <span class="badge bg-success">نشط</span>
                            @else
                            <span class="badge bg-danger">غير نشط</span>
                            @endif
                        </li>
                    </ul>
                </div>
                <div class="card-footer bg-light">
                    <h6 class="mb-2">ملاحظات:</h6>
                    <p class="mb-0">{{ $shippingCompany->notes ?? 'لا توجد ملاحظات' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">إحصائيات الشحن</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">إجمالي الشحنات</h6>
                                    <h2 class="mb-0">{{ $shippingCompany->deliveryTransactions->count() }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">الشحنات المكتملة</h6>
                                    <h2 class="mb-0">{{ $shippingCompany->completed_deliveries_count }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">الشحنات قيد التنفيذ</h6>
                                    <h2 class="mb-0">{{ $shippingCompany->pending_deliveries_count }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">إجمالي تكاليف الشحن</h6>
                                    <h2 class="mb-0">{{ number_format($shippingCompany->total_shipping_cost, 2) }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">معاملات التوصيل</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>العميل</th>
                                    <th>رقم الفاتورة</th>
                                    <th>رقم التتبع</th>
                                    <th>تكلفة الشحن</th>
                                    <th>تاريخ الشحن</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deliveries as $delivery)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $delivery->customer->name }}</td>
                                    <td>
                                        <a href="{{ route('invoices.show', $delivery->invoice_id) }}">
                                            {{ $delivery->invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $delivery->tracking_number ?? '-' }}</td>
                                    <td>{{ $delivery->shipping_cost ? number_format($delivery->shipping_cost, 2) : '-' }}</td>
                                    <td>{{ $delivery->shipped_at ? $delivery->shipped_at->format('Y-m-d') : '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $delivery->status->code == 'delivered_pending_payment' || $delivery->status->code == 'paid' ? 'success' : ($delivery->status->code == 'returned' ? 'danger' : 'warning') }}">
                                            {{ $delivery->status->name }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('delivery.show', $delivery->id) }}" class="btn btn-sm btn-info text-white" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد معاملات توصيل</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-center mt-3">
                        {{ $deliveries->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 