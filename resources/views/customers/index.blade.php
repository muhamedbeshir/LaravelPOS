@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Hidden form for customer deletion -->
    <form id="deleteCustomerForm" action="" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">إجمالي العملاء</h6>
                    <h3>{{ $stats['total_customers'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-title">العملاء برصيد</h6>
                    <h3>{{ $stats['customers_with_balance'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">إجمالي الرصيد</h6>
                    <h3>{{ number_format($stats['total_balance'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">مدفوعات اليوم</h6>
                    <h3>{{ number_format($stats['today_payments'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">إدارة العملاء</h5>
            <div>
                <button type="button" class="btn btn-outline-primary me-2" onclick="exportCustomers()">
                    <i class="fas fa-file-export"></i> تصدير
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="fas fa-plus"></i> إضافة عميل جديد
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Buttons -->
            <div class="mb-4">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn {{ !request('balance_filter') ? 'btn-primary' : 'btn-outline-primary' }}" 
                            onclick="applyFilter('')">
                        الكل
                    </button>
                    <button type="button" class="btn {{ request('balance_filter') == 'negative' ? 'btn-primary' : 'btn-outline-primary' }}" 
                            onclick="applyFilter('negative')">
                        عليه مديونية (دين)
                    </button>
                    <button type="button" class="btn {{ request('balance_filter') == 'positive' ? 'btn-primary' : 'btn-outline-primary' }}" 
                            onclick="applyFilter('positive')">
                        له رصيد (دائن)
                    </button>
                    <button type="button" class="btn {{ request('balance_filter') == 'zero' ? 'btn-primary' : 'btn-outline-primary' }}" 
                            onclick="applyFilter('zero')">
                        رصيده صفر
                    </button>
                </div>
            </div>

            <!-- Search Form -->
            <form id="searchForm" action="{{ route('customers.index') }}" method="GET" class="row g-3 mb-4">
                <!-- Preserve current filter when searching -->
                @if(request('balance_filter'))
                <input type="hidden" name="balance_filter" value="{{ request('balance_filter') }}">
                @endif
                
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" name="search" class="form-control" placeholder="بحث بالاسم أو رقم الهاتف..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <div id="tableLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري البحث...</span>
                    </div>
                    <p class="mt-2">جاري البحث...</p>
                </div>

                <div id="customerTableContainer">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>اسم العميل</th>
                            <th>رقم الهاتف</th>
                            <th>الرصيد</th>
                            <th>حد الائتمان</th>
                            <th>نقاط الولاء</th>
                            <th>الحالة</th>
                            <th>العمليات</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $customer->name }}</div>
                                @if($customer->notes)
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($customer->notes, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <a href="tel:{{ $customer->phone }}" class="text-decoration-none">
                                    {{ $customer->phone }}
                                </a>
                            </td>
                            <td class="{{ $customer->credit_balance < 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                {{ number_format($customer->credit_balance, 2) }}
                                @if($customer->credit_balance < 0)
                                    <small class="d-block text-muted">مديونية عليه</small>
                                @elseif($customer->credit_balance > 0)
                                    <small class="d-block text-muted">مديونية له</small>
                                @endif
                            </td>
                            <td>
                                @if($customer->is_unlimited_credit)
                                    <span class="badge bg-primary">غير محدود</span>
                                @else
                                    {{ number_format($customer->credit_limit, 2) }}
                                    @if($customer->credit_limit > 0 && $customer->credit_balance < 0)
                                        @php 
                                            $percentage = min(100, (abs($customer->credit_balance) / $customer->credit_limit) * 100);
                                            $badgeClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                                        @endphp
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar {{ $badgeClass }}" role="progressbar" 
                                                 style="width: {{ $percentage }}%" 
                                                 aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="fw-bold text-primary">{{ number_format($customer->total_loyalty_points) }}</div>
                                @if($customer->total_loyalty_points >= 50)
                                    <small class="text-success">قابل للاستبدال</small>
                                @elseif($customer->total_loyalty_points > 0)
                                    <small class="text-warning">غير كافي</small>
                                @else
                                    <small class="text-muted">لا توجد نقاط</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $customer->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $customer->is_active ? 'نشط' : 'غير نشط' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info" onclick="viewCustomerInfo({{ $customer->id }})" title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="viewInvoices({{ $customer->id }})" title="عرض الفواتير">
                                        <i class="fas fa-file-invoice"></i>
                                    </button>
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-success" onclick="addPayment({{ $customer->id }}, 'balance')" title="إضافة رصيد">
                                        <i class="fas fa-coins"></i>
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="manageLoyaltyPoints({{ $customer->id }})" title="إدارة نقاط الولاء">
                                        <i class="fas fa-star"></i>
                                    </button>
                                    @if($customer->credit_balance < 0)
                                    <button type="button" class="btn btn-success" onclick="addPayment({{ $customer->id }}, 'debt')" title="إضافة دفعة">
                                        <i class="fas fa-dollar-sign"></i>
                                    </button>
                                    @endif
                                    <button type="button" class="btn btn-danger" onclick="deleteCustomer({{ $customer->id }})" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                                لا يوجد عملاء مطابقين لمعايير البحث
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            
            <div class="mt-4" id="paginationContainer">
                {{ $customers->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة عميل جديد -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة عميل جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCustomerForm" action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم العميل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">حد الائتمان</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="customer-credit-limit" name="credit_limit" value="0" step="0.01" min="0">
                            <span class="input-group-text">جنيه</span>
                        </div>
                        <small class="text-muted">الحد الأقصى للرصيد المسموح به للعميل</small>
                        
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="customer-unlimited-credit">
                            <label class="form-check-label" for="customer-unlimited-credit">
                                <span class="text-primary">ائتمان غير محدود</span>
                            </label>
                            <input type="hidden" name="has_unlimited_credit" id="customer-has-unlimited-credit" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السعر الافتراضي</label>
                        <select class="form-control" name="default_price_type_id">
                            <option value="">استخدام الإعدادات العامة</option>
                            @foreach($priceTypes as $priceType)
                            <option value="{{ $priceType->id }}">{{ $priceType->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">إذا تم اختيار سعر افتراضي، سيتم تجاهل الإعدادات العامة لهذا العميل</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">العنوان</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">معلومات إضافية</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal إضافة دفعة -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModalLabel">إضافة دفعة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
                <div class="modal-body">
                <form id="addPaymentForm">
                    <input type="hidden" id="payment_customer_id" name="customer_id">
                    <input type="hidden" id="payment_type" name="payment_type" value="debt">
                    <input type="hidden" id="customer_credit_balance" name="customer_credit_balance" value="0">
                    
                    <div class="alert alert-info mb-3" id="payment_info_alert">
                        <strong>رصيد العميل الحالي: <span id="current_balance_display">0.00</span></strong>
                        <p class="mb-0 small">يمكن إضافة دفعة أكبر من الرصيد المستحق</p>
                        <p class="mb-0 small">يمكن إدخال قيمة سالبة لسحب من الرصيد</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_amount">المبلغ</label>
                        <input type="number" class="form-control" id="payment_amount" name="amount" step="0.01" required>
                        <div class="invalid-feedback" id="amount_feedback">يرجى إدخال قيمة غير صفرية (موجبة أو سالبة)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">طريقة الدفع</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="cash">نقداً</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="check">شيك</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_notes">ملاحظات</label>
                        <textarea class="form-control" id="payment_notes" name="notes"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="savePayment">حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal بيانات العميل -->
<div class="modal fade" id="customerInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">بيانات العميل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                <div class="modal-body">
                <div id="customerInfoLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل بيانات العميل...</p>
                </div>
                <div id="customerInfoContent" class="d-none">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="fw-bold">بيانات أساسية</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th class="w-35">الاسم</th>
                                        <td id="customerName"></td>
                                    </tr>
                                    <tr>
                                        <th>رقم الهاتف</th>
                                        <td id="customerPhone"></td>
                                    </tr>
                                    <tr>
                                        <th>العنوان</th>
                                        <td id="customerAddress"></td>
                                    </tr>
                                    <tr>
                                        <th>الرصيد</th>
                                        <td id="customerBalance"></td>
                                    </tr>
                                    <tr>
                                        <th>حد الائتمان</th>
                                        <td>
                                            <div id="customerCreditLimit"></div>
                                            <div id="customerCreditLimitUsage" class="d-none">
                                                <div class="mt-1 progress" style="height: 8px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: 0%;" 
                                                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="mt-1">
                                                    استخدام <span id="creditUsagePercentage">0%</span> من حد الائتمان
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                    <div class="mb-3">
                                <h6 class="fw-bold">إحصائيات</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="border rounded p-2 text-center bg-light">
                                            <div class="small text-muted">عدد الفواتير</div>
                                            <div class="fs-5 fw-bold" id="customerInvoicesCount">0</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2 text-center bg-light">
                                            <div class="small text-muted">إجمالي المبيعات</div>
                                            <div class="fs-5 fw-bold" id="customerTotalSales">0</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2 text-center bg-light">
                                            <div class="small text-muted">إجمالي المدفوعات</div>
                                            <div class="fs-5 fw-bold" id="customerTotalPayments">0</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2 text-center bg-light">
                                            <div class="small text-muted">تاريخ التسجيل</div>
                                            <div class="fs-5 fw-bold" id="customerCreatedAt">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h6 class="fw-bold">ملاحظات</h6>
                        <p id="customerNotes" class="border rounded p-2 bg-light">-</p>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                <a href="#" id="customerEditLink" class="btn btn-warning">
                    <i class="fas fa-edit"></i> تعديل
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal فواتير العميل -->
<div class="modal fade" id="customerInvoicesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">فواتير العميل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="invoicesLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل فواتير العميل...</p>
                </div>
                <div id="invoicesContent" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>التاريخ</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>العمليات</th>
                                </tr>
                            </thead>
                            <tbody id="invoicesTableBody">
                                <!-- سيتم إضافة الفواتير هنا بواسطة جافاسكريبت -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="noInvoicesMessage" class="text-center py-5 d-none">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <p>لا توجد فواتير لهذا العميل</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal إدارة نقاط الولاء -->
<div class="modal fade" id="loyaltyPointsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إدارة نقاط الولاء</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="loyaltyPointsLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل بيانات نقاط الولاء...</p>
                </div>
                <div id="loyaltyPointsContent" class="d-none">
                    <!-- Customer loyalty summary -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">النقاط الحالية</h6>
                                    <h3 class="mb-0" id="currentLoyaltyPoints">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">قيمة النقاط بالجنيه</h6>
                                    <h3 class="mb-0" id="pointsValueInCurrency">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-success" onclick="showRedeemToBalanceForm()">
                                    <i class="fas fa-exchange-alt"></i> تحويل إلى رصيد
                                </button>
                                <button type="button" class="btn btn-warning" onclick="showAdjustPointsForm()">
                                    <i class="fas fa-edit"></i> تعديل النقاط
                                </button>
                                <button type="button" class="btn btn-danger" onclick="resetCustomerPoints()">
                                    <i class="fas fa-undo"></i> إعادة تعيين
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Forms section -->
                    <div id="loyaltyFormsSection" class="d-none">
                        <!-- Redeem to balance form -->
                        <div id="redeemToBalanceForm" class="loyalty-form d-none">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">تحويل النقاط إلى رصيد</h6>
                                </div>
                                <div class="card-body">
                                    <form id="redeemForm">
                                        <input type="hidden" id="redeemCustomerId">
                                        <div class="mb-3">
                                            <label class="form-label">عدد النقاط المراد تحويلها</label>
                                            <input type="number" class="form-control" id="redeemPoints" min="1">
                                            <div class="form-text">المبلغ المكافئ: <span id="equivalentAmount">0</span> جنيه</div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success">تحويل</button>
                                            <button type="button" class="btn btn-secondary" onclick="hideLoyaltyForms()">إلغاء</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Adjust points form -->
                        <div id="adjustPointsForm" class="loyalty-form d-none">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">تعديل النقاط يدوياً</h6>
                                </div>
                                <div class="card-body">
                                    <form id="adjustForm">
                                        <input type="hidden" id="adjustCustomerId">
                                        <div class="mb-3">
                                            <label class="form-label">عدد النقاط</label>
                                            <input type="number" class="form-control" id="adjustPoints" placeholder="موجب للإضافة، سالب للخصم">
                                            <div class="form-text">أدخل رقماً موجباً للإضافة أو سالباً للخصم</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">السبب</label>
                                            <textarea class="form-control" id="adjustReason" rows="2" required></textarea>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-warning">تعديل</button>
                                            <button type="button" class="btn btn-secondary" onclick="hideLoyaltyForms()">إلغاء</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent transactions -->
                    <div class="mt-4">
                        <h6>آخر المعاملات</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>النوع</th>
                                        <th>النقاط</th>
                                        <th>الوصف</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody id="loyaltyTransactionsList">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="invoiceDetailsModalLabel">تفاصيل الفاتورة <span id="invoice-number"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="invoice-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل بيانات الفاتورة...</p>
                </div>
                <div id="invoice-content" style="display: none;">
                    <!-- Invoice Header -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold">معلومات الفاتورة</h6>
                            <p><strong>رقم الفاتورة:</strong> <span id="modal-invoice-number"></span></p>
                            <p><strong>التاريخ:</strong> <span id="modal-invoice-date"></span></p>
                            <p><strong>نوع الفاتورة:</strong> <span id="modal-invoice-type"></span></p>
                            <p><strong>نوع الطلب:</strong> <span id="modal-invoice-order-type"></span></p>
                            <p><strong>الحالة:</strong> <span id="modal-invoice-status"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">معلومات العميل</h6>
                            <p><strong>الاسم:</strong> <span id="modal-customer-name"></span></p>
                            <p><strong>الهاتف:</strong> <span id="modal-customer-phone"></span></p>
                            <p><strong>العنوان:</strong> <span id="modal-customer-address"></span></p>
                        </div>
                    </div>
                    <!-- Invoice Items -->
                    <h6 class="fw-bold mb-3">منتجات الفاتورة</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>المنتج</th>
                                    <th>الوحدة</th>
                                    <th>الكمية</th>
                                    <th>السعر</th>
                                    <th>الخصم</th>
                                    <th>الإجمالي</th>
                                    <th>الربح</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items">
                                <!-- Items will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Invoice Summary -->
                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th>المجموع قبل الخصم</th>
                                        <td id="modal-subtotal"></td>
                                    </tr>
                                    <tr>
                                        <th>الخصم</th>
                                        <td id="modal-discount"></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <th>الإجمالي</th>
                                        <td id="modal-total"></td>
                                    </tr>
                                    <tr>
                                        <th>المدفوع</th>
                                        <td id="modal-paid"></td>
                                    </tr>
                                    <tr>
                                        <th>المتبقي</th>
                                        <td id="modal-remaining"></td>
                                    </tr>
                                    <tr class="table-success">
                                        <th>إجمالي الربح</th>
                                        <td id="modal-profit"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <a href="#" class="btn btn-primary" id="print-invoice-btn" target="_blank">
                    <i class="fas fa-print me-1"></i> طباعة الفاتورة
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Helper function for formatting numbers
function number_format(number, decimals = 0) {
    return parseFloat(number).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Add the abs function
function abs(number) {
    return Math.abs(number);
}

// Define showAlert globally if not already defined
if (typeof window.showAlert !== 'function') {
    window.showAlert = function(message, type) {
        if (typeof toastr !== 'undefined') {
            if (type === 'success') toastr.success(message);
            else if (type === 'error') toastr.error(message);
            else if (type === 'info') toastr.info(message);
            else toastr.warning(message);
        } else {
            alert(message);
        }
    };
}

$(document).ready(function() {
    // Add CSRF token to all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Manual modal close handlers
    $('.modal .btn-close, .modal .btn-secondary[data-bs-dismiss="modal"]').on('click', function() {
        var modalId = $(this).closest('.modal').attr('id');
        $('#' + modalId).modal('hide');
    });
    
    // تهيئة النموذج
    initializeForm();
    
    // البحث المباشر
    let searchTimeout;
    $('#searchInput').on('input', function() {
        const searchValue = $(this).val();
        const balanceFilter = $('input[name="balance_filter"]').val() || '';
        
        clearTimeout(searchTimeout);
        
        // Show loading indicator
        if (searchValue.length > 1) {
            $('#tableLoading').removeClass('d-none');
            $('#customerTableContainer').addClass('d-none');
        }
        
        searchTimeout = setTimeout(() => {
            // If search is empty and there's no filter, just reload the page
            if (searchValue.length === 0 && balanceFilter === '') {
                window.location.href = "{{ route('customers.index') }}";
                return;
            }
            
            // Perform AJAX search
            performSearch(searchValue, balanceFilter);
        }, 500);
    });
    
    // Intercept form submission to use AJAX instead
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        const searchValue = $('#searchInput').val();
        const balanceFilter = $('input[name="balance_filter"]').val() || '';
        
        performSearch(searchValue, balanceFilter);
    });

    // Add this function right after the document ready handler
    // For debugging AJAX issues
    $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
        console.error("AJAX Error Details:");
        console.error("Status: " + jqXHR.status);
        console.error("Status Text: " + jqXHR.statusText);
        console.error("Response Text: " + jqXHR.responseText);
        console.error("Error: " + thrownError);
        console.error("URL: " + settings.url);
    });

    // Add payment save handler
    $('#savePayment').on('click', function() {
        var customerId = $('#payment_customer_id').val();
        var amount = parseFloat($('#payment_amount').val());
        var notes = $('#payment_notes').val();
        var paymentType = $('#payment_type').val();
        var creditBalance = parseFloat($('#customer_credit_balance').val());
        var paymentMethod = $('#payment_method').val();
        
        // Validate amount - allow any non-zero number
        if (isNaN(amount) || amount === 0) {
            $('#payment_amount').addClass('is-invalid');
            $('#amount_feedback').text('يرجى إدخال قيمة غير صفرية (موجبة أو سالبة)');
            return;
        }
        
        // Reset validation
        $('#payment_amount').removeClass('is-invalid');
        
        // Show loading state
        var $saveBtn = $('#savePayment');
        var originalText = $saveBtn.html();
        $saveBtn.html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');
        $saveBtn.prop('disabled', true);
        
        // Prepare data
        var requestData = {
            customer_id: customerId,
            amount: amount,
            notes: notes,
            payment_method: paymentMethod,
            override_balance_check: 1,  // Always allow exceeding the balance
            _token: '{{ csrf_token() }}'
        };
        
        // Add flag for balance override - ensure it's sent as a boolean
        if (amount > creditBalance) {
            requestData.override_balance_check = 1; // Send as 1 instead of true
        }
        
        $.ajax({
            url: '/api/customer-payments',
            method: 'POST',
            data: requestData,
            success: function(response) {
                console.log('Payment success:', response);
                
                // Close modal - multiple methods for redundancy
                $('#addPaymentModal').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
                
                // Show success message
                toastr.success('تم تسجيل الدفعة بنجاح');
                
                // Reload page after brief delay
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            },
            error: function(xhr, status, error) {
                console.error('Payment error:', xhr.responseText);
                
                // Reset button state
                $saveBtn.html(originalText);
                $saveBtn.prop('disabled', false);
                
                // Show error message
                var errorMsg = 'حدث خطأ أثناء حفظ الدفعة';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
            }
        });
    });

    // Add explicit cancel button handler
    $('#addPaymentModal .btn-secondary').on('click', function() {
        $('#addPaymentModal').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });
});

function initializeForm() {
    // Handle unlimited credit toggle for new customer modal
    $('#customer-unlimited-credit').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('#customer-has-unlimited-credit').val(isChecked ? '1' : '0');
        
        if (isChecked) {
            // Store the current value before disabling
            $('#customer-credit-limit').data('previous-value', $('#customer-credit-limit').val());
            // Just disable the input with a default value
            $('#customer-credit-limit').val('0').prop('disabled', true);
        } else {
            // Restore the previous value if available, otherwise set to 0
            const previousValue = $('#customer-credit-limit').data('previous-value') || '0';
            $('#customer-credit-limit').val(previousValue).prop('disabled', false);
        }
    });

    // تقديم نموذج إضافة عميل
    $('#addCustomerForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#addCustomerModal').modal('hide');
                toastr.success('تم إضافة العميل بنجاح');
                window.location.reload();
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error('حدث خطأ أثناء إضافة العميل');
                }
            }
        });
    });

    // تقديم نموذج إضافة دفعة
    $('#addPaymentForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#addPaymentModal').modal('hide');
                toastr.success('تم إضافة الدفعة بنجاح');
                window.location.reload();
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error('حدث خطأ أثناء إضافة الدفعة');
                }
            }
        });
    });
}

