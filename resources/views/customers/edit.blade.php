@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تعديل بيانات العميل</h5>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> رجوع
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.update', $customer) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم العميل</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           name="name" value="{{ old('name', $customer->name) }}" required>
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                           name="phone" value="{{ old('phone', $customer->phone) }}" required>
                                    @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">حد الائتمان</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('credit_limit') is-invalid @enderror" 
                                               name="credit_limit" id="credit-limit-input" value="{{ old('credit_limit', $customer->credit_limit) }}" step="0.01" min="0" {{ $customer->is_unlimited_credit ? 'disabled' : '' }}>
                                        <span class="input-group-text">جنيه</span>
                                    </div>
                                    <small class="text-muted">الحد الأقصى للرصيد المسموح به للعميل</small>
                                    @error('credit_limit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="unlimited-credit" 
                                               {{ $customer->is_unlimited_credit ? 'checked' : '' }}>
                                        <label class="form-check-label" for="unlimited-credit">
                                            <span class="text-primary">ائتمان غير محدود</span>
                                        </label>
                                        <input type="hidden" name="has_unlimited_credit" id="has-unlimited-credit" value="{{ $customer->is_unlimited_credit ? '1' : '0' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">السعر الافتراضي</label>
                                    <select class="form-control @error('default_price_type_id') is-invalid @enderror" 
                                            name="default_price_type_id">
                                        <option value="">استخدام الإعدادات العامة</option>
                                        @foreach($priceTypes as $priceType)
                                        <option value="{{ $priceType->id }}" 
                                                {{ old('default_price_type_id', $customer->default_price_type_id) == $priceType->id ? 'selected' : '' }}>
                                            {{ $priceType->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">إذا تم اختيار سعر افتراضي، سيتم تجاهل الإعدادات العامة لهذا العميل</small>
                                    @error('default_price_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">العنوان</label>
                                    <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                           name="address" value="{{ old('address', $customer->address) }}">
                                    @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">معلومات إضافية</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                           name="notes" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                                    @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle unlimited credit toggle
        $('#unlimited-credit').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('#has-unlimited-credit').val(isChecked ? '1' : '0');
            
            if (isChecked) {
                // Store the current value before disabling
                $('#credit-limit-input').data('previous-value', $('#credit-limit-input').val());
                // Just disable the input with a default value
                $('#credit-limit-input').val('0').prop('disabled', true);
            } else {
                // Restore the previous value if available, otherwise set to 0
                const previousValue = $('#credit-limit-input').data('previous-value') || '0';
                $('#credit-limit-input').val(previousValue).prop('disabled', false);
            }
        });
        
        // Trigger the change event on page load to set the initial state
        $('#unlimited-credit').trigger('change');
    });
</script>
@endpush
@endsection 