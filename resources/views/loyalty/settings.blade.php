@extends('layouts.app')

@section('title', 'إعدادات نقاط الولاء')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog"></i> إعدادات نقاط الولاء
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('loyalty.settings') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- تفعيل النظام -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">تفعيل النظام</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           value="1" {{ $settings->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong>تفعيل نظام نقاط الولاء</strong>
                                    </label>
                                </div>
                                <small class="text-muted">عند إلغاء التفعيل، لن يتم احتساب أو استبدال أي نقاط</small>
                            </div>
                        </div>

                        <!-- طريقة احتساب النقاط -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">طريقة احتساب النقاط</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="earning_method" 
                                                   id="per_invoice" value="per_invoice" 
                                                   {{ $settings->earning_method == 'per_invoice' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="per_invoice">
                                                <strong>لكل فاتورة مكتملة</strong>
                                                <p class="text-muted small mb-0">يحصل العميل على عدد ثابت من النقاط لكل فاتورة بغض النظر عن قيمتها</p>
                                            </label>
                                        </div>
                                        <div class="ps-4 mb-3" id="per_invoice_options">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label class="form-label">عدد النقاط لكل فاتورة</label>
                                                    <input type="number" class="form-control" name="points_per_invoice" 
                                                           value="{{ $settings->points_per_invoice }}" min="1" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="earning_method" 
                                                   id="per_amount" value="per_amount" 
                                                   {{ $settings->earning_method == 'per_amount' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="per_amount">
                                                <strong>لكل مبلغ معين</strong>
                                                <p class="text-muted small mb-0">يحصل العميل على نقطة واحدة مقابل كل مبلغ محدد من قيمة الفاتورة</p>
                                            </label>
                                        </div>
                                        <div class="ps-4 mb-3" id="per_amount_options">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label class="form-label">نقطة واحدة لكل</label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" name="points_per_amount" 
                                                               value="{{ $settings->points_per_amount }}" step="0.01" min="0.01" required>
                                                        <span class="input-group-text">جنيه</span>
                                                    </div>
                                                    <small class="text-muted">مثال: 1 = نقطة واحدة لكل 1 جنيه</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="earning_method" 
                                                   id="per_product" value="per_product" 
                                                   {{ $settings->earning_method == 'per_product' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="per_product">
                                                <strong>لكل منتج مُشترى</strong>
                                                <p class="text-muted small mb-0">يحصل العميل على عدد ثابت من النقاط لكل منتج داخل الفاتورة</p>
                                            </label>
                                        </div>
                                        <div class="ps-4 mb-3" id="per_product_options">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label class="form-label">عدد النقاط لكل منتج</label>
                                                    <input type="number" class="form-control" name="points_per_product" 
                                                           value="{{ $settings->points_per_product }}" min="1" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- إعدادات الاستبدال -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">إعدادات الاستبدال</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">معدل تحويل النقاط إلى نقود</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="points_to_currency_rate" 
                                                       value="{{ $settings->points_to_currency_rate }}" min="1" required>
                                                <span class="input-group-text">نقطة = 1 جنيه</span>
                                            </div>
                                            <small class="text-muted">مثال: 10 = كل 10 نقاط تساوي 1 جنيه</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">الحد الأدنى للنقاط قبل الاستبدال</label>
                                            <input type="number" class="form-control" name="min_points_for_redemption" 
                                                   value="{{ $settings->min_points_for_redemption }}" min="1" required>
                                            <small class="text-muted">العميل لا يستطيع استبدال النقاط قبل الوصول لهذا العدد</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">الحد الأقصى للاستبدال في العملية الواحدة</label>
                                            <input type="number" class="form-control" name="max_redemption_per_transaction" 
                                                   value="{{ $settings->max_redemption_per_transaction }}" min="1" placeholder="اتركه فارغاً لعدم وضع حد">
                                            <small class="text-muted">اتركه فارغاً للسماح بالاستبدال دون حدود</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" id="allow_full_discount" 
                                                       name="allow_full_discount" value="1" 
                                                       {{ $settings->allow_full_discount ? 'checked' : '' }}>
                                                <label class="form-check-label" for="allow_full_discount">
                                                    <strong>السماح بالخصم الكامل على الفاتورة</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted">السماح للعميل باستخدام النقاط لتغطية 100% من قيمة الفاتورة</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار الحفظ -->
                        <div class="text-end">
                            <a href="{{ route('home') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ الإعدادات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معاينة الإعدادات والإحصائيات -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">معاينة الإعدادات</h6>
                </div>
                <div class="card-body">
                    <div id="settingsPreview">
                        <div class="mb-3">
                            <strong>الطريقة الحالية:</strong>
                            <span id="currentMethod">{{ $settings->earning_method_label }}</span>
                        </div>
                        <div class="mb-3">
                            <strong>مثال:</strong>
                            <div id="exampleCalculation" class="alert alert-info">
                                سيتم عرض مثال حسب الإعدادات المختارة
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($statistics['is_active'])
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">إحصائيات النظام</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="mb-3">
                                <h4 class="text-primary">{{ number_format($statistics['total_customers_with_points']) }}</h4>
                                <small class="text-muted">عملاء لديهم نقاط</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <h4 class="text-success">{{ number_format($statistics['total_points_awarded']) }}</h4>
                                <small class="text-muted">إجمالي النقاط الممنوحة</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <h4 class="text-warning">{{ number_format($statistics['total_points_redeemed']) }}</h4>
                                <small class="text-muted">النقاط المستبدلة</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <h4 class="text-info">{{ number_format($statistics['total_amount_redeemed'], 2) }}</h4>
                                <small class="text-muted">قيمة المستبدل (جنيه)</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6>أكثر العملاء نقاطاً</h6>
                        @if(count($statistics['top_customers_by_points']) > 0)
                            @foreach(array_slice($statistics['top_customers_by_points'], 0, 5) as $customer)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>{{ $customer['name'] }}</span>
                                <span class="badge bg-primary">{{ number_format($customer['total_loyalty_points']) }}</span>
                            </div>
                            @endforeach
                        @else
                            <p class="text-muted small">لا يوجد عملاء لديهم نقاط بعد</p>
                        @endif
                    </div>

                    <div class="text-center">
                        <a href="{{ route('loyalty.customers') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chart-bar"></i> عرض التفاصيل
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // إظهار/إخفاء خيارات طريقة الاحتساب
    function toggleEarningMethodOptions() {
        const selectedMethod = $('input[name="earning_method"]:checked').val();
        
        $('#per_invoice_options, #per_amount_options, #per_product_options').hide();
        
        if (selectedMethod) {
            $(`#${selectedMethod}_options`).show();
        }
        
        updateSettingsPreview();
    }
    
    // تحديث معاينة الإعدادات
    function updateSettingsPreview() {
        const method = $('input[name="earning_method"]:checked').val();
        let methodText = '';
        let example = '';
        
        switch(method) {
            case 'per_invoice':
                const pointsPerInvoice = $('#per_invoice_options input').val() || 10;
                methodText = 'لكل فاتورة';
                example = `فاتورة بقيمة 100 جنيه = ${pointsPerInvoice} نقطة`;
                break;
                
            case 'per_amount':
                const pointsPerAmount = $('#per_amount_options input').val() || 1;
                methodText = 'لكل مبلغ';
                example = `فاتورة بقيمة 100 جنيه = ${Math.floor(100 / pointsPerAmount)} نقطة`;
                break;
                
            case 'per_product':
                const pointsPerProduct = $('#per_product_options input').val() || 5;
                methodText = 'لكل منتج';
                example = `فاتورة تحتوي على 3 منتجات = ${3 * pointsPerProduct} نقطة`;
                break;
        }
        
        $('#currentMethod').text(methodText);
        $('#exampleCalculation').html(example);
    }
    
    // ربط الأحداث
    $('input[name="earning_method"]').change(toggleEarningMethodOptions);
    $('#per_invoice_options input, #per_amount_options input, #per_product_options input').on('input', updateSettingsPreview);
    
    // تهيئة الواجهة
    toggleEarningMethodOptions();
});
</script>
@endpush 