function deleteCustomer(id) {
    if (confirm('هل أنت متأكد من حذف هذا العميل؟')) {
        console.log(`Attempting to delete customer with ID: ${id}`);
        
        // Get the token directly from meta tag
        const token = $('meta[name="csrf-token"]').attr('content');
        console.log(`CSRF Token: ${token ? 'Found' : 'Not found'}`);
        
        // Try using form submission first (more reliable)
        try {
            const form = $('#deleteCustomerForm');
            form.attr('action', `/customers/${id}`);
            form.submit();
            return;
        } catch (formError) {
            console.error('Form submission failed:', formError);
            // Fall back to AJAX if form submission fails
        }
        
        // Fallback to AJAX
        $.ajax({
            url: `/customers/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token
            },
            beforeSend: function(xhr) {
                console.log('Starting delete request with headers:', xhr.getAllResponseHeaders());
            },
            success: function(response) {
                console.log('Delete successful:', response);
                toastr.success('تم حذف العميل بنجاح');
                window.location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Delete failed:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                if (xhr.status === 419) {
                    toastr.error('خطأ في التحقق من الأمان. يرجى تحديث الصفحة والمحاولة مرة أخرى.');
                } else if (xhr.status === 422) {
                    toastr.error(xhr.responseJSON.message || 'لا يمكن حذف العميل');
                } else if (xhr.status === 404) {
                    toastr.error('العميل غير موجود');
                } else {
                    toastr.error(`حدث خطأ أثناء حذف العميل (${xhr.status}: ${error})`);
                }
            }
        });
    }
}

function addPayment(customerId, type) {
    // Reset form
    $('#addPaymentForm')[0].reset();
    $('#payment_amount').removeClass('is-invalid');
    
    // Set basic form values
    $('#payment_customer_id').val(customerId);
    $('#payment_type').val(type);
    
    // Get customer info to display the balance
    $.ajax({
        url: `/customers/${customerId}/info`,
        method: 'GET',
        success: function(response) {
            const creditBalance = Math.abs(parseFloat(response.credit_balance) || 0);
            
            // Store and display the balance
            $('#customer_credit_balance').val(creditBalance);
            $('#current_balance_display').text(number_format(creditBalance, 2));
            
            // Show the modal once data is loaded
    $('#addPaymentModal').modal('show');
        },
        error: function() {
            toastr.error('حدث خطأ أثناء تحميل بيانات العميل');
        }
    });
}

function exportCustomers() {
    const format = 'excel'; // يمكن تغييرها إلى 'pdf'
    const queryString = $('#searchForm').serialize();
    window.location.href = `/customers/export-report?format=${format}&${queryString}`;
}

// إعادة تعيين النموذج عند إغلاق النافذة المنبثقة
$('#addCustomerModal, #addPaymentModal').on('hidden.bs.modal', function() {
    $(this).find('form')[0].reset();
    
    // Reset unlimited credit toggle state in add customer modal
    if ($(this).attr('id') === 'addCustomerModal') {
        $('#customer-credit-limit').prop('disabled', false).val('0');
        $('#customer-has-unlimited-credit').val('0');
    }
});

function viewInvoices(customerId) {
    $('#invoicesLoading').removeClass('d-none');
    $('#invoicesContent').addClass('d-none');
    $('#noInvoicesMessage').addClass('d-none');
    $('#customerInvoicesModal').modal('show');
    
    // جلب الفواتير من الخادم
    $.ajax({
        url: `/customers/${customerId}/invoices`,
        method: 'GET',
        success: function(response) {
            console.log("Invoices API response:", response);
            const invoices = response.invoices ? response.invoices.data || response.invoices : [];
            console.log("Processed invoices:", invoices);
            
            $('#invoicesLoading').addClass('d-none');
            
            if (invoices.length === 0) {
                $('#noInvoicesMessage').removeClass('d-none');
                return;
            }
            
            $('#invoicesContent').removeClass('d-none');
            const tableBody = $('#invoicesTableBody');
            tableBody.empty();
            
            invoices.forEach(invoice => {
                console.log("Processing invoice:", invoice);
                
                // Map payment status to Arabic
                let paymentStatusText = 'غير مدفوع';
                let statusClass = 'bg-warning';
                
                if (invoice.payment_status === 'paid') {
                    paymentStatusText = 'مدفوع';
                    statusClass = 'bg-success';
                } else if (invoice.payment_status === 'partially_paid') {
                    paymentStatusText = 'مدفوع جزئياً';
                    statusClass = 'bg-info';
                }
                
                tableBody.append(`
                    <tr>
                        <td>${invoice.invoice_number || '-'}</td>
                        <td>${new Date(invoice.created_at).toLocaleDateString('ar')}</td>
                        <td>${parseFloat(invoice.total || 0).toFixed(2)}</td>
                        <td>
                            <span class="badge ${statusClass}">
                                ${paymentStatusText}
                            </span>
                        </td>
                        <td>
                            <a href="#" class="btn btn-sm btn-info view-invoice" data-id="${invoice.id}">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/invoices/${invoice.id}/print" class="btn btn-sm btn-secondary" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                `);
            });
        },
        error: function(xhr, status, error) {
            console.error("Error loading invoices:", xhr.responseText);
            $('#invoicesLoading').addClass('d-none');
            $('#invoicesContent').removeClass('d-none');
            $('#invoicesTableBody').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle"></i> حدث خطأ أثناء تحميل الفواتير
                    </td>
                </tr>
            `);
        }
    });
}

function viewCustomerInfo(customerId) {
    $('#customerInfoLoading').removeClass('d-none');
    $('#customerInfoContent').addClass('d-none');
    $('#customerInfoModal').modal('show');
    
    $.ajax({
        url: `/customers/${customerId}/info`,
        method: 'GET',
        success: function(response) {
            $('#customerInfoLoading').addClass('d-none');
            $('#customerInfoContent').removeClass('d-none');
            $('#customerName').text(response.name);
            $('#customerPhone').text(response.phone);
            $('#customerAddress').text(response.address);
            $('#customerBalance').text(number_format(response.credit_balance, 2));
            
            // Setup credit limit display
            const creditLimit = parseFloat(response.credit_limit) || 0;
            const creditBalance = parseFloat(response.credit_balance) || 0;
            
            // Check for unlimited credit
            if (response.is_unlimited_credit) {
                $('#customerCreditLimit').html('<span class="badge bg-primary">غير محدود</span>');
                $('#customerCreditLimitUsage').addClass('d-none');
            } else {
                $('#customerCreditLimit').text(number_format(creditLimit, 2));
                
                // Calculate credit usage percentage if applicable
                if (creditLimit > 0 && creditBalance < 0) {
                    const percentage = Math.min(100, (abs(creditBalance) / creditLimit) * 100);
                    const statusClass = percentage >= 90 ? 'text-danger' : (percentage >= 70 ? 'text-warning' : 'text-success');
                    $('#customerCreditLimitUsage').removeClass('d-none')
                        .find('.progress-bar')
                        .css('width', percentage + '%')
                        .attr('aria-valuenow', percentage)
                        .removeClass('bg-success bg-warning bg-danger')
                        .addClass(statusClass.replace('text-', 'bg-'));
                    
                    $('#creditUsagePercentage').text(Math.round(percentage) + '%')
                        .removeClass('text-success text-warning text-danger')
                        .addClass(statusClass);
                } else {
                    $('#customerCreditLimitUsage').addClass('d-none');
                }
            }
            
            $('#customerInvoicesCount').text(response.invoices_count);
            $('#customerTotalSales').text(number_format(response.total_sales, 2));
            $('#customerTotalPayments').text(number_format(response.total_payments, 2));
            $('#customerCreatedAt').text(new Date(response.created_at).toLocaleDateString('ar'));
            $('#customerNotes').text(response.notes || '-');
            $('#customerEditLink').attr('href', `/customers/${customerId}/edit`);
        },
        error: function() {
            $('#customerInfoLoading').addClass('d-none');
            $('#customerInfoContent').removeClass('d-none');
            $('#customerInfoContent').html(`
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                    <p>حدث خطأ أثناء تحميل بيانات العميل</p>
                </div>
            `);
        }
    });
}

function performSearch(search, balanceFilter) {
    $('#tableLoading').removeClass('d-none');
    $('#customerTableContainer').addClass('d-none');
    
    try {
        // Construct URL with parameters
        let url = "{{ route('customers.index') }}?";
        let params = [];
        
        if (search) {
            params.push(`search=${encodeURIComponent(search)}`);
        }
        
        if (balanceFilter) {
            params.push(`balance_filter=${encodeURIComponent(balanceFilter)}`);
        }
        
        if (params.length > 0) {
            url += params.join('&');
        }
        
        // Add wantsJson parameter to get JSON response
        url += (params.length > 0 ? '&' : '') + 'wantsJson=1';
        
        console.log("Requesting URL:", url);
        
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            cache: false,
            success: function(response) {
                console.log("Full response:", response);
                
                if (response.success) {
                    try {
                        // Check if we have data in the expected format
                        const customersData = response.customers.data || [];
                        updateTableWithResults(customersData, response.pagination || response.customers);
                    } catch (err) {
                        console.error("Error processing response:", err);
                        showSearchError('خطأ في معالجة البيانات: ' + err.message);
                    }
                } else {
                    console.error('Server returned error:', response);
                    showSearchError('حدث خطأ أثناء البحث: ' + (response.message || 'خطأ غير معروف'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response:', xhr.responseText);
                showSearchError('حدث خطأ أثناء البحث: ' + error);
            },
            complete: function() {
                $('#tableLoading').addClass('d-none');
                $('#customerTableContainer').removeClass('d-none');
            }
        });
    } catch (e) {
        console.error("Error in performSearch:", e);
        $('#tableLoading').addClass('d-none');
        $('#customerTableContainer').removeClass('d-none');
        showSearchError('حدث خطأ في النظام: ' + e.message);
    }
}

function updateTableWithResults(customers, paginationData) {
    const tableBody = $('#customerTableBody');
    tableBody.empty();
    
    console.log("Customers data:", customers);
    console.log("Pagination:", paginationData);
    
    try {
        // Check if we got the data in the expected format
        if (!customers || customers.length === 0) {
            tableBody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                        لا يوجد عملاء مطابقين لمعايير البحث
                    </td>
                </tr>
            `);
            
            // Clear pagination
            $('#paginationContainer').html('');
            return;
        }
        
        // Filter out the cash customer (ID: 1) from display
        customers = customers.filter(customer => customer.id !== 1);
        
        // If after filtering we have no customers, show empty message
        if (customers.length === 0) {
            tableBody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                        لا يوجد عملاء مطابقين لمعايير البحث
                    </td>
                </tr>
            `);
            
            // Clear pagination
            $('#paginationContainer').html('');
            return;
        }
        
        // Add each customer to the table
        customers.forEach(customer => {
            // Make sure we're working with numbers
            const creditBalance = parseFloat(customer.credit_balance) || 0;
            const creditLimit = parseFloat(customer.credit_limit) || 0;
            
            // Calculate credit usage percentage if applicable
            let creditLimitHtml = '';
            if (customer.is_unlimited_credit) {
                creditLimitHtml = '<span class="badge bg-primary">غير محدود</span>';
            } else {
                creditLimitHtml = number_format(creditLimit, 2);
                
                if (creditLimit > 0 && creditBalance < 0) {
                    const percentage = Math.min(100, (abs(creditBalance) / creditLimit) * 100);
                    const badgeClass = percentage >= 90 ? 'bg-danger' : (percentage >= 70 ? 'bg-warning' : 'bg-success');
                    
                    creditLimitHtml += `
                        <div class="progress mt-1" style="height: 5px;">
                            <div class="progress-bar ${badgeClass}" role="progressbar" 
                                 style="width: ${percentage}%" 
                                 aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    `;
                }
            }
            
            const row = `
                <tr>
                    <td>${customer.id}</td>
                    <td>
                        <div class="fw-bold">${customer.name || ''}</div>
                        ${customer.notes ? `<small class="text-muted">${limitText(customer.notes, 50)}</small>` : ''}
                    </td>
                    <td>
                        <a href="tel:${customer.phone || ''}" class="text-decoration-none">
                            ${customer.phone || ''}
                        </a>
                    </td>
                    <td class="${creditBalance < 0 ? 'text-danger' : 'text-success'} fw-bold">
                        ${number_format(creditBalance, 2)}
                        ${creditBalance < 0 ? 
                            `<small class="d-block text-muted">مديونية عليه</small>` : 
                            (creditBalance > 0 ? 
                                `<small class="d-block text-muted">مديونية له</small>` : 
                                ''
                            )
                        }
                    </td>
                    <td>
                        ${creditLimitHtml}
                    </td>
                    <td class="text-center">
                        <div class="fw-bold text-primary">${number_format(customer.total_loyalty_points || 0)}</div>
                        ${(customer.total_loyalty_points || 0) >= 50 ? '<small class="text-success">قابل للاستبدال</small>' : 
                          ((customer.total_loyalty_points || 0) > 0 ? '<small class="text-warning">غير كافي</small>' : 
                          '<small class="text-muted">لا توجد نقاط</small>')}
                    </td>
                    <td>
                        <span class="badge ${customer.is_active ? 'bg-success' : 'bg-danger'}">
                            ${customer.is_active ? 'نشط' : 'غير نشط'}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-info" onclick="viewCustomerInfo(${customer.id})" title="عرض">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-primary" onclick="viewInvoices(${customer.id})" title="عرض الفواتير">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                            <a href="/customers/${customer.id}/edit" class="btn btn-warning" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-success" onclick="addPayment(${customer.id}, 'balance')" title="إضافة رصيد">
                                <i class="fas fa-coins"></i>
                            </button>
                            <button type="button" class="btn btn-info" onclick="manageLoyaltyPoints(${customer.id})" title="إدارة نقاط الولاء">
                                <i class="fas fa-star"></i>
                            </button>
                            ${creditBalance < 0 ? `
                            <button type="button" class="btn btn-success" onclick="addPayment(${customer.id}, 'debt')" title="إضافة دفعة">
                                <i class="fas fa-dollar-sign"></i>
                            </button>
                            ` : ''}
                            <button type="button" class="btn btn-danger" onclick="deleteCustomer(${customer.id})" title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tableBody.append(row);
        });
        
        // We're not handling pagination in the AJAX response for simplicity
        // For a complete solution, we would need to update the pagination links as well
    } catch (error) {
        console.error("Error in updateTableWithResults:", error);
        showSearchError('خطأ في عرض النتائج: ' + error.message);
    }
}

