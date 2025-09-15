<!-- Modal: إضافة عميل جديد -->
<div class="modal fade" id="add-customer-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة عميل جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-customer-form">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="mb-3">
                        <label class="form-label">اسم العميل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="customer-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="customer-phone" name="phone" required>
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
                        <select class="form-control" name="default_price_type_id" id="customer-default-price-type">
                            <option value="">استخدام الإعدادات العامة</option>
                            @if(isset($priceTypes))
                                @foreach($priceTypes as $priceType)
                                <option value="{{ $priceType->id }}">{{ $priceType->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-muted">إذا تم اختيار سعر افتراضي، سيتم تجاهل الإعدادات العامة لهذا العميل</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">العنوان</label>
                        <textarea class="form-control" id="customer-address" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="customer-notes" name="notes" rows="2"></textarea>
                    </div>
                    <input type="submit" class="d-none">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="customerHelper.saveNewCustomer()">حفظ العميل</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: اختيار عميل -->
<div class="modal fade" id="select-customer-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">اختيار عميل</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="customer-search" placeholder="بحث عن عميل بالاسم أو رقم الهاتف...">
                        <button class="btn btn-outline-primary" type="button" id="search-customer-btn">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-striped" id="customers-table">
                        <thead class="bg-light">
                            <tr>
                                <th>الاسم</th>
                                <th>الهاتف</th>
                                <th>العنوان</th>
                                <th>الرصيد</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($customers))
                                @foreach($customers as $customer)
                                    <tr data-id="{{ $customer->id }}">
                                        <td>{{ $customer->name }}</td>
                                        <td>{{ $customer->phone }}</td>
                                        <td>{{ $customer->address ?? '-' }}</td>
                                        <td>{{ $customer->credit_balance ?? '0.00' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-success select-customer-btn" 
                                                data-id="{{ $customer->id }}"
                                                data-name="{{ $customer->name }}"
                                                data-credit="{{ $customer->credit_balance ?? '0.00' }}"
                                                data-credit-limit="{{ $customer->credit_limit ?? '0.00' }}"
                                                data-is-unlimited-credit="{{ $customer->is_unlimited_credit ? '1' : '0' }}"
                                                data-address="{{ $customer->address ?? '' }}"
                                                data-default-price-type-code="{{ $customer->defaultPriceType ? $customer->defaultPriceType->code : '' }}">
                                                <i class="fas fa-check"></i> اختيار
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center py-3" id="no-customers-found" style="display: none;">
                    <i class="fas fa-search fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">لم يتم العثور على أي عملاء مطابقين للبحث</p>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#add-customer-modal">
                    <i class="fas fa-plus"></i> إضافة عميل جديد
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div> 