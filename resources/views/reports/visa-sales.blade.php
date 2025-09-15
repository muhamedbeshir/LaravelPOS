@extends('layouts.app')

@section('title', 'تقرير مبيعات فيزا')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fab fa-cc-visa me-2"></i>تقرير مبيعات فيزا</h5>
        </div>
        <div class="card-body">
            <!-- Summary boxes -->
            <div class="row mb-4">
                <div class="col-lg-6 col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي فواتير الفيزا</h5>
                            <h2 class="mb-0">{{ number_format($invoices->total()) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي مبيعات الفيزا</h5>
                            <h2 class="mb-0">{{ number_format($totalVisaSales, 2) }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoices table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>التاريخ</th>
                            <th>الإجمالي</th>
                            <th>العميل</th>
                            <th>المدفوع</th>
                            <th>المتبقي</th>
                            <th>العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        @php
                            $amount = $invoice->type === 'mixed' ? $invoice->payments->where('method','visa')->sum('amount') : $invoice->total;
                        @endphp
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ number_format($amount, 2) }}</td>
                            <td>{{ $invoice->customer->name ?? 'غير محدد' }}</td>
                            <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                            <td>{{ number_format($invoice->remaining_amount, 2) }}</td>
                            <td>
                                <button class="btn btn-sm btn-primary view-invoice" data-id="{{ $invoice->id }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>لا توجد فواتير فيزا</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">{{ $invoices->links() }}</div>
        </div>
    </div>
</div>

<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="invoiceDetailsModalLabel">تفاصيل الفاتورة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="invoice-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
                <div id="invoice-content" style="display: none;">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <a href="#" class="btn btn-primary" id="print-invoice-btn" target="_blank">
                    <i class="fas fa-print me-1"></i> طباعة
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.view-invoice').click(function(e) {
        e.preventDefault();
        const invoiceId = $(this).data('id');
        
        $('#invoice-loading').show();
        $('#invoice-content').hide().empty();
        $('#invoiceDetailsModal').modal('show');
        
        $('#print-invoice-btn').attr('href', `{{ url('sales/invoices') }}/${invoiceId}/print`);
        
        $.ajax({
            url: `{{ url('api/sales/invoices') }}/${invoiceId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const invoice = response.invoice;
                    const content = `
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold">معلومات الفاتورة</h6>
                                <p><strong>رقم الفاتورة:</strong> ${invoice.invoice_number}</p>
                                <p><strong>التاريخ:</strong> ${new Date(invoice.created_at).toLocaleString()}</p>
                                <p><strong>الحالة:</strong> <span class="badge bg-success">${invoice.status}</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">معلومات العميل</h6>
                                <p><strong>الاسم:</strong> ${invoice.customer ? invoice.customer.name : 'غير متوفر'}</p>
                                <p><strong>الهاتف:</strong> ${invoice.customer ? invoice.customer.phone : 'غير متوفر'}</p>
                            </div>
                        </div>
                        <h6 class="fw-bold">المنتجات</h6>
                        <table class="table table-bordered">
                            <thead>
                                <tr><th>المنتج</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr>
                            </thead>
                            <tbody>
                                ${invoice.items.map(item => `
                                    <tr>
                                        <td>${item.product.name}</td>
                                        <td>${item.quantity}</td>
                                        <td>${Number(item.unit_price).toFixed(2)}</td>
                                        <td>${Number(item.total_price).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        <div class="row mt-3">
                             <div class="col-md-6 offset-md-6">
                                <table class="table">
                                    <tr><th>الإجمالي الفرعي</th><td>${Number(invoice.subtotal).toFixed(2)}</td></tr>
                                    <tr><th>الخصم</th><td>${Number(invoice.discount_value).toFixed(2)}</td></tr>
                                    <tr><th>الإجمالي</th><td><strong>${Number(invoice.total).toFixed(2)}</strong></td></tr>
                                    <tr><th>المدفوع</th><td>${Number(invoice.paid_amount).toFixed(2)}</td></tr>
                                    <tr><th>المتبقي</th><td>${Number(invoice.remaining_amount).toFixed(2)}</td></tr>
                                </table>
                            </div>
                        </div>
                    `;
                    $('#invoice-content').html(content);
                    $('#invoice-loading').hide();
                    $('#invoice-content').show();
                } else {
                    $('#invoice-content').html('<p class="text-danger">فشل تحميل بيانات الفاتورة.</p>');
                }
            },
            error: function() {
                 $('#invoice-content').html('<p class="text-danger">خطأ في الاتصال بالخادم.</p>');
                 $('#invoice-loading').hide();
                 $('#invoice-content').show();
            }
        });
    });
});
</script>
@endpush 