function showSearchError(message) {
    const tableBody = $('#customerTableBody');
    tableBody.html(`
        <tr>
            <td colspan="7" class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-circle fa-2x mb-3 d-block"></i>
                ${message}
            </td>
        </tr>
    `);
    
    // Clear pagination
    $('#paginationContainer').html('');
}

function limitText(text, length) {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
}

function applyFilter(filterValue) {
    // Update the hidden input or create one if it doesn't exist
    if ($('input[name="balance_filter"]').length) {
        $('input[name="balance_filter"]').val(filterValue);
    } else {
        $('#searchForm').append(`<input type="hidden" name="balance_filter" value="${filterValue}">`);
    }
    
    // Update UI to show active filter
    $('.btn-primary').removeClass('btn-primary').addClass('btn-outline-primary');
    if (filterValue === '') {
        $('button[onclick="applyFilter(\'\')"]').removeClass('btn-outline-primary').addClass('btn-primary');
    } else {
        $(`button[onclick="applyFilter('${filterValue}')"]`).removeClass('btn-outline-primary').addClass('btn-primary');
    }
    
    // Get current search value
    const searchValue = $('#searchInput').val();
    
    // Perform search with new filter
    performSearch(searchValue, filterValue);
}

// Loyalty Points Management Functions
let currentCustomerIdForLoyalty = null;
let loyaltySettings = null;

