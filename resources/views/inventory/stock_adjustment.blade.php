@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center mb-3">
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-arrow-right"></i>
                </a>
                <div>
                    <h2 class="mb-0"><i class="fas fa-boxes text-primary me-2"></i>تعديل المخزون</h2>
                    <p class="text-muted small mb-0">إضافة أو خصم كمية من المخزون</p>
                </div>
            </div>
            
            <div class="card shadow-sm border-0">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="card-body p-4">
                    <form action="{{ route('inventory.save-adjustment') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="product_id" class="form-label fw-bold">المنتج</label>
                            <select class="form-select form-select-lg @error('product_id') is-invalid @enderror" 
                                    id="product_id" name="product_id" required>
                                <option value="">اختر المنتج</option>
                                @foreach($products as $product)
                                <option value="{{ $product->id }}" 
                                        data-stock="{{ $product->stock_quantity }}">
                                    {{ $product->name }}
                                    <span class="text-muted">(المخزون: {{ number_format($product->stock_quantity, 2) }})</span>
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                اختر المنتج الذي تريد تعديل مخزونه
                            </div>
                            @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="adjustment_type" class="form-label fw-bold">نوع التعديل</label>
                                <div class="d-flex">
                                    <div class="form-check form-check-inline flex-fill">
                                        <input class="form-check-input" type="radio" name="adjustment_type" id="type_add" value="add" checked>
                                        <label class="form-check-label p-2 rounded w-100 text-center" for="type_add">
                                            <i class="fas fa-plus-circle text-success me-1"></i>
                                            إضافة للمخزون
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline flex-fill">
                                        <input class="form-check-input" type="radio" name="adjustment_type" id="type_subtract" value="subtract">
                                        <label class="form-check-label p-2 rounded w-100 text-center" for="type_subtract">
                                            <i class="fas fa-minus-circle text-danger me-1"></i>
                                            خصم من المخزون
                                        </label>
                                    </div>
                                </div>
                                @error('adjustment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="quantity" class="form-label fw-bold">الكمية</label>
                                <div class="input-group">
                                    <input type="number" class="form-control form-control-lg @error('quantity') is-invalid @enderror" 
                                        id="quantity" name="quantity" step="0.01" min="0.01" required placeholder="0.00">
                                    <span class="input-group-text" id="unit-display">وحدة</span>
                                </div>
                                <div id="stockWarning" class="form-text text-danger d-none">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    الكمية المطلوبة أكبر من المخزون المتوفر
                                </div>
                                @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label fw-bold">ملاحظات</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                    id="notes" name="notes" rows="3" placeholder="أضف ملاحظات حول سبب تعديل المخزون"></textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="stock-summary p-3 mb-3 rounded bg-light d-none" id="stock-summary">
                            <h6 class="border-bottom pb-2 mb-2">ملخص التعديل</h6>
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1 small">المخزون الحالي:</p>
                                    <p class="fw-bold" id="current-stock">0</p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1 small">المخزون بعد التعديل:</p>
                                    <p class="fw-bold" id="new-stock">0</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                                <i class="fas fa-save me-1"></i>
                                حفظ التعديل
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.form-check-inline {
    margin-right: 0;
}

.form-check-input {
    display: none;
}

.form-check-label {
    cursor: pointer;
    border: 1px solid #dee2e6;
    transition: all 0.2s;
}

.form-check-input:checked + .form-check-label {
    background-color: #f8f9fa;
    border-color: #6c757d;
    font-weight: bold;
}

#type_add:checked + .form-check-label {
    background-color: rgba(25, 135, 84, 0.1);
    border-color: #198754;
    color: #198754;
}

#type_subtract:checked + .form-check-label {
    background-color: rgba(220, 53, 69, 0.1);
    border-color: #dc3545;
    color: #dc3545;
}

.stock-summary {
    border: 1px solid #dee2e6;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const typeAdd = document.getElementById('type_add');
    const typeSubtract = document.getElementById('type_subtract');
    const quantity = document.getElementById('quantity');
    const stockWarning = document.getElementById('stockWarning');
    const submitBtn = document.getElementById('submitBtn');
    const stockSummary = document.getElementById('stock-summary');
    const currentStockEl = document.getElementById('current-stock');
    const newStockEl = document.getElementById('new-stock');

    function updateStockSummary() {
        if (!productSelect.value) {
            stockSummary.classList.add('d-none');
            return;
        }

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const currentStock = parseFloat(selectedOption.dataset.stock);
        const requestedQuantity = parseFloat(quantity.value) || 0;
        
        currentStockEl.textContent = currentStock.toFixed(2);
        
        let newStock;
        if (typeAdd.checked) {
            newStock = currentStock + requestedQuantity;
            newStockEl.classList.remove('text-danger');
            newStockEl.classList.add('text-success');
        } else {
            newStock = currentStock - requestedQuantity;
            if (newStock < 0) {
                newStockEl.classList.add('text-danger');
                newStockEl.classList.remove('text-success');
            } else {
                newStockEl.classList.remove('text-danger');
                newStockEl.classList.remove('text-success');
            }
        }
        
        newStockEl.textContent = newStock.toFixed(2);
        stockSummary.classList.remove('d-none');
    }

    function validateQuantity() {
        if (typeSubtract.checked) {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) return;
            
            const currentStock = parseFloat(selectedOption.dataset.stock);
            const requestedQuantity = parseFloat(quantity.value);

            if (requestedQuantity > currentStock) {
                stockWarning.classList.remove('d-none');
                submitBtn.disabled = true;
            } else {
                stockWarning.classList.add('d-none');
                submitBtn.disabled = false;
            }
        } else {
            stockWarning.classList.add('d-none');
            submitBtn.disabled = false;
        }
        
        updateStockSummary();
    }

    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.value) {
            document.getElementById('unit-display').textContent = 'وحدة';
            validateQuantity();
        } else {
            stockSummary.classList.add('d-none');
        }
    });
    
    [typeAdd, typeSubtract].forEach(el => {
        el.addEventListener('change', validateQuantity);
    });
    
    quantity.addEventListener('input', validateQuantity);
});
</script>
@endpush
@endsection 