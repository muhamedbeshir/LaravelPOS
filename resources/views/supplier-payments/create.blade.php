@extends('layouts.app')

@section('title', 'تسجيل دفعة لمورد')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">تسجيل دفعة جديدة لمورد</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('supplier-payments.store') }}" method="POST" id="payment-form">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="supplier_id">اختر المورد <span class="text-danger">*</span></label>
                            <select class="form-control" id="supplier_id" name="supplier_id" required>
                                <option value="">-- اختر مورد --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" data-balance="{{ $supplier->remaining_amount }}">{{ $supplier->name }} (الرصيد: {{ number_format($supplier->remaining_amount, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="payment_date">تاريخ الدفعة <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="payment_method">طريقة الدفع <span class="text-danger">*</span></label>
                            <select class="form-control" id="payment_method" name="payment_method" required>
                                <option value="cash">نقداً</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="check">شيك</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                     <div class="col-md-4">
                        <div class="form-group">
                            <label for="amount">مبلغ الدفعة <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required placeholder="أدخل مبلغ الدفعة">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">تخصيص الدفعة على الفواتير المستحقة</h5>
                <div id="invoices-section" class="table-responsive">
                    <p class="text-center" id="invoice-placeholder">الرجاء اختيار مورد لعرض فواتيره المستحقة.</p>
                    <table class="table table-bordered" id="invoices-table" style="display: none;">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>المبلغ الإجمالي</th>
                                <th>المبلغ المتبقي</th>
                                <th>المبلغ المدفوع من هذه الدفعة</th>
                            </tr>
                        </thead>
                        <tbody id="invoices-tbody">
                            <!-- Invoices will be loaded here by JS -->
                        </tbody>
                    </table>
                </div>

                <hr>

                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between">
                            <strong>إجمالي الدفعة:</strong>
                            <span id="total-payment">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <strong>الإجمالي المخصص للفواتير:</strong>
                            <span id="total-allocated">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between font-weight-bold">
                            <strong>المبلغ غير المخصص:</strong>
                            <span id="unallocated-amount">0.00</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">حفظ الدفعة</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const supplierSelect = document.getElementById('supplier_id');
    const amountInput = document.getElementById('amount');
    const invoicesTbody = document.getElementById('invoices-tbody');
    const invoicesTable = document.getElementById('invoices-table');
    const invoicePlaceholder = document.getElementById('invoice-placeholder');
    const totalPaymentSpan = document.getElementById('total-payment');
    const totalAllocatedSpan = document.getElementById('total-allocated');
    const unallocatedAmountSpan = document.getElementById('unallocated-amount');
    const paymentForm = document.getElementById('payment-form');

    supplierSelect.addEventListener('change', fetchInvoices);
    amountInput.addEventListener('input', updateSummaryAndAllocate);
    invoicesTbody.addEventListener('input', handleAllocationInput);

    function fetchInvoices() {
        const supplierId = supplierSelect.value;
        if (!supplierId) {
            resetInvoiceSection();
            return;
        }

        invoicePlaceholder.textContent = 'جاري تحميل الفواتير...';
        invoicesTable.style.display = 'none';
        invoicesTbody.innerHTML = '';

        fetch(`/supplier-payments/get-invoices/${supplierId}`)
            .then(response => response.json())
            .then(invoices => {
                if (invoices.length > 0) {
                    invoicePlaceholder.style.display = 'none';
                    invoicesTable.style.display = '';
                    invoices.forEach(invoice => {
                        const row = `
                            <tr>
                                <td>${invoice.invoice_number}</td>
                                <td>${invoice.due_date}</td>
                                <td>${parseFloat(invoice.amount).toFixed(2)}</td>
                                <td class="remaining-amount">${parseFloat(invoice.remaining_amount).toFixed(2)}</td>
                                <td>
                                    <input type="number" step="0.01" class="form-control allocation-input" name="allocations[${invoice.id}]" 
                                           data-invoice-id="${invoice.id}" data-max="${invoice.remaining_amount}" value="0.00" min="0">
                                </td>
                            </tr>
                        `;
                        invoicesTbody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    invoicePlaceholder.textContent = 'لا توجد فواتير مستحقة لهذا المورد.';
                    invoicePlaceholder.style.display = '';
                }
                updateSummaryAndAllocate();
            })
            .catch(error => {
                console.error('Error fetching invoices:', error);
                invoicePlaceholder.textContent = 'حدث خطأ أثناء تحميل الفواتير.';
            });
    }
    
    function updateSummaryAndAllocate() {
        updateSummary();
        autoAllocate();
    }

    function handleAllocationInput() {
        // When user manually changes an allocation, just update the summary
        updateSummary();
    }

    function autoAllocate() {
        let totalPayment = parseFloat(amountInput.value) || 0;
        const allocationInputs = document.querySelectorAll('.allocation-input');
        
        // Reset all allocations first
        allocationInputs.forEach(input => input.value = '0.00');

        allocationInputs.forEach(input => {
            if (totalPayment <= 0) return;

            const remainingAmount = parseFloat(input.dataset.max);
            const amountToAllocate = Math.min(totalPayment, remainingAmount);
            
            input.value = amountToAllocate.toFixed(2);
            totalPayment -= amountToAllocate;
        });
        
        updateSummary();
    }

    function updateSummary() {
        const totalPayment = parseFloat(amountInput.value) || 0;
        let totalAllocated = 0;
        document.querySelectorAll('.allocation-input').forEach(input => {
            totalAllocated += parseFloat(input.value) || 0;
        });

        const unallocatedAmount = totalPayment - totalAllocated;

        totalPaymentSpan.textContent = totalPayment.toFixed(2);
        totalAllocatedSpan.textContent = totalAllocated.toFixed(2);
        unallocatedAmountSpan.textContent = unallocatedAmount.toFixed(2);
        
        if (unallocatedAmount < 0) {
            unallocatedAmountSpan.classList.add('text-danger');
        } else {
            unallocatedAmountSpan.classList.remove('text-danger');
        }
    }
    
    function resetInvoiceSection() {
        invoicesTbody.innerHTML = '';
        invoicesTable.style.display = 'none';
        invoicePlaceholder.textContent = 'الرجاء اختيار مورد لعرض فواتيره المستحقة.';
        invoicePlaceholder.style.display = '';
        updateSummary();
    }

    paymentForm.addEventListener('submit', function(e) {
        const totalPayment = parseFloat(amountInput.value) || 0;
        let totalAllocated = 0;
        document.querySelectorAll('.allocation-input').forEach(input => {
            totalAllocated += parseFloat(input.value) || 0;
        });

        if (totalAllocated > totalPayment) {
            e.preventDefault();
            alert('خطأ: المبلغ المخصص للفواتير لا يمكن أن يكون أكبر من إجمالي مبلغ الدفعة.');
            return;
        }

        if (totalAllocated <= 0 && totalPayment > 0) {
             if (!confirm('مبلغ الدفعة أكبر من المبلغ المخصص للفواتير. هل تريد المتابعة وحفظ المبلغ المتبقي كرصيد للمورد؟')) {
                 e.preventDefault();
             }
        }
    });
});
</script>
@endpush 