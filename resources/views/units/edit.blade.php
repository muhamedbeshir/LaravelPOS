@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">تعديل الوحدة: {{ $unit->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('units.update', $unit) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">اسم الوحدة</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $unit->name) }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_base_unit" 
                                       name="is_base_unit" value="1" 
                                       {{ old('is_base_unit', $unit->is_base_unit) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_base_unit">
                                    وحدة أساسية
                                </label>
                            </div>
                            <small class="text-muted">
                                الوحدة الأساسية هي أصغر وحدة يمكن البيع بها (مثل: قطعة)
                            </small>
                        </div>

                        <div id="unit_details" class="mb-3 {{ old('is_base_unit', $unit->is_base_unit) ? 'd-none' : '' }}">
                            <div class="mb-3">
                                <label for="parent_unit_id" class="form-label">الوحدة الأم</label>
                                <select class="form-select @error('parent_unit_id') is-invalid @enderror" 
                                        id="parent_unit_id" name="parent_unit_id">
                                    <option value="">اختر الوحدة الأم</option>
                                    @foreach($units as $parentUnit)
                                    <option value="{{ $parentUnit->id }}" 
                                        {{ old('parent_unit_id', $unit->parent_unit_id) == $parentUnit->id ? 'selected' : '' }}>
                                        {{ $parentUnit->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('parent_unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="conversion_factor" class="form-label">معامل التحويل</label>
                                <div class="input-group">
                                    <span class="input-group-text">1 {{ $unit->name }} =</span>
                                    <input type="number" class="form-control @error('conversion_factor') is-invalid @enderror" 
                                           id="conversion_factor" name="conversion_factor" 
                                           value="{{ old('conversion_factor', $unit->conversion_factor) }}" 
                                           step="0.01" min="0.01">
                                    <span class="input-group-text" id="parent_unit_name">
                                        {{ $unit->parentUnit ? $unit->parentUnit->name : '' }}
                                    </span>
                                </div>
                                @error('conversion_factor')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('units.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-1"></i>
                                رجوع
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ التغييرات
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
document.getElementById('is_base_unit').addEventListener('change', function() {
    const unitDetails = document.getElementById('unit_details');
    if (this.checked) {
        unitDetails.classList.add('d-none');
    } else {
        unitDetails.classList.remove('d-none');
    }
});

document.getElementById('parent_unit_id').addEventListener('change', function() {
    const parentUnitName = this.options[this.selectedIndex].text;
    document.getElementById('parent_unit_name').textContent = parentUnitName;
});
</script>
@endpush
@endsection 