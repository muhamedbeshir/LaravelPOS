@extends('layouts.app')

@section('title', 'تعديل عرض ترويجي')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تعديل عرض ترويجي: {{ $promotion->name }}</h3>
                </div>
                <form action="{{ route('promotions.update', $promotion->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @include('partials.flash_messages')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">اسم العرض <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $promotion->name) }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="promotion_type">نوع العرض <span class="text-danger">*</span></label>
                                    <select class="form-control @error('promotion_type') is-invalid @enderror" id="promotion_type" name="promotion_type" required>
                                        <option value="">-- اختر نوع العرض --</option>
                                        <option value="simple_discount" {{ old('promotion_type', $promotion->promotion_type) == 'simple_discount' ? 'selected' : '' }}>خصم بسيط</option>
                                        <option value="buy_x_get_y" {{ old('promotion_type', $promotion->promotion_type) == 'buy_x_get_y' ? 'selected' : '' }}>اشتر X واحصل على Y</option>
                                        <option value="spend_x_save_y" {{ old('promotion_type', $promotion->promotion_type) == 'spend_x_save_y' ? 'selected' : '' }}>أنفق X ووفر Y</option>
                                        <option value="coupon_code" {{ old('promotion_type', $promotion->promotion_type) == 'coupon_code' ? 'selected' : '' }}>كوبون خصم</option>
                                    </select>
                                    @error('promotion_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="applies_to">ينطبق على <span class="text-danger">*</span></label>
                                    <select class="form-control @error('applies_to') is-invalid @enderror" id="applies_to" name="applies_to" required>
                                        <option value="">-- اختر --</option>
                                        <option value="product" {{ old('applies_to', $promotion->applies_to) == 'product' ? 'selected' : '' }}>منتج محدد</option>
                                        <option value="category" {{ old('applies_to', $promotion->applies_to) == 'category' ? 'selected' : '' }}>تصنيف</option>
                                        <option value="all" {{ old('applies_to', $promotion->applies_to) == 'all' ? 'selected' : '' }}>جميع المنتجات</option>
                                    </select>
                                    @error('applies_to')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="discount_value">قيمة الخصم</label>
                                    <input type="number" step="0.01" class="form-control @error('discount_value') is-invalid @enderror" id="discount_value" name="discount_value" value="{{ old('discount_value', $promotion->discount_value) }}">
                                    @error('discount_value')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">تاريخ البدء</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $promotion->start_date ? $promotion->start_date->format('Y-m-d') : '') }}">
                                    @error('start_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">تاريخ الانتهاء</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $promotion->end_date ? $promotion->end_date->format('Y-m-d') : '') }}">
                                    @error('end_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="minimum_purchase">الحد الأدنى للشراء</label>
                                    <input type="number" step="0.01" class="form-control @error('minimum_purchase') is-invalid @enderror" id="minimum_purchase" name="minimum_purchase" value="{{ old('minimum_purchase', $promotion->minimum_purchase) }}">
                                    @error('minimum_purchase')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="maximum_discount">الحد الأقصى للخصم</label>
                                    <input type="number" step="0.01" class="form-control @error('maximum_discount') is-invalid @enderror" id="maximum_discount" name="maximum_discount" value="{{ old('maximum_discount', $promotion->maximum_discount) }}">
                                    @error('maximum_discount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="usage_limit">حد الاستخدام</label>
                                    <input type="number" class="form-control @error('usage_limit') is-invalid @enderror" id="usage_limit" name="usage_limit" value="{{ old('usage_limit', $promotion->usage_limit) }}">
                                    @error('usage_limit')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">الحالة</label>
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">نشط</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">الوصف</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $promotion->description) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="product-selection d-none" id="product-selection">
                            <div class="form-group">
                                <label for="products">المنتجات</label>
                                <select class="form-control select2 @error('products') is-invalid @enderror" id="products" name="products[]" multiple>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ in_array($product->id, old('products', $promotion->products->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                @error('products')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="category-selection d-none" id="category-selection">
                            <div class="form-group">
                                <label for="categories">التصنيفات</label>
                                <select class="form-control select2 @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('categories')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="customer-selection">
                            <div class="form-group">
                                <label for="customers">العملاء (اختياري - إذا كان العرض محدد لعملاء معينين)</label>
                                <select class="form-control select2 @error('customers') is-invalid @enderror" id="customers" name="customers[]" multiple>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ in_array($customer->id, old('customers', $promotion->customers->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customers')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        <a href="{{ route('promotions.index') }}" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        // Initialize select2
        $('.select2').select2();
        
        // Handle applies_to change
        $('#applies_to').change(function() {
            var appliesTo = $(this).val();
            
            // Hide all selection divs first
            $('.product-selection, .category-selection').addClass('d-none');
            
            // Show the appropriate selection div based on applies_to
            if (appliesTo === 'product') {
                $('.product-selection').removeClass('d-none');
            } else if (appliesTo === 'category') {
                $('.category-selection').removeClass('d-none');
            }
        });
        
        // Trigger change on page load to set initial state
        $('#applies_to').trigger('change');
        
        // Handle promotion_type change
        $('#promotion_type').change(function() {
            var promotionType = $(this).val();
            
            // Show/hide fields based on promotion type
            if (promotionType === 'simple_discount') {
                $('#discount_value').prop('required', true);
            } else {
                $('#discount_value').prop('required', false);
            }
        });
        
        // Trigger change on page load to set initial state
        $('#promotion_type').trigger('change');
    });
</script>
@endsection 