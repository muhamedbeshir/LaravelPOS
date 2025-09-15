@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">جرد المخزون</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <form action="{{ route('inventory.save-count') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات الجرد</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="2"></textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>المجموعة</th>
                                        <th>المنتج</th>
                                        <th>المخزون النظري</th>
                                        <th>المخزون الفعلي</th>
                                        <th>الفرق</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $category)
                                        @if($category->products->count() > 0)
                                            <tr class="table-light">
                                                <td colspan="5">
                                                    <strong>{{ $category->name }}</strong>
                                                </td>
                                            </tr>
                                            @foreach($category->products as $product)
                                            <tr>
                                                <td></td>
                                                <td>{{ $product->name }}</td>
                                                <td>
                                                    <span class="theoretical-stock">
                                                        {{ number_format($product->stock_quantity, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <input type="number" 
                                                           class="form-control form-control-sm actual-stock" 
                                                           name="counts[{{ $product->id }}][actual_quantity]"
                                                           step="0.01" 
                                                           min="0"
                                                           value="{{ $product->stock_quantity }}"
                                                           data-product-id="{{ $product->id }}">
                                                    <input type="hidden" 
                                                           name="counts[{{ $product->id }}][product_id]" 
                                                           value="{{ $product->id }}">
                                                </td>
                                                <td>
                                                    <span class="difference" data-product-id="{{ $product->id }}">
                                                        0.00
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('inventory.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-1"></i>
                                رجوع
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ نتائج الجرد
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
    const actualStockInputs = document.querySelectorAll('.actual-stock');
    
    function calculateDifference(input) {
        const productId = input.dataset.productId;
        const theoreticalStock = parseFloat(input.closest('tr').querySelector('.theoretical-stock').textContent.replace(',', ''));
        const actualStock = parseFloat(input.value) || 0;
        const difference = actualStock - theoreticalStock;
        
        const differenceElement = document.querySelector(`.difference[data-product-id="${productId}"]`);
        differenceElement.textContent = difference.toFixed(2);
        
        if (difference < 0) {
            differenceElement.classList.add('text-danger');
            differenceElement.classList.remove('text-success');
        } else if (difference > 0) {
            differenceElement.classList.add('text-success');
            differenceElement.classList.remove('text-danger');
        } else {
            differenceElement.classList.remove('text-danger', 'text-success');
        }
    }

    actualStockInputs.forEach(input => {
        input.addEventListener('input', () => calculateDifference(input));
        calculateDifference(input); // Calculate initial differences
    });
});
</script>
@endpush
@endsection 