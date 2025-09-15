@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="settings-header mb-3">
                <h2 class="fw-bold mb-0"><i class="fas fa-cogs text-primary me-2"></i>إعدادات النظام</h2>
                <p class="text-muted small mb-0">تخصيص إعدادات النظام وفقًا لاحتياجاتك</p>
                </div>

                @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm py-2 mb-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm py-2 mb-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="card-header bg-white p-0">
                    <ul class="nav nav-tabs nav-fill settings-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 active text-dark" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab" aria-controls="inventory" aria-selected="true">
                                <i class="fas fa-box me-2"></i>إعدادات المخزون
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 text-dark" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="false">
                                <i class="fas fa-cog me-2"></i>الإعدادات العامة
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 text-dark" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab" aria-controls="sales" aria-selected="false">
                                <i class="fas fa-shopping-cart me-2"></i>إعدادات المبيعات
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 text-dark" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab" aria-controls="purchases" aria-selected="false">
                                <i class="fas fa-truck-loading me-2"></i>إعدادات المشتريات
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 text-dark" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab" aria-controls="employees" aria-selected="false">
                                <i class="fas fa-users me-2"></i>إعدادات الموظفين
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 text-dark" id="printing-tab" data-bs-toggle="tab" data-bs-target="#printing" type="button" role="tab" aria-controls="printing" aria-selected="false">
                                <i class="fas fa-print me-2"></i>إعدادات الطباعة
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 text-dark" id="barcode-tab" data-bs-toggle="tab" data-bs-target="#barcode" type="button" role="tab" aria-controls="barcode" aria-selected="false">
                                <i class="fas fa-barcode me-2"></i>إعدادات الباركود
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 text-dark" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab" aria-controls="notifications" aria-selected="false">
                                <i class="fas fa-bell me-2"></i>إعدادات الإشعارات
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-0">
                    <div class="tab-content" id="settingsTabsContent">
                        <!-- Inventory Settings Tab -->
                        <div class="tab-pane fade {{ request('tab', 'inventory') === 'inventory' ? 'show active' : '' }} p-3" id="inventory" role="tabpanel" aria-labelledby="inventory-tab">
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="inventory">
                                <h5 class="border-bottom pb-2 mb-3">إعدادات المخزون</h5>
                                
                                <!-- Allow Negative Inventory -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="allow_negative" class="form-label fw-medium m-0">السماح بالمخزون السالب</label>
                                        <span id="negativeInventoryBadge" class="badge {{ $settings->allow_negative ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->allow_negative ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="allow_negative" id="allow_negative" 
                                            {{ $settings->allow_negative ? 'checked' : '' }} onchange="updateBadgeStatus('negativeInventoryBadge', this); handleNegativeInventoryChange()">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، يمكنك بيع المنتجات حتى لو كانت كمية المخزون صفرًا أو أقل
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Subtract Sold Quantity from Inventory -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="subtract_inventory" class="form-label fw-medium m-0">خصم كمية المبيعات من المخزون</label>
                                        <span id="subtractInventoryBadge" class="badge {{ $settings->subtract_inventory ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->subtract_inventory ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="subtract_inventory" id="subtract_inventory" 
                                            {{ $settings->subtract_inventory ? 'checked' : '' }} onchange="updateBadgeStatus('subtractInventoryBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيقوم النظام تلقائيًا بخصم كمية المنتج من المخزون عند بيعه
                                        </span>
                                        <div id="option_dependency_note" class="alert alert-warning small mt-2 py-2" style="display: none;">
                                            هذا الخيار يتطلب تفعيل خيار السماح بالمخزون السالب.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="show_colors_options" class="form-label fw-medium m-0">عرض خيارات الألوان</label>
                                        <span id="showColorsOptionsBadge" class="badge {{ $settings->show_colors_options ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->show_colors_options ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_colors_options" id="show_colors_options" 
                                            {{ $settings->show_colors_options ? 'checked' : '' }} onchange="updateBadgeStatus('showColorsOptionsBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم عرض خيارات الألوان في صفحات المنتجات.
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="show_sizes_options" class="form-label fw-medium m-0">عرض خيارات المقاسات</label>
                                        <span id="showSizesOptionsBadge" class="badge {{ $settings->show_sizes_options ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->show_sizes_options ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_sizes_options" id="show_sizes_options" 
                                            {{ $settings->show_sizes_options ? 'checked' : '' }} onchange="updateBadgeStatus('showSizesOptionsBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم عرض خيارات المقاسات في صفحات المنتجات.
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- General Settings Tab (Placeholder) -->
                        <div class="tab-pane fade p-3" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                                <input type="hidden" name="tab" value="general">
                                <h5 class="border-bottom pb-2 mb-3">الإعدادات العامة</h5>
                                
                                <!-- Store Information -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-store me-2"></i>معلومات المتجر</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <label for="store_name" class="form-label">اسم المتجر</label>
                                        <input type="text" class="form-control" id="store_name" name="store_name" value="{{ $settings->store_name ?? '' }}">
                                        <span class="form-text text-muted small">سيظهر هذا الاسم في تقارير النظام والفواتير</span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="store_phone" class="form-label">رقم الهاتف</label>
                                        <input type="text" class="form-control" id="store_phone" name="store_phone" value="{{ $settings->store_phone ?? '' }}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="store_address" class="form-label">العنوان</label>
                                        <textarea class="form-control" id="store_address" name="store_address" rows="2">{{ $settings->store_address ?? '' }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tax_number" class="form-label">الرقم الضريبي</label>
                                        <input type="text" class="form-control" id="tax_number" name="tax_number" value="{{ $settings->tax_number ?? '' }}">
                                    </div>
                                </div>
                                
                                <!-- Currency Settings -->
                                <h6 class="fw-bold mb-3 mt-4"><i class="fas fa-money-bill me-2"></i>إعدادات العملة</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <label for="currency_symbol" class="form-label">رمز العملة</label>
                                        <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="{{ $settings->currency_symbol ?? 'ج.م' }}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="currency_position" class="form-label">موضع رمز العملة</label>
                                        <select class="form-select" id="currency_position" name="currency_position">
                                            <option value="before" {{ ($settings->currency_position ?? 'after') == 'before' ? 'selected' : '' }}>قبل المبلغ (ج.م 100)</option>
                                            <option value="after" {{ ($settings->currency_position ?? 'after') == 'after' ? 'selected' : '' }}>بعد المبلغ (100 ج.م)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="decimal_places" class="form-label">عدد الأرقام العشرية</label>
                                        <select class="form-select" id="decimal_places" name="decimal_places">
                                            <option value="0" {{ ($settings->decimal_places ?? '2') == '0' ? 'selected' : '' }}>0</option>
                                            <option value="1" {{ ($settings->decimal_places ?? '2') == '1' ? 'selected' : '' }}>1</option>
                                            <option value="2" {{ ($settings->decimal_places ?? '2') == '2' ? 'selected' : '' }}>2</option>
                                            <option value="3" {{ ($settings->decimal_places ?? '2') == '3' ? 'selected' : '' }}>3</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Default Settings -->
                                <h6 class="fw-bold mb-3 mt-4"><i class="fas fa-sliders-h me-2"></i>الإعدادات الافتراضية</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <label for="default_customer" class="form-label">العميل الافتراضي للمبيعات</label>
                                        <select class="form-select" id="default_customer" name="default_customer">
                                            @foreach(\App\Models\Customer::where('is_active', true)->orderBy('name')->get() as $customer)
                                                <option value="{{ $customer->id }}" {{ ($settings->default_customer ?? '1') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="default_supplier" class="form-label">المورد الافتراضي للمشتريات</label>
                                        <select class="form-select" id="default_supplier" name="default_supplier">
                                            @foreach(\App\Models\Supplier::where('is_active', true)->orderBy('name')->get() as $supplier)
                                                <option value="{{ $supplier->id }}" {{ ($settings->default_supplier ?? '1') == $supplier->id ? 'selected' : '' }}>
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                    </button>
                            </div>
                            </form>
                        </div>

                        <!-- Sales Settings Tab (Placeholder) -->
                        <div class="tab-pane fade p-3" id="sales" role="tabpanel" aria-labelledby="sales-tab">
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="sales">
                                <h5 class="border-bottom pb-2 mb-3">إعدادات المبيعات</h5>
                                
                                <!-- Show Profit Info -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="show_profit_in_summary" class="form-label fw-medium m-0">إظهار معلومات الربح في ملخص المبيعات</label>
                                        <span id="showProfitBadge" class="badge {{ $settings->show_profit_in_summary ?? false ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->show_profit_in_summary ?? false ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_profit_in_summary" id="show_profit_in_summary" 
                                            {{ $settings->show_profit_in_summary ?? false ? 'checked' : '' }} onchange="updateBadgeStatus('showProfitBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم عرض معلومات الربح في تبويب الملخص في صفحة المبيعات
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Show Profit in Sales Table -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="show_profit_in_sales_table" class="form-label fw-medium m-0">إظهار عمود الربح في جدول المبيعات</label>
                                        <span id="showProfitTableBadge" class="badge {{ $settings->show_profit_in_sales_table ?? false ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->show_profit_in_sales_table ?? false ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_profit_in_sales_table" id="show_profit_in_sales_table" 
                                            {{ $settings->show_profit_in_sales_table ?? false ? 'checked' : '' }} onchange="updateBadgeStatus('showProfitTableBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم عرض عمود الربح في جدول المنتجات في صفحة المبيعات
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Show Expiry Dates -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="show_expiry_dates" class="form-label fw-medium m-0">إظهار تواريخ الصلاحية في المبيعات</label>
                                        <span id="showExpiryBadge" class="badge {{ $settings->show_expiry_dates ?? false ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->show_expiry_dates ?? false ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_expiry_dates" id="show_expiry_dates" 
                                            {{ $settings->show_expiry_dates ?? false ? 'checked' : '' }} onchange="updateBadgeStatus('showExpiryBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم عرض تواريخ الصلاحية للمنتجات في شاشة المبيعات
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Default Price Type -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="default_price_type" class="form-label fw-medium m-0">نوع السعر الافتراضي</label>
                                    </div>
                                    <div class="mb-3">
                                        <select class="form-select" name="default_price_type" id="default_price_type">
                                            @foreach(\App\Models\PriceType::where('is_active', true)->orderBy('sort_order')->get() as $priceType)
                                                <option value="{{ $priceType->code }}" {{ ($settings->default_price_type ?? '') == $priceType->code ? 'selected' : '' }}>
                                                    {{ $priceType->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="form-text text-muted small">
                                            اختر نوع السعر الافتراضي الذي سيتم استخدامه عند فتح شاشة المبيعات (إذا لم يكن البيع بأسعار مختلفة مفعلاً، أو كقيمة أولية إذا كان مفعلاً)
                                        </span>
                                    </div>
                                </div>

                                <!-- Allow Selling at Different Prices -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="allow_selling_at_different_prices" class="form-label fw-medium m-0">السماح بالبيع بأسعار مختلفة</label>
                                        <span id="allowSellingDifferentPricesBadge" class="badge {{ $settings->allow_selling_at_different_prices ?? true ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->allow_selling_at_different_prices ?? true ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="allow_selling_at_different_prices" id="allow_selling_at_different_prices" 
                                            {{ $settings->allow_selling_at_different_prices ?? true ? 'checked' : '' }} onchange="updateBadgeStatus('allowSellingDifferentPricesBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، ستظهر قائمة منسدلة لاختيار نوع السعر في شاشة المبيعات. إذا كان معطلاً، سيتم استخدام نوع السعر الافتراضي دائماً.
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Allow Editing Price During Sale -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="allow_price_edit_during_sale" class="form-label fw-medium m-0">السماح بتعديل سعر البيع أثناء البيع</label>
                                        <span id="allowPriceEditBadge" class="badge {{ $settings->allow_price_edit_during_sale ?? true ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->allow_price_edit_during_sale ?? true ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="allow_price_edit_during_sale" id="allow_price_edit_during_sale" 
                                            {{ $settings->allow_price_edit_during_sale ?? true ? 'checked' : '' }} onchange="updateBadgeStatus('allowPriceEditBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، يمكن للمستخدم تعديل سعر البيع للمنتج في شاشة المبيعات. إذا كان معطلاً، سيكون حقل السعر غير قابل للتعديل.
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Show Units Modal on Product Barcode -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="show_units_modal_on_product_barcode" class="form-label fw-medium m-0">إظهار مودال الوحدات عند إدخال باركود المنتج</label>
                                        <span id="showUnitsModalBadge" class="badge {{ $settings->show_units_modal_on_product_barcode ?? true ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->show_units_modal_on_product_barcode ?? true ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_units_modal_on_product_barcode" id="show_units_modal_on_product_barcode" 
                                            {{ $settings->show_units_modal_on_product_barcode ?? true ? 'checked' : '' }} onchange="updateBadgeStatus('showUnitsModalBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيفتح مودال اختيار الوحدات عند إدخال باركود المنتج الأساسي. إذا كان معطلاً، سيتم إضافة الوحدة الأساسية مباشرة. ملاحظة: باركود الوحدات المحددة سيتم إضافتها مباشرة في الحالتين.
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Purchases Settings Tab -->
                        <div class="tab-pane fade p-3" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="purchases">
                                <h5 class="border-bottom pb-2 mb-3">إعدادات المشتريات</h5>
                                
                                <!-- Show Profit Info in Purchases -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="show_profit_in_purchases" class="form-label fw-medium m-0">إظهار معلومات الربح في شاشة المشتريات</label>
                                        <span id="showProfitPurchasesBadge" class="badge {{ $settings->show_profit_in_purchases ?? false ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->show_profit_in_purchases ?? false ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_profit_in_purchases" id="show_profit_in_purchases" 
                                            {{ $settings->show_profit_in_purchases ?? false ? 'checked' : '' }} onchange="updateBadgeStatus('showProfitPurchasesBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم عرض معلومات الربح المتوقع في شاشة المشتريات
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Show Expiry Dates in Purchases -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="show_expiry_in_purchases" class="form-label fw-medium m-0">إظهار تواريخ الصلاحية في المشتريات</label>
                                        <span id="showExpiryPurchasesBadge" class="badge {{ $settings->show_expiry_in_purchases ?? false ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->show_expiry_in_purchases ?? false ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="show_expiry_in_purchases" id="show_expiry_in_purchases" 
                                            {{ $settings->show_expiry_in_purchases ?? false ? 'checked' : '' }} onchange="updateBadgeStatus('showExpiryPurchasesBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم إظهار حقول تواريخ الصلاحية عند إضافة منتجات في المشتريات
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Update All Units Cost -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="update_all_units_cost" class="form-label fw-medium m-0">تحديث تكلفة جميع وحدات المنتج</label>
                                        <span id="updateAllUnitsCostBadge" class="badge {{ $settings->update_all_units_cost ?? true ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->update_all_units_cost ?? true ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="update_all_units_cost" id="update_all_units_cost" 
                                            {{ $settings->update_all_units_cost ?? true ? 'checked' : '' }} onchange="updateBadgeStatus('updateAllUnitsCostBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم تحديث تكلفة جميع وحدات المنتج عند شراء أي وحدة منه بناءً على معامل التحويل
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Employees Settings Tab -->
                        <div class="tab-pane fade p-3" id="employees" role="tabpanel" aria-labelledby="employees-tab">
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="employees">
                                <h5 class="border-bottom pb-2 mb-3">إعدادات الموظفين</h5>
                                
                                <!-- Count Salaries as Expenses -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="count_salaries_as_expenses" class="form-label fw-medium m-0">حساب الرواتب كمصروفات</label>
                                        <span id="countSalariesBadge" class="badge {{ $settings->count_salaries_as_expenses ?? true ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->count_salaries_as_expenses ?? true ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="count_salaries_as_expenses" id="count_salaries_as_expenses"
                                            {{ $settings->count_salaries_as_expenses ?? true ? 'checked' : '' }} onchange="updateBadgeStatus('countSalariesBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم حساب رواتب الموظفين كمصروفات وخصمها من الأرباح في التقارير
                                        </span>
                                    </div>
                                </div>

                                <!-- Salary Display Frequency -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="salary_display_frequency" class="form-label fw-medium m-0">عرض الراتب</label>
                                    </div>
                                    <div class="mb-3">
                                        <select class="form-select" id="salary_display_frequency" name="salary_display_frequency">
                                            <option value="monthly" {{ ($settings->salary_display_frequency ?? 'monthly') == 'monthly' ? 'selected' : '' }}>شهري</option>
                                            <option value="weekly" {{ ($settings->salary_display_frequency ?? 'monthly') == 'weekly' ? 'selected' : '' }}>أسبوعي</option>
                                        </select>
                                        <span class="form-text text-muted small">اختر ما إذا كنت تريد عرض الرواتب بالشهر أو بالأسبوع</span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Printing Settings Tab -->
                        <div class="tab-pane fade p-3" id="printing" role="tabpanel" aria-labelledby="printing-tab">
                            <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="tab" value="printing">
                                <h5 class="border-bottom pb-2 mb-3">إعدادات الطباعة</h5>
                                
                                <!-- Logo and Images Settings -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-image me-2"></i>إعدادات الشعارات والصور</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <!-- Header Logo -->
                                    <div class="mb-4">
                                        <label for="header_logo" class="form-label fw-medium">شعار المتجر العلوي</label>
                                        <input type="file" class="form-control" id="header_logo" name="header_logo" accept="image/*">
                                        <span class="form-text text-muted small">سيظهر في أعلى الفاتورة - أقصى حجم: 2MB، أنواع مدعومة: JPG, PNG, GIF</span>
                                        @if(isset($settings->header_logo) && $settings->header_logo)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $settings->header_logo) }}" alt="Header Logo" style="max-width: 100px; max-height: 60px;" class="border rounded">
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" name="remove_header_logo" id="remove_header_logo">
                                                <label class="form-check-label text-danger" for="remove_header_logo">حذف الشعار الحالي</label>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Header Text Below Logo -->
                                    <div class="mb-4">
                                        <label for="header_text_below_logo" class="form-label">النص تحت الشعار العلوي</label>
                                        <textarea class="form-control" id="header_text_below_logo" name="header_text_below_logo" rows="2">{{ $settings->header_text_below_logo ?? '' }}</textarea>
                                        <span class="form-text text-muted small">النص الذي سيظهر أسفل الشعار العلوي مباشرة (مثل اسم المتجر أو العنوان)</span>
                                    </div>
                                    
                                    <!-- Footer Text Above Bottom Logo -->
                                    <div class="mb-4">
                                        <label for="footer_text_above_logo" class="form-label">النص فوق الشعار السفلي</label>
                                        <textarea class="form-control" id="footer_text_above_logo" name="footer_text_above_logo" rows="2">{{ $settings->footer_text_above_logo ?? '' }}</textarea>
                                        <span class="form-text text-muted small">النص الذي سيظهر قبل الشعار السفلي (مثل رسالة شكر أو معلومات إضافية)</span>
                                    </div>
                                    
                                    <!-- Footer Logo -->
                                    <div class="mb-4">
                                        <label for="footer_logo" class="form-label fw-medium">شعار المتجر السفلي</label>
                                        <input type="file" class="form-control" id="footer_logo" name="footer_logo" accept="image/*">
                                        <span class="form-text text-muted small">سيظهر في أسفل الفاتورة - أقصى حجم: 2MB، أنواع مدعومة: JPG, PNG, GIF</span>
                                        @if(isset($settings->footer_logo) && $settings->footer_logo)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $settings->footer_logo) }}" alt="Footer Logo" style="max-width: 100px; max-height: 60px;" class="border rounded">
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" name="remove_footer_logo" id="remove_footer_logo">
                                                <label class="form-check-label text-danger" for="remove_footer_logo">حذف الشعار الحالي</label>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Show/Hide Options -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_header_logo" id="show_header_logo" 
                                                    {{ $settings->show_header_logo ?? true ? 'checked' : '' }}>
                                                <label class="form-check-label" for="show_header_logo">إظهار الشعار العلوي</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_footer_logo" id="show_footer_logo" 
                                                    {{ $settings->show_footer_logo ?? true ? 'checked' : '' }}>
                                                <label class="form-check-label" for="show_footer_logo">إظهار الشعار السفلي</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Store Information for Printing -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-store me-2"></i>معلومات المتجر للطباعة</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <label for="print_store_name" class="form-label">اسم المتجر</label>
                                        <input type="text" class="form-control" id="print_store_name" name="print_store_name" value="{{ $settings->print_store_name ?? $settings->store_name ?? '' }}">
                                        <span class="form-text text-muted small">اسم المتجر كما سيظهر في الفواتير المطبوعة</span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="print_store_address" class="form-label">عنوان المتجر</label>
                                        <textarea class="form-control" id="print_store_address" name="print_store_address" rows="2">{{ $settings->print_store_address ?? $settings->store_address ?? '' }}</textarea>
                                        <span class="form-text text-muted small">عنوان المتجر كما سيظهر في الفواتير المطبوعة</span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="print_store_phone" class="form-label">رقم الهاتف</label>
                                        <input type="text" class="form-control" id="print_store_phone" name="print_store_phone" value="{{ $settings->print_store_phone ?? $settings->store_phone ?? '' }}">
                                        <span class="form-text text-muted small">رقم هاتف المتجر كما سيظهر في الفواتير المطبوعة</span>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_store_info" id="show_store_info" 
                                                    {{ $settings->show_store_info ?? true ? 'checked' : '' }}>
                                                <label class="form-check-label" for="show_store_info">إظهار معلومات المتجر</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" name="store_info_at_bottom" id="store_info_at_bottom" 
                                                    {{ $settings->store_info_at_bottom ?? true ? 'checked' : '' }}>
                                                <label class="form-check-label" for="store_info_at_bottom">إظهار المعلومات في الأسفل</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Receipt Settings -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-receipt me-2"></i>إعدادات الإيصال</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <label for="receipt_size" class="form-label">حجم الإيصال</label>
                                        <select class="form-select" id="receipt_size" name="receipt_size">
                                            <option value="58mm" {{ ($settings->receipt_size ?? '80mm') == '58mm' ? 'selected' : '' }}>58 مم</option>
                                            <option value="80mm" {{ ($settings->receipt_size ?? '80mm') == '80mm' ? 'selected' : '' }}>80 مم</option>
                                            <option value="a4" {{ ($settings->receipt_size ?? '80mm') == 'a4' ? 'selected' : '' }}>A4</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="print_layout" class="form-label">تخطيط طباعة الفاتورة</label>
                                        <select class="form-select" id="print_layout" name="print_layout">
                                            <option value="layout_1" {{ ($settings->print_layout ?? 'layout_1') == 'layout_1' ? 'selected' : '' }}>تخطيط مدمج</option>
                                            <option value="layout_2" {{ ($settings->print_layout ?? 'layout_1') == 'layout_2' ? 'selected' : '' }}>تخطيط مفصل</option>
                                        </select>
                                        <span class="form-text text-muted small">اختر شكل الفاتورة المطبوعة.</span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="receipt_header" class="form-label">ترويسة إضافية للإيصال</label>
                                        <textarea class="form-control" id="receipt_header" name="receipt_header" rows="2">{{ $settings->receipt_header ?? '' }}</textarea>
                                        <span class="form-text text-muted small">نص إضافي سيظهر في أعلى الإيصال (بعد الشعار والنص المرفق به)</span>
                                        </div>
                                    
                                    <div class="mb-3">
                                        <label for="receipt_footer" class="form-label">تذييل إضافي للإيصال</label>
                                        <textarea class="form-control" id="receipt_footer" name="receipt_footer" rows="2">{{ $settings->receipt_footer ?? 'شكراً لتعاملكم معنا' }}</textarea>
                                        <span class="form-text text-muted small">نص إضافي سيظهر في أسفل الإيصال (قبل الشعار السفلي والنص المرفق به)</span>
                                    </div>
                                </div>
                                
                                <!-- Invoice Settings -->
                                <h6 class="fw-bold mb-3 mt-4"><i class="fas fa-file-invoice me-2"></i>إعدادات الفاتورة</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_tax_in_invoice" id="show_tax_in_invoice" 
                                                {{ $settings->show_tax_in_invoice ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="show_tax_in_invoice">إظهار معلومات الضريبة في الفاتورة</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_customer_info" id="show_customer_info" 
                                                {{ $settings->show_customer_info ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="show_customer_info">إظهار معلومات العميل في الفاتورة</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="auto_print_after_save" id="auto_print_after_save" 
                                                {{ $settings->auto_print_after_save ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="auto_print_after_save">طباعة تلقائية بعد حفظ الفاتورة</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Barcode Settings -->
                                <h6 class="fw-bold mb-3 mt-4"><i class="fas fa-barcode me-2"></i>إعدادات الباركود</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <label for="barcode_type" class="form-label">نوع الباركود</label>
                                        <select class="form-select" id="barcode_type" name="barcode_type">
                                            <option value="CODE128" {{ ($settings->barcode_type ?? 'CODE128') == 'CODE128' ? 'selected' : '' }}>CODE128</option>
                                            <option value="CODE39" {{ ($settings->barcode_type ?? 'CODE128') == 'CODE39' ? 'selected' : '' }}>CODE39</option>
                                            <option value="EAN13" {{ ($settings->barcode_type ?? 'CODE128') == 'EAN13' ? 'selected' : '' }}>EAN13</option>
                                            <option value="UPC" {{ ($settings->barcode_type ?? 'CODE128') == 'UPC' ? 'selected' : '' }}>UPC</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="barcode_label_size" class="form-label">حجم ملصق الباركود</label>
                                        <select class="form-select" id="barcode_label_size" name="barcode_label_size">
                                            <option value="small" {{ ($settings->barcode_label_size ?? 'medium') == 'small' ? 'selected' : '' }}>صغير</option>
                                            <option value="medium" {{ ($settings->barcode_label_size ?? 'medium') == 'medium' ? 'selected' : '' }}>متوسط</option>
                                            <option value="large" {{ ($settings->barcode_label_size ?? 'medium') == 'large' ? 'selected' : '' }}>كبير</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_price_in_barcode" id="show_price_in_barcode" 
                                                {{ $settings->show_price_in_barcode ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="show_price_in_barcode">إظهار السعر في ملصق الباركود</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Print Layout Preview -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-eye me-2"></i>معاينة تخطيط الفاتورة</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>معاينة ترتيب العناصر في الفاتورة المطبوعة:</strong>
                                    </div>
                                    
                                    <div class="preview-container border rounded p-3" style="background-color: #f8f9fa; max-width: 300px; margin: 0 auto; font-size: 0.9rem;">
                                        <div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding-bottom: 8px;">
                                            <div class="text-primary">📷 شعار المتجر العلوي</div>
                                            <div class="text-muted small">النص تحت الشعار العلوي</div>
                                            <div class="text-muted small">(اسم المتجر، العنوان، الهاتف)</div>
                                        </div>
                                        
                                        <div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">
                                            <div class="text-success">ترويسة إضافية للإيصال</div>
                                        </div>
                                        
                                        <div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">
                                            <div class="fw-bold">عنوان الفاتورة ورقمها</div>
                                            <div class="text-muted small">تفاصيل الفاتورة والعميل</div>
                                        </div>
                                        
                                        <div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">
                                            <div class="text-dark">جدول المنتجات والأسعار</div>
                                            <div class="text-muted small">إجماليات الفاتورة</div>
                                        </div>
                                        
                                        <div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">
                                            <div class="text-success">تذييل إضافي للإيصال</div>
                                        </div>
                                        
                                        <div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">
                                            <div class="text-muted small">النص فوق الشعار السفلي</div>
                                            <div class="text-muted small">(معلومات المتجر في الأسفل)</div>
                                        </div>
                                        
                                        <div class="preview-item text-center">
                                            <div class="text-primary">📷 شعار المتجر السفلي</div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            <strong>ملاحظة:</strong> يمكنك تخصيص أي من هذه العناصر أو إخفاؤها حسب احتياجاتك. العناصر الفارغة لن تظهر في الفاتورة المطبوعة.
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                            </button>
                        </div>
                    </form>
                        </div>

                        <!-- Barcode Settings Tab -->
                        <div class="tab-pane fade p-3" id="barcode" role="tabpanel" aria-labelledby="barcode-tab">
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="barcode">
                                <h5 class="border-bottom pb-2 mb-3">إعدادات الباركود</h5>
                                
                                <!-- Barcode Size Settings -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-expand-arrows-alt me-2"></i>إعدادات مقاس الباركود</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode_label_width" class="form-label">عرض ملصق الباركود (مم)</label>
                                                <input type="number" class="form-control" id="barcode_label_width" name="barcode_label_width" 
                                                    value="{{ $settings->barcode_label_width ?? 38 }}" min="20" max="100" step="1">
                                                <span class="form-text text-muted small">المقاس الافتراضي: 38 مم</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode_label_height" class="form-label">ارتفاع ملصق الباركود (مم)</label>
                                                <input type="number" class="form-control" id="barcode_label_height" name="barcode_label_height" 
                                                    value="{{ $settings->barcode_label_height ?? 25 }}" min="15" max="50" step="1">
                                                <span class="form-text text-muted small">المقاس الافتراضي: 25 مم</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode_size_preset" class="form-label">مقاسات جاهزة</label>
                                                <select class="form-select" id="barcode_size_preset" onchange="applyPresetSize()">
                                                    <option value="">اختر مقاس جاهز</option>
                                                    <option value="38x25">38x25 مم (ملصق صغير)</option>
                                                    <option value="50x30">50x30 مم (ملصق متوسط)</option>
                                                    <option value="60x40">60x40 مم (ملصق كبير)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode_dpi" class="form-label">جودة الطباعة (DPI)</label>
                                                <select class="form-select" id="barcode_dpi" name="barcode_dpi">
                                                    <option value="150" {{ ($settings->barcode_dpi ?? '300') == '150' ? 'selected' : '' }}>150 DPI (عادي)</option>
                                                    <option value="300" {{ ($settings->barcode_dpi ?? '300') == '300' ? 'selected' : '' }}>300 DPI (جيد)</option>
                                                    <option value="600" {{ ($settings->barcode_dpi ?? '300') == '600' ? 'selected' : '' }}>600 DPI (ممتاز)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Barcode Content Settings -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>محتوى ملصق الباركود</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" role="switch" name="barcode_show_product_name" id="barcode_show_product_name" 
                                                    {{ $settings->barcode_show_product_name ?? true ? 'checked' : '' }}>
                                                <label class="form-check-label" for="barcode_show_product_name">إظهار اسم المنتج</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" role="switch" name="barcode_show_price" id="barcode_show_price" 
                                                    {{ $settings->barcode_show_price ?? false ? 'checked' : '' }}>
                                                <label class="form-check-label" for="barcode_show_price">إظهار السعر</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" role="switch" name="barcode_show_store_name" id="barcode_show_store_name" 
                                                    {{ $settings->barcode_show_store_name ?? false ? 'checked' : '' }}>
                                                <label class="form-check-label" for="barcode_show_store_name">إظهار اسم المحل</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" role="switch" name="barcode_show_barcode_number" id="barcode_show_barcode_number" 
                                                    {{ $settings->barcode_show_barcode_number ?? true ? 'checked' : '' }}>
                                                <label class="form-check-label" for="barcode_show_barcode_number">إظهار رقم الباركود</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3" id="price_type_container" style="{{ ($settings->barcode_show_price ?? false) ? '' : 'display: none;' }}">
                                        <label for="barcode_price_type" class="form-label">نوع السعر المعروض</label>
                                        <select class="form-select" id="barcode_price_type" name="barcode_price_type">
                                            @foreach(\App\Models\PriceType::where('is_active', true)->get() as $priceType)
                                                <option value="{{ $priceType->id }}" {{ ($settings->barcode_price_type ?? '1') == $priceType->id ? 'selected' : '' }}>
                                                    {{ $priceType->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Barcode Type and Style -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-barcode me-2"></i>نوع وتنسيق الباركود</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode_type" class="form-label">نوع الباركود</label>
                                                <select class="form-select" id="barcode_type" name="barcode_type">
                                                    <option value="C128" {{ ($settings->barcode_type ?? 'C128') == 'C128' ? 'selected' : '' }}>Code 128</option>
                                                    <option value="C39" {{ ($settings->barcode_type ?? 'C128') == 'C39' ? 'selected' : '' }}>Code 39</option>
                                                    <option value="EAN13" {{ ($settings->barcode_type ?? 'C128') == 'EAN13' ? 'selected' : '' }}>EAN-13</option>
                                                    <option value="UPCA" {{ ($settings->barcode_type ?? 'C128') == 'UPCA' ? 'selected' : '' }}>UPC-A</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode_font_size" class="form-label">حجم خط النص</label>
                                                <select class="form-select" id="barcode_font_size" name="barcode_font_size">
                                                    <option value="8" {{ ($settings->barcode_font_size ?? '10') == '8' ? 'selected' : '' }}>صغير (8pt)</option>
                                                    <option value="10" {{ ($settings->barcode_font_size ?? '10') == '10' ? 'selected' : '' }}>متوسط (10pt)</option>
                                                    <option value="12" {{ ($settings->barcode_font_size ?? '10') == '12' ? 'selected' : '' }}>كبير (12pt)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode_height" class="form-label">ارتفاع الباركود (بكسل)</label>
                                                <input type="number" class="form-control" id="barcode_height" name="barcode_height" 
                                                    value="{{ $settings->barcode_height ?? 50 }}" min="30" max="100" step="5">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode_width_factor" class="form-label">عامل عرض الباركود</label>
                                                <select class="form-select" id="barcode_width_factor" name="barcode_width_factor">
                                                    <option value="1" {{ ($settings->barcode_width_factor ?? '2') == '1' ? 'selected' : '' }}>1 (رفيع)</option>
                                                    <option value="2" {{ ($settings->barcode_width_factor ?? '2') == '2' ? 'selected' : '' }}>2 (متوسط)</option>
                                                    <option value="3" {{ ($settings->barcode_width_factor ?? '2') == '3' ? 'selected' : '' }}>3 (عريض)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Print Layout Settings -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-th me-2"></i>إعدادات تخطيط الطباعة</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="barcode_labels_per_row" class="form-label">عدد الملصقات في الصف</label>
                                                <select class="form-select" id="barcode_labels_per_row" name="barcode_labels_per_row">
                                                    <option value="1" {{ ($settings->barcode_labels_per_row ?? '3') == '1' ? 'selected' : '' }}>1</option>
                                                    <option value="2" {{ ($settings->barcode_labels_per_row ?? '3') == '2' ? 'selected' : '' }}>2</option>
                                                    <option value="3" {{ ($settings->barcode_labels_per_row ?? '3') == '3' ? 'selected' : '' }}>3</option>
                                                    <option value="4" {{ ($settings->barcode_labels_per_row ?? '3') == '4' ? 'selected' : '' }}>4</option>
                                                    <option value="5" {{ ($settings->barcode_labels_per_row ?? '3') == '5' ? 'selected' : '' }}>5</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="barcode_margin_horizontal" class="form-label">المسافة الأفقية (مم)</label>
                                                <input type="number" class="form-control" id="barcode_margin_horizontal" name="barcode_margin_horizontal" 
                                                    value="{{ $settings->barcode_margin_horizontal ?? 2 }}" min="0" max="10" step="0.5">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="barcode_margin_vertical" class="form-label">المسافة العمودية (مم)</label>
                                                <input type="number" class="form-control" id="barcode_margin_vertical" name="barcode_margin_vertical" 
                                                    value="{{ $settings->barcode_margin_vertical ?? 2 }}" min="0" max="10" step="0.5">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-eye me-2"></i>معاينة ملصق الباركود</h6>
                                <div class="mb-4 settings-item p-3 rounded text-center">
                                    <div class="barcode-preview border rounded p-3 d-inline-block" style="background: white; min-width: 150px;">
                                        <div id="preview-store-name" style="font-size: 8px; margin-bottom: 2px; display: none;">اسم المحل</div>
                                        <div id="preview-product-name" style="font-size: 10px; margin-bottom: 3px;">اسم المنتج</div>
                                        <div style="background: linear-gradient(90deg, #000 1px, transparent 1px), linear-gradient(90deg, #000 1px, transparent 1px); background-size: 2px 100%, 3px 100%; height: 30px; margin: 5px 0;"></div>
                                        <div id="preview-barcode-number" style="font-size: 8px; margin-bottom: 2px;">1234567890123</div>
                                        <div id="preview-price" style="font-size: 9px; font-weight: bold; display: none;">25.00 ج.م</div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">معاينة تقريبية لشكل الملصق</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                            </button>
                        </div>
                    </form>
                        </div>

                        <!-- Notifications Settings Tab (Placeholder) -->
                        <div class="tab-pane fade p-3" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="notifications">
                                <h5 class="border-bottom pb-2 mb-3">إعدادات الإشعارات</h5>
                                
                                <!-- Inventory Notifications -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-box me-2"></i>إشعارات المخزون</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="low_stock_notification" id="low_stock_notification" 
                                                {{ $settings->low_stock_notification ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="low_stock_notification">تنبيه عند انخفاض المخزون</label>
                                        </div>
                                        <div class="mt-2" id="low_stock_threshold_container">
                                            <label for="low_stock_threshold" class="form-label">حد الإنذار للمخزون المنخفض</label>
                                            <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" 
                                                value="{{ $settings->low_stock_threshold ?? 10 }}" min="1">
                                            <span class="form-text text-muted small">سيتم تنبيهك عندما تصل كمية المنتج إلى هذا الحد أو أقل</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="out_of_stock_notification" id="out_of_stock_notification" 
                                                {{ $settings->out_of_stock_notification ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="out_of_stock_notification">تنبيه عند نفاذ المخزون</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="expiry_date_notification" id="expiry_date_notification" 
                                                {{ $settings->expiry_date_notification ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="expiry_date_notification">تنبيه قبل انتهاء صلاحية المنتجات</label>
                                        </div>
                                        <div class="mt-2" id="expiry_notification_days_container">
                                            <label for="expiry_notification_days" class="form-label">عدد أيام التنبيه قبل انتهاء الصلاحية</label>
                                            <input type="number" class="form-control" id="expiry_notification_days" name="expiry_notification_days" 
                                                value="{{ $settings->expiry_notification_days ?? 30 }}" min="1">
                                            <span class="form-text text-muted small">سيتم تنبيهك قبل انتهاء صلاحية المنتجات بهذا العدد من الأيام</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Sales Notifications -->
                                <h6 class="fw-bold mb-3 mt-4"><i class="fas fa-shopping-cart me-2"></i>إشعارات المبيعات</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="new_sale_notification" id="new_sale_notification" 
                                                {{ $settings->new_sale_notification ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="new_sale_notification">تنبيه عند إتمام عملية بيع جديدة</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="daily_sales_report" id="daily_sales_report" 
                                                {{ $settings->daily_sales_report ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="daily_sales_report">تقرير يومي للمبيعات</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Notification Methods -->
                                <h6 class="fw-bold mb-3 mt-4"><i class="fas fa-bell me-2"></i>طرق الإشعارات</h6>
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="notification_in_app" id="notification_in_app" 
                                                {{ $settings->notification_in_app ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="notification_in_app">إشعارات داخل التطبيق</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="notification_email" id="notification_email" 
                                                {{ $settings->notification_email ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="notification_email">إشعارات عبر البريد الإلكتروني</label>
                                        </div>
                                        <div class="mt-2" id="notification_email_container">
                                            <label for="notification_email_address" class="form-label">البريد الإلكتروني للإشعارات</label>
                                            <input type="email" class="form-control" id="notification_email_address" name="notification_email_address" 
                                                value="{{ $settings->notification_email_address ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Employees Settings Tab -->
                        <div class="tab-pane fade p-3" id="employees" role="tabpanel" aria-labelledby="employees-tab">
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="employees">
                                <h5 class="border-bottom pb-2 mb-3">إعدادات الموظفين</h5>
                                
                                <!-- Count Salaries as Expenses -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="count_salaries_as_expenses" class="form-label fw-medium m-0">حساب الرواتب كمصروفات</label>
                                        <span id="countSalariesBadge" class="badge {{ $settings->count_salaries_as_expenses ?? true ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                            {{ $settings->count_salaries_as_expenses ?? true ? 'مفعل' : 'معطل' }}
                                        </span>
                                    </div>
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="count_salaries_as_expenses" id="count_salaries_as_expenses"
                                            {{ $settings->count_salaries_as_expenses ?? true ? 'checked' : '' }} onchange="updateBadgeStatus('countSalariesBadge', this)">
                                        <span class="form-text text-muted small">
                                            إذا كان مفعلاً، سيتم حساب رواتب الموظفين كمصروفات وخصمها من الأرباح في التقارير
                                        </span>
                                    </div>
                                </div>

                                <!-- Salary Display Frequency -->
                                <div class="mb-4 settings-item p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="salary_display_frequency" class="form-label fw-medium m-0">عرض الراتب</label>
                                    </div>
                                    <div class="mb-3">
                                        <select class="form-select" id="salary_display_frequency" name="salary_display_frequency">
                                            <option value="monthly" {{ ($settings->salary_display_frequency ?? 'monthly') == 'monthly' ? 'selected' : '' }}>شهري</option>
                                            <option value="weekly" {{ ($settings->salary_display_frequency ?? 'monthly') == 'weekly' ? 'selected' : '' }}>أسبوعي</option>
                                        </select>
                                        <span class="form-text text-muted small">اختر ما إذا كنت تريد عرض الرواتب بالشهر أو بالأسبوع</span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-tabs .nav-link {
    border-radius: 0 !important;
    border: none !important;
    position: relative !important;
    transition: all 0.2s !important;
    color: #000000 !important; /* Force black text */
    background-color: #f8f9fa !important; /* Light gray background */
}

.settings-tabs .nav-link.active {
    color: #0d6efd !important; /* Bootstrap primary blue */
    background-color: #ffffff !important; /* White background */
    font-weight: 600 !important;
}

.settings-tabs .nav-link.active:after {
    content: '' !important;
    position: absolute !important;
    bottom: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 3px !important;
    background-color: #0d6efd !important; /* Bootstrap primary blue */
}

.settings-tabs .nav-link:hover:not(.active) {
    background-color: #e9ecef !important; /* Darker gray on hover */
    color: #000000 !important;
}

.settings-item {
    transition: all 0.3s ease;
}

.settings-item:hover {
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}

.form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.form-check-input {
    width: 2.5em;
    height: 1.25em;
    cursor: pointer;
}

.form-check-label {
    cursor: pointer;
}
</style>

<script>
function updateBadgeStatus(badgeId, checkbox) {
    const badge = document.getElementById(badgeId);
    if (checkbox.checked) {
        badge.textContent = 'مفعل';
        badge.classList.remove('bg-secondary');
        badge.classList.add('bg-success');
    } else {
        badge.textContent = 'معطل';
        badge.classList.remove('bg-success');
        badge.classList.add('bg-secondary');
    }
}

function handleNegativeInventoryChange() {
    const allowNegativeInventory = document.getElementById('allow_negative').checked;
    const subtractInventoryOnZero = document.getElementById('subtract_inventory');
    const dependencyNote = document.getElementById('option_dependency_note');
    const parentItem = subtractInventoryOnZero.closest('.settings-item');
    
    // If allow_negative is unchecked, also uncheck subtract_inventory_on_zero
    if (!allowNegativeInventory) {
        subtractInventoryOnZero.checked = false;
        subtractInventoryOnZero.disabled = true;
        dependencyNote.style.display = 'block';
        parentItem.classList.add('bg-light-subtle', 'text-muted');
        parentItem.classList.remove('bg-light');
    } else {
        subtractInventoryOnZero.disabled = false;
        dependencyNote.style.display = 'none';
        parentItem.classList.remove('bg-light-subtle', 'text-muted');
        parentItem.classList.add('bg-light');
    }
    
    // Update the badge statuses
    document.querySelectorAll('.form-check-input').forEach(input => {
        const badge = input.closest('.settings-item').querySelector('.badge');
        if (badge) {
            if (input.checked) {
                badge.textContent = 'مفعل';
                badge.classList.remove('bg-secondary');
                badge.classList.add('bg-success');
            } else {
                badge.textContent = 'معطل';
                badge.classList.remove('bg-success');
                badge.classList.add('bg-secondary');
            }
        }
    });
}

// Toggle visibility of dependent fields
function toggleDependentFields() {
    // Low stock notification threshold
    const lowStockNotification = document.getElementById('low_stock_notification');
    const lowStockThresholdContainer = document.getElementById('low_stock_threshold_container');
    
    if (lowStockNotification) {
        lowStockNotification.addEventListener('change', function() {
            if (lowStockThresholdContainer) {
                lowStockThresholdContainer.style.display = this.checked ? 'block' : 'none';
            }
        });
        
        // Initialize on page load
        if (lowStockThresholdContainer) {
            lowStockThresholdContainer.style.display = lowStockNotification.checked ? 'block' : 'none';
        }
    }
    
    // Expiry notification days
    const expiryNotification = document.getElementById('expiry_date_notification');
    const expiryDaysContainer = document.getElementById('expiry_notification_days_container');
    
    if (expiryNotification) {
        expiryNotification.addEventListener('change', function() {
            if (expiryDaysContainer) {
                expiryDaysContainer.style.display = this.checked ? 'block' : 'none';
            }
        });
        
        // Initialize on page load
        if (expiryDaysContainer) {
            expiryDaysContainer.style.display = expiryNotification.checked ? 'block' : 'none';
        }
    }
    
    // Email notification address
    const emailNotification = document.getElementById('notification_email');
    const emailContainer = document.getElementById('notification_email_container');
    
    if (emailNotification) {
        emailNotification.addEventListener('change', function() {
            if (emailContainer) {
                emailContainer.style.display = this.checked ? 'block' : 'none';
            }
        });
        
        // Initialize on page load
        if (emailContainer) {
            emailContainer.style.display = emailNotification.checked ? 'block' : 'none';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    handleNegativeInventoryChange();
    toggleDependentFields();
    
    // Keep the active tab after form submission
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    
    if (activeTab) {
        const tabEl = document.getElementById(`${activeTab}-tab`);
        if (tabEl) {
            const tab = new bootstrap.Tab(tabEl);
            tab.show();
        }
    }
    
    // Add event listeners for all checkboxes to update badges
    document.querySelectorAll('.form-check-input').forEach(input => {
        input.addEventListener('change', function() {
            const badge = this.closest('.settings-item').querySelector('.badge');
            if (badge) {
                if (this.checked) {
                    badge.textContent = 'مفعل';
                    badge.classList.remove('bg-secondary');
                    badge.classList.add('bg-success');
                } else {
                    badge.textContent = 'معطل';
                    badge.classList.remove('bg-success');
                    badge.classList.add('bg-secondary');
                }
            }
            
            // Update print preview
            updatePrintPreview();
        });
    });
    
    // Add event listeners for text fields to update preview
    const previewFields = ['header_text_below_logo', 'footer_text_above_logo', 'receipt_header', 'receipt_footer'];
    previewFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updatePrintPreview);
        }
    });
    
    // Initial preview update
    updatePrintPreview();
    
    // Initialize barcode settings
    initializeBarcodeSettings();
});

// Update print preview based on current settings
function updatePrintPreview() {
    const preview = document.querySelector('.preview-container');
    if (!preview) return;
    
    // Get current values
    const showHeaderLogo = document.getElementById('show_header_logo')?.checked ?? true;
    const showFooterLogo = document.getElementById('show_footer_logo')?.checked ?? true;
    const showStoreInfo = document.getElementById('show_store_info')?.checked ?? true;
    const storeInfoAtBottom = document.getElementById('store_info_at_bottom')?.checked ?? true;
    
    const headerText = document.getElementById('header_text_below_logo')?.value || '';
    const footerText = document.getElementById('footer_text_above_logo')?.value || '';
    const receiptHeader = document.getElementById('receipt_header')?.value || '';
    const receiptFooter = document.getElementById('receipt_footer')?.value || '';
    
    // Build preview HTML
    let previewHTML = '';
    
    // Header Logo and Text
    if (showHeaderLogo || headerText || (showStoreInfo && !storeInfoAtBottom)) {
        previewHTML += '<div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding-bottom: 8px;">';
        if (showHeaderLogo) {
            previewHTML += '<div class="text-primary">📷 شعار المتجر العلوي</div>';
        }
        if (headerText) {
            previewHTML += `<div class="text-muted small">${headerText}</div>`;
        }
        if (showStoreInfo && !storeInfoAtBottom) {
            previewHTML += '<div class="text-muted small">(اسم المتجر، العنوان، الهاتف)</div>';
        }
        previewHTML += '</div>';
    }
    
    // Receipt Header
    if (receiptHeader) {
        previewHTML += '<div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">';
        previewHTML += `<div class="text-success">${receiptHeader}</div>`;
        previewHTML += '</div>';
    }
    
    // Invoice Content (always shown)
    previewHTML += '<div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">';
    previewHTML += '<div class="fw-bold">عنوان الفاتورة ورقمها</div>';
    previewHTML += '<div class="text-muted small">تفاصيل الفاتورة والعميل</div>';
    previewHTML += '</div>';
    
    previewHTML += '<div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">';
    previewHTML += '<div class="text-dark">جدول المنتجات والأسعار</div>';
    previewHTML += '<div class="text-muted small">إجماليات الفاتورة</div>';
    previewHTML += '</div>';
    
    // Receipt Footer
    if (receiptFooter) {
        previewHTML += '<div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">';
        previewHTML += `<div class="text-success">${receiptFooter}</div>`;
        previewHTML += '</div>';
    }
    
    // Footer Text and Store Info
    if (footerText || (showStoreInfo && storeInfoAtBottom)) {
        previewHTML += '<div class="preview-item text-center mb-2" style="border-bottom: 1px dashed #ccc; padding: 8px 0;">';
        if (footerText) {
            previewHTML += `<div class="text-muted small">${footerText}</div>`;
        }
        if (showStoreInfo && storeInfoAtBottom) {
            previewHTML += '<div class="text-muted small">(معلومات المتجر في الأسفل)</div>';
        }
        previewHTML += '</div>';
    }
    
    // Footer Logo
    if (showFooterLogo) {
        previewHTML += '<div class="preview-item text-center">';
        previewHTML += '<div class="text-primary">📷 شعار المتجر السفلي</div>';
        previewHTML += '</div>';
    }
    
    preview.innerHTML = previewHTML;
}

// Barcode Settings Functions
function initializeBarcodeSettings() {
    // Toggle price type dropdown based on show price checkbox
    const showPriceCheckbox = document.getElementById('barcode_show_price');
    const priceTypeContainer = document.getElementById('price_type_container');
    
    if (showPriceCheckbox && priceTypeContainer) {
        showPriceCheckbox.addEventListener('change', function() {
            priceTypeContainer.style.display = this.checked ? 'block' : 'none';
            updateBarcodePreview();
        });
    }
    
    // Add event listeners for all barcode settings to update preview
    const barcodeInputs = [
        'barcode_show_product_name',
        'barcode_show_price', 
        'barcode_show_store_name',
        'barcode_show_barcode_number',
        'barcode_price_type'
    ];
    
    barcodeInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('change', updateBarcodePreview);
        }
    });
    
    // Initial preview update
    updateBarcodePreview();
}

function applyPresetSize() {
    const preset = document.getElementById('barcode_size_preset').value;
    const widthInput = document.getElementById('barcode_label_width');
    const heightInput = document.getElementById('barcode_label_height');
    
    if (preset && widthInput && heightInput) {
        const [width, height] = preset.split('x');
        widthInput.value = width;
        heightInput.value = height;
    }
}

function updateBarcodePreview() {
    const showStoreName = document.getElementById('barcode_show_store_name')?.checked ?? false;
    const showProductName = document.getElementById('barcode_show_product_name')?.checked ?? true;
    const showPrice = document.getElementById('barcode_show_price')?.checked ?? false;
    const showBarcodeNumber = document.getElementById('barcode_show_barcode_number')?.checked ?? true;
    
    // Update preview elements
    const storeNameEl = document.getElementById('preview-store-name');
    const productNameEl = document.getElementById('preview-product-name');
    const priceEl = document.getElementById('preview-price');
    const barcodeNumberEl = document.getElementById('preview-barcode-number');
    
    if (storeNameEl) {
        storeNameEl.style.display = showStoreName ? 'block' : 'none';
    }
    
    if (productNameEl) {
        productNameEl.style.display = showProductName ? 'block' : 'none';
    }
    
    if (priceEl) {
        priceEl.style.display = showPrice ? 'block' : 'none';
    }
    
    if (barcodeNumberEl) {
        barcodeNumberEl.style.display = showBarcodeNumber ? 'block' : 'none';
    }
}
</script>
@endsection 