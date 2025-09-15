@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">إضافة وحدة جديدة</h5>
                </div>
                <div class="card-body">
                    @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('units.store') }}" method="POST" id="unitForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">اسم الوحدة <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_base_unit" 
                                       name="is_base_unit" value="1" {{ old('is_base_unit') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_base_unit">
                                    وحدة أساسية
                                </label>
                            </div>
                            <small class="text-muted">
                                الوحدة الأساسية هي أصغر وحدة يمكن البيع بها (مثل: قطعة)
                            </small>
                        </div>

                        <div id="unit_details" class="mb-3 {{ old('is_base_unit') ? 'd-none' : '' }}">
                            <div class="mb-3">
                                <label for="parent_unit_id" class="form-label">الوحدة الأم <span class="text-danger sub-unit-required">*</span></label>
                                <select class="form-select @error('parent_unit_id') is-invalid @enderror" 
                                        id="parent_unit_id" name="parent_unit_id">
                                    <option value="">اختر الوحدة الأم</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('parent_unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('parent_unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="conversion_factor" class="form-label">معامل التحويل <span class="text-danger sub-unit-required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">1 [الوحدة الجديدة] =</span>
                                    <input type="number" class="form-control @error('conversion_factor') is-invalid @enderror" 
                                           id="conversion_factor" name="conversion_factor" 
                                           value="{{ old('conversion_factor') }}" step="0.01" min="0.01">
                                    <span class="input-group-text">[الوحدة الأم]</span>
                                </div>
                                @error('conversion_factor')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    مثال: 1 علبة = 12 قطعة
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('units.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-1"></i>
                                رجوع
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ
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
document.addEventListener('DOMContentLoaded', function() {
    const isBaseUnitCheckbox = document.getElementById('is_base_unit');
    const unitDetails = document.getElementById('unit_details');
    const parentUnitSelect = document.getElementById('parent_unit_id');
    const conversionFactorInput = document.getElementById('conversion_factor');
    const form = document.getElementById('unitForm');

    function updateUnitDetails() {
        if (isBaseUnitCheckbox.checked) {
            unitDetails.classList.add('d-none');
            parentUnitSelect.value = '';
            conversionFactorInput.value = '';
            parentUnitSelect.removeAttribute('required');
            conversionFactorInput.removeAttribute('required');
        } else {
            unitDetails.classList.remove('d-none');
            parentUnitSelect.setAttribute('required', 'required');
            conversionFactorInput.setAttribute('required', 'required');
        }
    }

    isBaseUnitCheckbox.addEventListener('change', updateUnitDetails);
    updateUnitDetails();

    form.addEventListener('submit', function(e) {
        const nameInput = document.getElementById('name');
        
        if (!nameInput.value.trim()) {
            e.preventDefault();
            alert('يرجى إدخال اسم الوحدة');
            nameInput.focus();
            return false;
        }

        if (!isBaseUnitCheckbox.checked) {
            if (!parentUnitSelect.value) {
                e.preventDefault();
                alert('يرجى اختيار الوحدة الأم');
                parentUnitSelect.focus();
                return false;
            }

            const conversionFactor = parseFloat(conversionFactorInput.value);
            if (!conversionFactor || conversionFactor <= 0) {
                e.preventDefault();
                alert('يرجى إدخال معامل تحويل صحيح أكبر من الصفر');
                conversionFactorInput.focus();
                return false;
            }
        }
    });
});
</script>
@endpush

@endsection 