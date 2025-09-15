@extends('layouts.app')

@section('title', 'الموردين')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة الموردين</h2>
        <div>
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة مورد جديد
            </a>
            <a href="{{ route('suppliers.export') }}" class="btn btn-secondary">
                <i class="fas fa-file-export me-1"></i>
                تصدير الموردين
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

    <!-- أدوات التصفية -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nameFilter">البحث بالاسم</label>
                        <input type="text" class="form-control" id="nameFilter" placeholder="اسم المورد أو الشركة">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="statusFilter">حالة الدفع</label>
                        <select class="form-control" id="statusFilter">
                            <option value="">الكل</option>
                            <option value="unpaid">ليه فلوس</option>
                            <option value="paid">تم السداد</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary form-control" onclick="applyFilters()">
                            <i class="fas fa-filter me-1"></i>
                            تصفية
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="suppliersTable">
                    <thead class="table-light">
                        <tr>
                            <th>المورد</th>
                            <th>رقم الهاتف</th>
                            <th>إجمالي المستحقات</th>
                            <th>المبلغ المدفوع</th>
                            <th>المبلغ المتبقي</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                        <tr>
                            <td>
                                <strong>{{ $supplier->name }}</strong>
                                @if($supplier->company_name)
                                <br>
                                <small class="text-muted">{{ $supplier->company_name }}</small>
                                @endif
                            </td>
                            <td>{{ $supplier->phone }}</td>
                            <td>{{ number_format($supplier->total_amount, 2) }}</td>
                            <td>{{ number_format($supplier->paid_amount, 2) }}</td>
                            <td>{{ number_format($supplier->remaining_amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $supplier->getStatusClass() }}">
                                    {{ $supplier->getStatusText() }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-info text-white">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($supplier->hasUnpaidInvoices())
                                    <a href="{{ route('supplier-payments.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-success">
                                        <i class="fas fa-dollar-sign"></i>
                                    </a>
                                    @endif
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا المورد؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">لا يوجد موردين مضافين</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal تفاصيل المورد -->
<div class="modal fade" id="supplierDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل المورد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>البيانات الأساسية</h5>
                        <table class="table table-striped">
                            <tr>
                                <th>اسم المورد</th>
                                <td id="supplierName"></td>
                            </tr>
                            <tr>
                                <th>اسم الشركة</th>
                                <td id="companyName"></td>
                            </tr>
                            <tr>
                                <th>رقم الهاتف</th>
                                <td id="supplierPhone"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>المستحقات المالية</h5>
                        <table class="table table-striped">
                            <tr>
                                <th>إجمالي المستحقات</th>
                                <td id="totalAmount"></td>
                            </tr>
                            <tr>
                                <th>المبلغ المدفوع</th>
                                <td id="paidAmount"></td>
                            </tr>
                            <tr>
                                <th>المبلغ المتبقي</th>
                                <td id="remainingAmount"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>الفواتير المستحقة</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dueInvoicesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>المبلغ</th>
                                    <th>تاريخ الاستحقاق</th>
                                    <th>المبلغ المتبقي</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('/assets/dataTables.bootstrap5.min.css') }}">
<style>
    .badge {
        font-size: 0.9rem;
        padding: 0.5rem;
    }
    .table th, .table td {
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('/assets/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('/assets/dataTables.bootstrap5.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة DataTable
    const table = $('#suppliersTable').DataTable({
        "language": {
            "url": "{{ asset('js/datatables/i18n/ar.json') }}"
        },
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [5, 10, 25, 50],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    // عرض تفاصيل المورد
    const supplierDetailsModal = new bootstrap.Modal(document.getElementById('supplierDetailsModal'));
    
    document.querySelectorAll('.show-supplier-details').forEach(button => {
        button.addEventListener('click', function() {
            const supplierId = this.dataset.supplierId;
        
            // Fetch supplier data via AJAX
            fetch(`/suppliers/${supplierId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('supplierName').textContent = data.supplier.name;
                    document.getElementById('companyName').textContent = data.supplier.company_name || '-';
                    document.getElementById('supplierPhone').textContent = data.supplier.phone;
                    document.getElementById('totalAmount').textContent = parseFloat(data.supplier.total_amount).toFixed(2);
                    document.getElementById('paidAmount').textContent = parseFloat(data.supplier.paid_amount).toFixed(2);
                    document.getElementById('remainingAmount').textContent = parseFloat(data.supplier.remaining_amount).toFixed(2);
                
                    const dueInvoicesTbody = document.querySelector('#dueInvoicesTable tbody');
                    dueInvoicesTbody.innerHTML = '';
                    if(data.dueInvoices.length > 0) {
                data.dueInvoices.forEach(invoice => {
                            // تنسيق تاريخ الاستحقاق
                            const dueDate = invoice.due_date ? new Date(invoice.due_date).toLocaleDateString('ar-EG') : '-';
                            
                            dueInvoicesTbody.innerHTML += `
                        <tr>
                            <td>${invoice.invoice_number}</td>
                                    <td>${parseFloat(invoice.amount).toFixed(2)}</td>
                                    <td>${dueDate}</td>
                                    <td>${parseFloat(invoice.remaining_amount).toFixed(2)}</td>
                                    <td><span class="badge ${invoice.status_class}">${invoice.status_text}</span></td>
                        </tr>
                            `;
                });
                    } else {
                        dueInvoicesTbody.innerHTML = '<tr><td colspan="5" class="text-center">لا توجد فواتير مستحقة حالياً.</td></tr>';
                    }
                
                supplierDetailsModal.show();
                });
        });
    });

    // تصفية البيانات
    function applyFilters() {
        const nameFilter = $('#nameFilter').val().toLowerCase();
        const statusFilter = $('#statusFilter').val();
        
        table.rows().every(function() {
            const row = this.node();
            const name = $(row).find('td:first').text().toLowerCase();
            const statusText = $(row).find('.badge').text().trim();
            let statusMatch = false;
            if(statusFilter === 'unpaid' && statusText === 'له مستحقات') {
                statusMatch = true;
            } else if (statusFilter === 'paid' && statusText === 'تم السداد') {
                statusMatch = true;
            } else if (statusFilter === '') {
                statusMatch = true;
            }
            
            if (name.includes(nameFilter) && statusMatch) {
                $(row).show();
            } else {
                $(row).hide();
            }
        });
        
        table.draw();
    }

    // تحديث التنبيهات
    function updateNotifications() {
        $.ajax({
            url: '/suppliers-notifications',
            method: 'GET',
            success: function(notifications) {
                // يمكنك هنا إضافة منطق لعرض التنبيهات
                console.log('Notifications:', notifications);
            }
        });
    }

    // تحديث التنبيهات كل 5 دقائق
    setInterval(updateNotifications, 300000);
    updateNotifications();
});
</script>
@endpush
@endsection 