function manageLoyaltyPoints(customerId) {
    currentCustomerIdForLoyalty = customerId;
    
    // Show modal and loading state
    $('#loyaltyPointsModal').modal('show');
    $('#loyaltyPointsLoading').removeClass('d-none');
    $('#loyaltyPointsContent').addClass('d-none');
    
    // Load customer loyalty data
    loadCustomerLoyaltyData(customerId);
}

function loadCustomerLoyaltyData(customerId) {
    // Load customer summary
    $.get(`/loyalty/api/customers/${customerId}/summary`)
        .done(function(response) {
            if (response.success) {
                displayLoyaltyData(response.data);
                loyaltySettings = response.data;
            } else {
                showAlert('خطأ في تحميل بيانات نقاط الولاء', 'error');
            }
        })
        .fail(function(xhr) {
            console.log('Failed to load loyalty settings:', xhr);
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء تحميل البيانات';
            showAlert(message, 'error');
        });
    
    // Load recent transactions
    $.get(`/loyalty/api/customers/${customerId}/history?limit=10`)
        .done(function(response) {
            if (response.success) {
                displayLoyaltyTransactions(response.data);
            }
        })
        .fail(function(xhr) {
            console.error('Error loading loyalty history:', xhr);
        })
        .always(function() {
            $('#loyaltyPointsLoading').addClass('d-none');
            $('#loyaltyPointsContent').removeClass('d-none');
        });
}

