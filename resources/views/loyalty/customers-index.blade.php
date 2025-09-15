@extends('layouts.app')

@section('title', 'إدارة نقاط ولاء العملاء')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star"></i> إدارة نقاط ولاء العملاء
                    </h5>
                </div>
                <div class="col-auto">
                    <a href="{{ route('loyalty.settings') }}" class="btn btn-outline-primary">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                    <a href="{{ route('loyalty.transactions') }}" class="btn btn-outline-info">
                        <i class="fas fa-list"></i> سجل المعاملات
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- إحصائيات سريعة -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4>{{ number_format($statistics['total_customers_with_points']) }}</h4>
                            <small>عملاء لديهم نقاط</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h4>{{ number_format($statistics['total_points_awarded']) }}</h4>
                            <small>إجمالي النقاط الممنوحة</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h4>{{ number_format($statistics['total_points_redeemed']) }}</h4>
                            <small>النقاط المستبدلة</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h4>{{ number_format($statistics['total_amount_redeemed'], 2) }}</h4>
                            <small>قيمة المستبدل (جنيه)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- فلاتر البحث -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <form action="{{ route('loyalty.customers') }}" method="GET" id="searchForm">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" 
                                   value="{{ request('search') }}" placeholder="البحث بالاسم أو رقم الهاتف">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <select class="form-select" onchange="applyPointsFilter(this.value)">
                        <option value="">جميع العملاء</option>
                        <option value="with_points" {{ request('points_filter') == 'with_points' ? 'selected' : '' }}>
                            العملاء الذين لديهم نقاط
                        </option>
                        <option value="no_points" {{ request('points_filter') == 'no_points' ? 'selected' : '' }}>
                            العملاء بدون نقاط
                        </option>
                        <option value="redeemable" {{ request('points_filter') == 'redeemable' ? 'selected' : '' }}>
                            نقاط قابلة للاستبدال
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="btn-group w-100">
                        <button type="button" class="btn btn-outline-secondary" onclick="exportLoyaltyData()">
                            <i class="fas fa-download"></i> تصدير
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="refreshData()">
                            <i class="fas fa-sync"></i> تحديث
                        </button>
                    </div>
                </div>
            </div>

            <!-- جدول العملاء -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>رقم الهاتف</th>
                            <th class="text-center">النقاط الحالية</th>
                            <th class="text-center">النقاط المكتسبة</th>
                            <th class="text-center">النقاط المستبدلة</th>
                            <th class="text-center">قيمة النقاط</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $customer->name }}</div>
                                @if($customer->notes)
                                    <small class="text-muted">{{ Str::limit($customer->notes, 30) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($customer->phone)
                                    <a href="tel:{{ $customer->phone }}" class="text-decoration-none">
                                        {{ $customer->phone }}
                                    </a>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="fw-bold text-primary fs-5">
                                    {{ number_format($customer->total_loyalty_points) }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="text-success">
                                    +{{ number_format($customer->getTotalEarnedPoints()) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="text-danger">
                                    -{{ number_format($customer->getTotalRedeemedPoints()) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php
                                    $settings = \App\Models\LoyaltySetting::getSettings();
                                    $value = $settings->pointsToCurrency($customer->total_loyalty_points);
                                @endphp
                                <span class="fw-bold text-success">
                                    {{ number_format($value, 2) }} ج.م
                                </span>
                            </td>
                            <td class="text-center">
                                @if($customer->total_loyalty_points >= $settings->min_points_for_redemption)
                                    <span class="badge bg-success">قابل للاستبدال</span>
                                @elseif($customer->total_loyalty_points > 0)
                                    <span class="badge bg-warning">غير كافي</span>
                                @else
                                    <span class="badge bg-secondary">لا توجد نقاط</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info" 
                                            onclick="viewLoyaltyHistory({{ $customer->id }})" title="عرض السجل">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <button type="button" class="btn btn-success" 
                                            onclick="redeemPoints({{ $customer->id }})" title="استبدال النقاط"
                                            {{ $customer->total_loyalty_points < $settings->min_points_for_redemption ? 'disabled' : '' }}>
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-warning" 
                                            onclick="adjustPoints({{ $customer->id }})" title="تعديل النقاط">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="resetPoints({{ $customer->id }})" title="إعادة تعيين">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-star fa-2x mb-3 d-block"></i>
                                لا يوجد عملاء مطابقين لمعايير البحث
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- الصفحات -->
            <div class="mt-4">
                {{ $customers->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal عرض سجل النقاط -->
<div class="modal fade" id="loyaltyHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">سجل نقاط الولاء</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historyLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
                <div id="historyContent" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>النوع</th>
                                    <th>النقاط</th>
                                    <th>المصدر</th>
                                    <th>الوصف</th>
                                    <th>الرصيد بعدها</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal استبدال النقاط -->
<div class="modal fade" id="redeemPointsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">استبدال النقاط</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="redeemForm">
                    <input type="hidden" id="redeemCustomerId">
                    <div class="mb-3">
                        <label class="form-label">النقاط المتاحة</label>
                        <input type="text" class="form-control" id="availablePoints" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">عدد النقاط المراد استبدالها</label>
                        <input type="number" class="form-control" id="pointsToRedeem" min="1" required>
                        <div class="form-text">المبلغ المكافئ: <span id="equivalentAmount">0</span> جنيه</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" onclick="submitRedemption()">
                    <i class="fas fa-exchange-alt"></i> استبدال
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal تعديل النقاط -->
<div class="modal fade" id="adjustPointsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل النقاط</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adjustForm">
                    <input type="hidden" id="adjustCustomerId">
                    <div class="mb-3">
                        <label class="form-label">النقاط الحالية</label>
                        <input type="text" class="form-control" id="currentPoints" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">عدد النقاط للتعديل</label>
                        <input type="number" class="form-control" id="pointsAdjustment" required>
                        <div class="form-text">أدخل رقماً موجباً للإضافة أو سالباً للخصم</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السبب</label>
                        <textarea class="form-control" id="adjustReason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-warning" onclick="submitAdjustment()">
                    <i class="fas fa-edit"></i> تعديل
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let loyaltySettings = null;

$(document).ready(function() {
    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Load loyalty settings
    $.get('/loyalty/api/statistics')
        .done(function(response) {
            if (response.success) {
                loyaltySettings = response.data;
            }
        })
        .fail(function(xhr) {
            console.error('Failed to load loyalty settings:', xhr);
        });
});

function applyPointsFilter(filter) {
    const url = new URL(window.location);
    if (filter) {
        url.searchParams.set('points_filter', filter);
    } else {
        url.searchParams.delete('points_filter');
    }
    window.location = url.toString();
}

function viewLoyaltyHistory(customerId) {
    $('#loyaltyHistoryModal').modal('show');
    $('#historyLoading').removeClass('d-none');
    $('#historyContent').addClass('d-none');
    
    $.get(`/loyalty/api/customers/${customerId}/history`)
        .done(function(response) {
            if (response.success) {
                displayLoyaltyHistory(response.data);
            } else {
                showAlert('خطأ في تحميل السجل', 'error');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء تحميل السجل';
            showAlert(message, 'error');
        })
        .always(function() {
            $('#historyLoading').addClass('d-none');
            $('#historyContent').removeClass('d-none');
        });
}

function displayLoyaltyHistory(transactions) {
    const tbody = $('#historyTableBody');
    tbody.empty();
    
    if (transactions.length === 0) {
        tbody.html('<tr><td colspan="6" class="text-center text-muted">لا توجد معاملات</td></tr>');
        return;
    }
    
    transactions.forEach(function(transaction) {
        const typeClass = transaction.points >= 0 ? 'text-success' : 'text-danger';
        const typeBadge = getTransactionTypeBadge(transaction.type);
        
        const row = `
            <tr>
                <td><small>${transaction.created_at_human}</small></td>
                <td>${typeBadge}</td>
                <td class="${typeClass}">${transaction.formatted_points}</td>
                <td><small>${transaction.source_type_label}</small></td>
                <td><small>${transaction.description || '-'}</small></td>
                <td><strong>${number_format(transaction.balance_after)}</strong></td>
            </tr>
        `;
        tbody.append(row);
    });
}

function getTransactionTypeBadge(type) {
    const badges = {
        'earned': '<span class="badge bg-success">مكتسبة</span>',
        'redeemed': '<span class="badge bg-danger">مستبدلة</span>',
        'manual_add': '<span class="badge bg-info">إضافة يدوية</span>',
        'manual_subtract': '<span class="badge bg-warning">خصم يدوي</span>'
    };
    return badges[type] || '<span class="badge bg-secondary">غير محدد</span>';
}

function redeemPoints(customerId) {
    // Validate customer ID
    if (!customerId || customerId <= 0) {
        showAlert('معرف العميل غير صحيح', 'error');
        return;
    }
    
    console.log('Redeeming points for customer ID:', customerId);
    
    // Get customer data first
    $.get(`/loyalty/api/customers/${customerId}/summary`)
        .done(function(response) {
            console.log('API Response:', response);
            if (response.success) {
                const data = response.data;
                $('#redeemCustomerId').val(customerId);
                $('#availablePoints').val(number_format(data.total_points));
                $('#pointsToRedeem').attr('max', data.total_points);
                $('#redeemPointsModal').modal('show');
            } else {
                showAlert(response.message || 'خطأ في تحميل بيانات العميل', 'error');
            }
        })
        .fail(function(xhr) {
            console.error('API Error:', xhr);
            console.error('Response text:', xhr.responseText);
            let errorMessage = 'خطأ في تحميل بيانات العميل';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 404) {
                errorMessage = 'العميل غير موجود';
            } else if (xhr.status === 403) {
                errorMessage = 'ليس لديك صلاحية للوصول لهذه البيانات';
            } else if (xhr.status === 500) {
                errorMessage = 'خطأ في الخادم، يرجى المحاولة مرة أخرى';
            } else if (xhr.status === 0) {
                errorMessage = 'فشل في الاتصال بالخادم، تحقق من الاتصال بالإنترنت';
            }
            
            showAlert(errorMessage, 'error');
        });
}

function adjustPoints(customerId) {
    // Get customer data first
    $.get(`/loyalty/api/customers/${customerId}/summary`)
        .done(function(response) {
            console.log('API Response for adjust:', response);
            if (response.success) {
                const data = response.data;
                $('#adjustCustomerId').val(customerId);
                $('#currentPoints').val(number_format(data.total_points));
                $('#adjustPointsModal').modal('show');
            } else {
                showAlert(response.message || 'خطأ في تحميل بيانات العميل', 'error');
            }
        })
        .fail(function(xhr) {
            console.error('API Error for adjust:', xhr);
            let errorMessage = 'خطأ في تحميل بيانات العميل';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 404) {
                errorMessage = 'العميل غير موجود';
            } else if (xhr.status === 403) {
                errorMessage = 'ليس لديك صلاحية للوصول لهذه البيانات';
            } else if (xhr.status === 500) {
                errorMessage = 'خطأ في الخادم، يرجى المحاولة مرة أخرى';
            }
            
            showAlert(errorMessage, 'error');
        });
}

function resetPoints(customerId) {
    const reason = prompt('أدخل سبب إعادة تعيين النقاط:');
    if (!reason || reason.trim() === '') {
        return;
    }
    
    if (confirm('هل أنت متأكد من إعادة تعيين جميع نقاط العميل؟')) {
        $.post('/loyalty/reset-points', {
            customer_id: customerId,
            reason: reason.trim()
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                location.reload();
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

function submitRedemption() {
    const customerId = $('#redeemCustomerId').val();
    const points = parseInt($('#pointsToRedeem').val()) || 0;
    
    if (points <= 0) {
        showAlert('يجب إدخال عدد نقاط صحيح', 'error');
        return;
    }
    
    $.post('/loyalty/redeem-to-balance', {
        customer_id: customerId,
        points: points
    })
    .done(function(response) {
        if (response.success) {
            showAlert(response.message, 'success');
            $('#redeemPointsModal').modal('hide');
            location.reload();
        } else {
            showAlert(response.message, 'error');
        }
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'حدث خطأ أثناء استبدال النقاط';
        showAlert(message, 'error');
    });
}

function submitAdjustment() {
    const customerId = $('#adjustCustomerId').val();
    const points = parseInt($('#pointsAdjustment').val()) || 0;
    const reason = $('#adjustReason').val().trim();
    
    if (points === 0) {
        showAlert('يجب إدخال عدد نقاط صحيح', 'error');
        return;
    }
    
    if (!reason) {
        showAlert('يجب إدخال سبب التعديل', 'error');
        return;
    }
    
    $.post('/loyalty/adjust-points', {
        customer_id: customerId,
        points: points,
        reason: reason
    })
    .done(function(response) {
        if (response.success) {
            showAlert(response.message, 'success');
            $('#adjustPointsModal').modal('hide');
            location.reload();
        } else {
            showAlert(response.message, 'error');
        }
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'حدث خطأ أثناء تعديل النقاط';
        showAlert(message, 'error');
    });
}

// Calculate equivalent amount while typing
$(document).on('input', '#pointsToRedeem', function() {
    const points = parseInt($(this).val()) || 0;
    const rate = loyaltySettings?.points_to_currency_rate || 10;
    const amount = points / rate;
    $('#equivalentAmount').text(number_format(amount, 2));
});

function refreshData() {
    location.reload();
}

function exportLoyaltyData() {
    showAlert('وظيفة التصدير ستكون متاحة قريباً', 'info');
}

function number_format(number, decimals = 0) {
    return parseFloat(number).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function showAlert(message, type = 'info') {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').prepend(alert);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Test function for debugging API connection
function testAPI() {
    console.log('Testing API connection...');
    $.get('/loyalty/api/statistics')
        .done(function(response) {
            console.log('API test successful:', response);
            showAlert('اتصال API يعمل بشكل صحيح', 'success');
        })
        .fail(function(xhr) {
            console.error('API test failed:', xhr);
            showAlert('فشل في الاتصال بـ API', 'error');
        });
}
</script>
@endpush 