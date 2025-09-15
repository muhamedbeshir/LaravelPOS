@extends('layouts.app')

@section('title', $pageTitle ?? 'معاملات الدليفري')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $pageTitle ?? 'معاملات الدليفري' }}</h3>
                </div>
                <div class="card-body">
                    <!-- منطقة الرسائل -->
                    <div id="alert-messages">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                    </div>
                    
                    <div class="mb-4">
                        <form action="{{ route('delivery-transactions.index') }}" method="GET" class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="status">الحالة</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">الكل</option>
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->code }}" {{ request('status') == $status->code ? 'selected' : '' }}>
                                                {{ $status->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="employee_id">موظف التوصيل</label>
                                    <select name="employee_id" id="employee_id" class="form-control">
                                        <option value="">الكل</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                                {{ $employee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_from">من تاريخ</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_to">إلى تاريخ</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary form-control">
                                        <i class="fas fa-search"></i> بحث
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <a href="{{ route('delivery-transactions.index') }}" class="btn btn-secondary form-control">
                                        <i class="fas fa-redo"></i> إعادة تعيين
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>رقم الفاتورة</th>
                                    <th>العميل</th>
                                    <th>موظف التوصيل</th>
                                    <th>المبلغ</th>
                                    <th>المحصل</th>
                                    <th>المتبقي</th>
                                    <th>الحالة</th>
                                    <th>وقت التوصيل</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->id }}</td>
                                        <td>
                                            <a href="{{ route('sales.invoices.print', $transaction->invoice_id) }}" target="_blank">
                                                {{ $transaction->invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td>{{ $transaction->customer->name }}</td>
                                        <td>{{ $transaction->employee->name }}</td>
                                        <td>{{ number_format($transaction->amount, 2) }}</td>
                                        <td>{{ number_format($transaction->collected_amount, 2) }}</td>
                                        <td>{{ number_format($transaction->remaining_amount, 2) }}</td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $transaction->status->color }}">
                                                {{ $transaction->status->name }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($transaction->delivery_date)
                                                {{ $transaction->delivery_date->format('Y-m-d H:i:s') }}
                                            @elseif($transaction->invoice->delivery_time)
                                                {{ $transaction->invoice->delivery_time->format('Y-m-d H:i:s') }}
                                                <span class="badge badge-info">من الفاتورة</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-delivery-btn" data-id="{{ $transaction->id }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">لا توجد معاملات دليفري</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة تفاصيل التوصيل المنبثقة -->
<div class="modal fade" id="delivery-status-modal" tabindex="-1" aria-labelledby="deliveryStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="deliveryStatusModalLabel">تفاصيل وتحديث حالة التوصيل</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <!-- منطقة رسائل النافذة المنبثقة -->
                <div id="modal-alert-messages"></div>
                
                <div id="delivery-transaction-details" class="mb-3">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <p>جاري تحميل البيانات...</p>
                    </div>
                </div>
                
                <hr>
                
                <form id="delivery-status-form">
                    <input type="hidden" id="delivery-transaction-id" name="transaction_id">
                    
                    <div class="mb-3">
                        <label for="delivery-status" class="form-label">تحديث الحالة إلى:</label>
                        <select class="form-select" id="delivery-status" name="status">
                            <option value="">-- اختر الحالة --</option>
                        </select>
                    </div>
                    
                    <div id="payment-amount-container" class="mb-3 d-none">
                        <label for="payment-amount" class="form-label">المبلغ المحصل:</label>
                        <input type="number" class="form-control" id="payment-amount" name="amount" step="0.01" min="0">
                    </div>
                    
                    <div id="return-notes-container" class="mb-3 d-none">
                        <label for="return-notes" class="form-label">ملاحظات الإرجاع:</label>
                        <textarea class="form-control" id="return-notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="update-delivery-status-btn">تحديث الحالة</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // وظائف عرض الرسائل
        function showAlert(type, message, container = '#alert-messages') {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertIcon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas ${alertIcon} me-2"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            `;
            
            $(container).html(alertHtml);
            
            // إزالة التنبيه تلقائياً بعد 5 ثوان
            setTimeout(() => {
                $(container).find('.alert').alert('close');
            }, 5000);
        }
        
        // إضافة حدث النقر على زر العرض
        $('.view-delivery-btn').on('click', function() {
            const transactionId = $(this).data('id');
            loadDeliveryDetails(transactionId);
        });

        // تحميل تفاصيل معاملة التوصيل
        function loadDeliveryDetails(transactionId) {
            // مسح أي رسائل سابقة
            $('#modal-alert-messages').html('');
            
            // عرض تحميل
            $('#delivery-transaction-details').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p>جاري تحميل البيانات...</p>
                </div>
            `);
            
            // حفظ رقم المعاملة
            $('#delivery-transaction-id').val(transactionId);
            
            // عرض النافذة المنبثقة
            $('#delivery-status-modal').modal('show');
            
            // جلب بيانات المعاملة
            $.ajax({
                url: `/delivery-transactions/${transactionId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        showAlert('error', response.message || 'حدث خطأ أثناء تحميل البيانات', '#modal-alert-messages');
                        return;
                    }
                    
                    const transaction = response.transaction;
                    
                    // تنسيق المبالغ
                    const formatCurrency = (amount) => parseFloat(amount || 0).toFixed(2) + ' جنيه';
                    
                    // تنسيق الحالة
                    const formatStatus = (status) => {
                        return `<span class="badge" style="background-color: ${status.color}">${status.name}</span>`;
                    };
                    
                    // تنسيق التاريخ
                    const formatDate = (dateStr) => {
                        if (!dateStr) return '—';
                        return new Date(dateStr).toLocaleString('ar-EG');
                    };
                    
                    // عرض تفاصيل المعاملة
                    $('#delivery-transaction-details').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>رقم الفاتورة:</strong> ${transaction.invoice.invoice_number}</p>
                                <p><strong>العميل:</strong> ${transaction.customer.name}</p>
                                <p><strong>موظف التوصيل:</strong> ${transaction.employee.name}</p>
                                <p><strong>وقت التوصيل:</strong> ${formatDate(transaction.delivery_date)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>المبلغ الإجمالي:</strong> ${formatCurrency(transaction.amount)}</p>
                                <p><strong>المبلغ المحصل:</strong> ${formatCurrency(transaction.collected_amount)}</p>
                                <p><strong>المبلغ المتبقي:</strong> ${formatCurrency(transaction.remaining_amount)}</p>
                                <p><strong>الحالة الحالية:</strong> ${formatStatus(transaction.status)}</p>
                            </div>
                        </div>
                        
                        <h6 class="mt-3">المنتجات:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>السعر</th>
                                        <th>الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${transaction.invoice.items.map(item => `
                                        <tr>
                                            <td>${item.product.name}</td>
                                            <td>${item.quantity} ${item.unit?.name || ''}</td>
                                            <td>${formatCurrency(item.unit_price)}</td>
                                            <td>${formatCurrency(item.total_price)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `);
                    
                    // تعبئة خيارات الحالة
                    populateStatusOptions(transaction.status.code);
                },
                error: function(xhr) {
                    showAlert('error', 'حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.', '#modal-alert-messages');
                    console.error('Error fetching delivery details:', xhr.responseText);
                }
            });
        }
        
        // تعبئة خيارات الحالة بناءً على الحالة الحالية
        function populateStatusOptions(currentStatus) {
            const statusSelect = $('#delivery-status');
            statusSelect.empty();
            
            // تحديد الحالات المتاحة للتغيير
            const nextStatuses = {
                'ready': [
                    {value: 'dispatched', text: 'خرج للتوصيل'}
                ],
                'dispatched': [
                    {value: 'delivered_pending_payment', text: 'تم التوصيل (بانتظار الدفع)'},
                    {value: 'returned', text: 'مرتجع'}
                ],
                'delivered_pending_payment': [
                    {value: 'paid', text: 'تم الدفع'},
                    {value: 'returned', text: 'مرتجع'}
                ],
                'paid': [],
                'returned': []
            };
            
            // الحصول على الحالات المتاحة للحالة الحالية
            const availableStatuses = nextStatuses[currentStatus] || [];
            
            // إذا لم تكن هناك حالات متاحة، عطل النموذج
            if (availableStatuses.length === 0) {
                statusSelect.append(`<option value="">لا يمكن تغيير الحالة</option>`);
                $('#update-delivery-status-btn').prop('disabled', true);
            } else {
                // إضافة الحالات المتاحة للقائمة المنسدلة
                statusSelect.append(`<option value="">-- اختر الحالة --</option>`);
                availableStatuses.forEach(status => {
                    statusSelect.append(`<option value="${status.value}">${status.text}</option>`);
                });
                $('#update-delivery-status-btn').prop('disabled', false);
            }
            
            // تشغيل حدث التغيير لتحديث حالة النموذج
            statusSelect.trigger('change');
        }
        
        // معالجة تغيير حالة التوصيل
        $('#delivery-status').on('change', function() {
            const newStatus = $(this).val();
            
            // عرض/إخفاء حقل المبلغ بناءً على الحالة
            if (newStatus === 'paid') {
                $('#payment-amount-container').removeClass('d-none');
                $('#return-notes-container').addClass('d-none');
            }
            // عرض/إخفاء حقل ملاحظات الإرجاع بناءً على الحالة
            else if (newStatus === 'returned') {
                $('#payment-amount-container').addClass('d-none');
                $('#return-notes-container').removeClass('d-none');
            }
            // إخفاء كلاهما للحالات الأخرى
            else {
                $('#payment-amount-container').addClass('d-none');
                $('#return-notes-container').addClass('d-none');
            }
        });
        
        // تحديث حالة التوصيل
        $('#update-delivery-status-btn').on('click', function() {
            const transactionId = $('#delivery-transaction-id').val();
            const newStatus = $('#delivery-status').val();
            
            // التحقق من صحة البيانات
            if (!transactionId || !newStatus) {
                showAlert('error', 'بيانات غير مكتملة', '#modal-alert-messages');
                return;
            }
            
            // الحصول على مبلغ الدفع إذا كان مرئياً
            let amount = null;
            if (!$('#payment-amount-container').hasClass('d-none')) {
                amount = parseFloat($('#payment-amount').val()) || 0;
            }
            
            // الحصول على ملاحظات الإرجاع إذا كانت مرئية
            let notes = null;
            if (!$('#return-notes-container').hasClass('d-none')) {
                notes = $('#return-notes').val();
            }
            
            // إظهار التحميل
            const updateBtn = $(this);
            updateBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري التحديث...');
            
            // إرسال طلب التحديث
            $.ajax({
                url: `/delivery-transactions/${transactionId}/update-status`,
                type: 'POST',
                data: {
                    status: newStatus,
                    amount: amount,
                    notes: notes,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    updateBtn.prop('disabled', false).html('تحديث الحالة');
                    
                    if (response.success) {
                        // عرض رسالة النجاح داخل النافذة المنبثقة
                        showAlert('success', 'تم تحديث حالة التوصيل بنجاح', '#modal-alert-messages');
                        
                        // إعادة تحميل بيانات المعاملة بعد ثانيتين
                        setTimeout(() => {
                            loadDeliveryDetails(transactionId);
                        }, 2000);
                    } else {
                        showAlert('error', response.message || 'خطأ غير معروف', '#modal-alert-messages');
                    }
                },
                error: function(xhr) {
                    updateBtn.prop('disabled', false).html('تحديث الحالة');
                    showAlert('error', 'حدث خطأ أثناء تحديث حالة التوصيل', '#modal-alert-messages');
                    console.error('Error updating delivery status:', xhr.responseText);
                }
            });
        });
    });
</script>
@endpush 