function displayLoyaltyData(data) {
    $('#currentLoyaltyPoints').text(number_format(data.total_points));
    $('#pointsValueInCurrency').text(number_format(data.points_value_in_currency, 2));
    
    // Update redeem points input max value
    $('#redeemPoints').attr('max', data.total_points);
}

function displayLoyaltyTransactions(transactions) {
    const tbody = $('#loyaltyTransactionsList');
    tbody.empty();
    
    if (transactions.length === 0) {
        tbody.html('<tr><td colspan="4" class="text-center text-muted">لا توجد معاملات</td></tr>');
        return;
    }
    
    transactions.forEach(function(transaction) {
        const row = `
            <tr>
                <td>
                    <span class="badge ${getTransactionTypeBadgeClass(transaction.type)}">
                        ${transaction.type_label}
                    </span>
                </td>
                <td class="${transaction.points >= 0 ? 'text-success' : 'text-danger'}">
                    ${transaction.formatted_points}
                </td>
                <td>${transaction.description || '-'}</td>
                <td>
                    <small>${transaction.created_at_human}</small>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function getTransactionTypeBadgeClass(type) {
    switch(type) {
        case 'earned':
        case 'manual_add':
            return 'bg-success';
        case 'redeemed':
        case 'manual_subtract':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function showRedeemToBalanceForm() {
    hideLoyaltyForms();
    $('#loyaltyFormsSection').removeClass('d-none');
    $('#redeemToBalanceForm').removeClass('d-none');
    $('#redeemCustomerId').val(currentCustomerIdForLoyalty);
    $('#redeemPoints').focus();
}

function showAdjustPointsForm() {
    hideLoyaltyForms();
    $('#loyaltyFormsSection').removeClass('d-none');
    $('#adjustPointsForm').removeClass('d-none');
    $('#adjustCustomerId').val(currentCustomerIdForLoyalty);
    $('#adjustPoints').focus();
}

function hideLoyaltyForms() {
    $('#loyaltyFormsSection').addClass('d-none');
    $('.loyalty-form').addClass('d-none');
    
    // Reset forms
    $('#redeemForm')[0].reset();
    $('#adjustForm')[0].reset();
}

// Calculate equivalent amount while typing
$(document).on('input', '#redeemPoints', function() {
    const points = parseInt($(this).val()) || 0;
    const rate = loyaltySettings?.points_to_currency_rate || 10;
    const amount = points / rate;
    $('#equivalentAmount').text(number_format(amount, 2));
});

// Handle redeem to balance form submission
$(document).on('submit', '#redeemForm', function(e) {
    e.preventDefault();
    
    const formData = {
        customer_id: $('#redeemCustomerId').val(),
        points: parseInt($('#redeemPoints').val()) || 0
    };
    
    if (formData.points <= 0) {
        showAlert('يجب إدخال عدد نقاط صحيح', 'error');
        return;
    }
    
    $.post('/loyalty/redeem-to-balance', formData)
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                hideLoyaltyForms();
                loadCustomerLoyaltyData(currentCustomerIdForLoyalty);
                location.reload(); // Refresh page to update customer balance
            } else {
                showAlert(response.message, 'error');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء تحويل النقاط';
            showAlert(message, 'error');
        });
});

// Handle adjust points form submission
$(document).on('submit', '#adjustForm', function(e) {
    e.preventDefault();
    
    const formData = {
        customer_id: $('#adjustCustomerId').val(),
        points: parseInt($('#adjustPoints').val()) || 0,
        reason: $('#adjustReason').val().trim()
    };
    
    if (formData.points === 0) {
        showAlert('يجب إدخال عدد نقاط صحيح', 'error');
        return;
    }
    
    if (!formData.reason) {
        showAlert('يجب إدخال سبب التعديل', 'error');
        return;
    }
    
    $.post('/loyalty/adjust-points', formData)
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                hideLoyaltyForms();
                loadCustomerLoyaltyData(currentCustomerIdForLoyalty);
                location.reload(); // Refresh page to update customer points
            } else {
                showAlert(response.message, 'error');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء تعديل النقاط';
            showAlert(message, 'error');
        });
});

function resetCustomerPoints() {
    if (!currentCustomerIdForLoyalty) return;
    
    const reason = prompt('أدخل سبب إعادة تعيين النقاط:');
    if (!reason || reason.trim() === '') {
        return;
    }
    
    if (confirm('هل أنت متأكد من إعادة تعيين جميع نقاط العميل؟')) {
        $.post('/loyalty/reset-points', {
            customer_id: currentCustomerIdForLoyalty,
            reason: reason.trim()
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadCustomerLoyaltyData(currentCustomerIdForLoyalty);
                location.reload(); // Refresh page to update customer points
            } else {
                showAlert(response.message, 'error');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء إعادة تعيين النقاط';
            showAlert(message, 'error');
        });
    }
}

$(document).on('click', '.view-invoice', function(e) {
    e.preventDefault();
    const invoiceId = $(this).data('id');
    // Reset modal content
    $('#invoice-loading').show();
    $('#invoice-content').hide();
    $('#invoice-items').empty();
    // Show modal
    $('#invoiceDetailsModal').modal('show');
    // Set the print button URL
    $('#print-invoice-btn').attr('href', `/sales/invoices/${invoiceId}/print`);
    // Fetch invoice details
    $.ajax({
        url: `/api/sales/invoices/${invoiceId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const invoice = response.invoice;
                // Set invoice details
                $('#invoice-number, #modal-invoice-number').text(invoice.invoice_number);
                $('#modal-invoice-date').text(new Date(invoice.created_at).toLocaleDateString('en-US', {
                    year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'
                }));
                const invoiceType = invoice.type || invoice.invoice_type || 'cash';
                $('#modal-invoice-type').text(invoiceType === 'cash' ? 'كاش' : 'آجل');
                $('#modal-invoice-order-type').text(invoice.order_type === 'takeaway' ? 'تيك أواي' : 'دليفري');
                let statusText = '';
                if (invoice.status === 'completed') {
                    statusText = '<span class="badge bg-success">مكتملة</span>';
                } else if (invoice.status === 'pending') {
                    statusText = '<span class="badge bg-warning">معلقة</span>';
                } else if (invoice.status === 'canceled') {
                    statusText = '<span class="badge bg-danger">ملغية</span>';
                }
                $('#modal-invoice-status').html(statusText);
                if (invoice.customer) {
                    $('#modal-customer-name').text(invoice.customer.name);
                    $('#modal-customer-phone').text(invoice.customer.phone || 'غير متوفر');
                    $('#modal-customer-address').text(invoice.customer.address || 'غير متوفر');
                }
                if (invoice.items && invoice.items.length > 0) {
                    $.each(invoice.items, function(index, item) {
                        try {
                            const quantity = Number(item.quantity || 0);
                            const unitPrice = Number(item.unit_price || 0);
                            const total = Number(item.total || 0);
                            const profit = Number(item.profit || 0);
                            const discountPercentage = Number(item.discount_percentage || 0);
                            const discountValue = Number(item.discount_value || 0);
                            const discountText = discountPercentage > 0 
                                ? `${discountPercentage}%` 
                                : `${discountValue}`;
                            const row = `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${item.product ? item.product.name : 'غير متوفر'}</td>
                                    <td>${item.product_unit && item.product_unit.unit ? item.product_unit.unit.name : 'غير متوفر'}</td>
                                    <td>${quantity}</td>
                                    <td>${unitPrice.toFixed(2)}</td>
                                    <td>${discountText}</td>
                                    <td>${total.toFixed(2)}</td>
                                    <td>${profit.toFixed(2)}</td>
                                </tr>
                            `;
                            $('#invoice-items').append(row);
                        } catch (err) {
                            $('#invoice-items').append(`
                                <tr>
                                    <td>${index + 1}</td>
                                    <td colspan="7" class="text-danger">خطأ في عرض تفاصيل المنتج</td>
                                </tr>
                            `);
                        }
                    });
                } else {
                    $('#invoice-items').html('<tr><td colspan="8" class="text-center">لا توجد منتجات</td></tr>');
                }
                try {
                    const subtotal = Number(invoice.subtotal || 0);
                    const discountPercentage = Number(invoice.discount_percentage || 0);
                    const discountValue = Number(invoice.discount_value || 0);
                    const total = Number(invoice.total || 0);
                    const paidAmount = Number(invoice.paid_amount || 0);
                    const remainingAmount = invoice.remaining_amount !== undefined ? Number(invoice.remaining_amount) : Number(invoice.remaining || 0);
                    const profit = Number(invoice.profit || 0);
                    $('#modal-subtotal').text(`${subtotal.toFixed(2)}`);
                    let discountText = '';
                    if (discountPercentage > 0) {
                        discountText = `${discountPercentage}% (${discountValue.toFixed(2)})`;
                    } else if (discountValue > 0) {
                        discountText = `${discountValue.toFixed(2)}`;
                    } else {
                        discountText = '0.00';
                    }
                    $('#modal-discount').text(discountText);
                    $('#modal-total').text(`${total.toFixed(2)}`);
                    $('#modal-paid').text(`${paidAmount.toFixed(2)}`);
                    $('#modal-remaining').text(`${remainingAmount.toFixed(2)}`);
                    $('#modal-profit').text(`${profit.toFixed(2)}`);
                } catch (err) {
                    alert('حدث خطأ في عرض ملخص الفاتورة');
                }
                $('#invoice-loading').hide();
                $('#invoice-content').show();
            } else {
                alert('حدث خطأ أثناء تحميل بيانات الفاتورة');
            }
        },
        error: function(xhr, status, error) {
            alert('حدث خطأ في الاتصال بالخادم');
            $('#invoiceDetailsModal').modal('hide');
        }
    });
});
</script>
@endpush 