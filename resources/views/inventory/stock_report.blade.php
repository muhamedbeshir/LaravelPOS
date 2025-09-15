@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-boxes text-primary fa-2x me-2"></i>
                <h2 class="mb-0">تقرير المخزن</h2>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <form id="filter-form" action="{{ route('inventory.stock-report') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="category_id" class="form-label">التصنيف</label>
                            <select class="form-select shadow-sm filter-input" id="category_id" name="category_id">
                                <option value="">كل التصنيفات</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">بحث</label>
                            <input type="text" class="form-control shadow-sm filter-input" id="search" name="search" 
                                   placeholder="اسم المنتج أو الباركود" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="stock_status" class="form-label">حالة المخزون</label>
                            <select class="form-select shadow-sm filter-input" id="stock_status" name="stock_status">
                                <option value="">كل المنتجات</option>
                                <option value="in_stock" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>
                                    متوفر في المخزون
                                </option>
                                <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>
                                    نفذ من المخزون
                                </option>
                                <option value="low_stock" {{ request('stock_status') == 'low_stock' ? 'selected' : '' }}>
                                    مخزون منخفض
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-flex w-100">
                                <button type="button" class="btn btn-secondary me-2" id="reset-filters">
                                    <i class="fas fa-redo me-1"></i> إعادة تعيين
                                </button>
                                <div class="spinner-border text-primary ms-2 d-none" id="loading-spinner" role="status">
                                    <span class="visually-hidden">جاري التحميل...</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="products-table-container">
                    <!-- This section will be replaced by AJAX -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>المنتج</th>
                                        <th>الباركود</th>
                                        <th>التصنيف</th>
                                        <th>الكمية الحالية</th>
                                        <th>الوحدة</th>
                                        <th>الحد الأدنى</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $index => $product)
                                    <tr>
                                        <td class="ps-3">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($product->image)
                                                    <img src="{{ asset('storage/products/' . $product->image) }}" 
                                                        class="rounded me-2" width="40" height="40" alt="{{ $product->name }}">
                                                @else
                                                    <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" 
                                                        style="width: 40px; height: 40px;">
                                                        <i class="fas fa-box text-secondary"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold">{{ $product->name }}</div>
                                                    <small class="text-muted">كود: {{ $product->barcode ?? 'ID: ' . $product->id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $product->barcode ?? '-' }}</td>
                                        <td>{{ $product->category ? $product->category->name : '-' }}</td>
                                        <td class="fw-bold stock-quantity-cell" id="stock-quantity-{{ $product->id }}">
                                            {{ number_format($product->getStockQuantity($product->main_unit_id), 2) }}
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm unit-selector" data-product-id="{{ $product->id }}">
                                                @foreach($product->units as $unit)
                                                    <option value="{{ $unit->unit_id }}" {{ $unit->is_main_unit ? 'selected' : '' }}>
                                                        {{ $unit->unit ? $unit->unit->name : '---' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>{{ number_format($product->alert_quantity, 2) }}</td>
                                        <td>
                                            @if($product->stock_quantity <= 0)
                                                <span class="badge bg-danger">نفذ المخزون</span>
                                            @elseif($product->alert_quantity > 0 && $product->stock_quantity <= $product->alert_quantity)
                                                <span class="badge bg-warning">مخزون منخفض</span>
                                            @else
                                                <span class="badge bg-success">متوفر</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-box-open text-muted fa-2x mb-3"></i>
                                            <p class="text-muted">لم يتم العثور على منتجات</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center p-3">
                            {{ $products->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summary-cards">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">إجمالي المنتجات</h6>
                            <h3 class="mb-0 fw-bold total-products">{{ \App\Models\Product::count() }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-boxes fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">المنتجات المتوفرة</h6>
                            <h3 class="mb-0 fw-bold text-success in-stock-products">{{ \App\Models\Product::where('stock_quantity', '>', 0)->count() }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">المنتجات نفذت من المخزون</h6>
                            <h3 class="mb-0 fw-bold text-danger out-of-stock-products">{{ \App\Models\Product::where('stock_quantity', '<=', 0)->count() }}</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-exclamation-circle fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #2563eb, #3b82f6);
}

.bg-gradient-success {
    background: linear-gradient(45deg, #059669, #10b981);
}

.bg-gradient-info {
    background: linear-gradient(45deg, #0284c7, #0ea5e9);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.transition-hover {
    transition: all 0.2s;
}

.transition-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.table th, .table td {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    const filterForm = document.getElementById('filter-form');
    const filterInputs = document.querySelectorAll('.filter-input');
    const loadingSpinner = document.getElementById('loading-spinner');
    const resetButton = document.getElementById('reset-filters');
    let currentPage = 1;
    
    // Function to load products using AJAX
    function loadProducts(page = 1) {
        loadingSpinner.classList.remove('d-none');
        
        const formData = new FormData(filterForm);
        formData.append('page', page);
        formData.append('ajax', 'true');
        
        const url = '{{ route('inventory.stock-report') }}?' + new URLSearchParams(formData).toString();
        console.log('Fetching URL:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json().catch(error => {
                console.error('Error parsing JSON:', error);
                throw new Error('Failed to parse response as JSON');
            });
        })
        .then(data => {
            console.log('Response data:', data);
            if (!data || typeof data !== 'object') {
                console.error('Response is not a valid object:', data);
                throw new Error('Invalid response data format');
            }
            
            if (data.error) {
                console.error('Server returned an error:', data.error);
                throw new Error(data.error);
            }
            
            if (!data.html) {
                console.error('Response data is missing HTML property:', data);
                throw new Error('Invalid response data: missing HTML');
            }
            
            try {
                document.getElementById('products-table-container').innerHTML = data.html;
                
                // Update summary cards if stats are available
                if (data.stats) {
                    if (data.stats.total !== undefined) {
                        document.querySelector('.total-products').textContent = data.stats.total;
                    }
                    if (data.stats.in_stock !== undefined) {
                        document.querySelector('.in-stock-products').textContent = data.stats.in_stock;
                    }
                    if (data.stats.out_of_stock !== undefined) {
                        document.querySelector('.out-of-stock-products').textContent = data.stats.out_of_stock;
                    }
                } else {
                    console.warn('Response data is missing stats property:', data);
                }
                
                // Update browser URL with current filter state
                const url = new URL(window.location);
                for (const [key, value] of formData.entries()) {
                    if (key !== 'ajax') {
                        if (value) {
                            url.searchParams.set(key, value);
                        } else {
                            url.searchParams.delete(key);
                        }
                    }
                }
                window.history.pushState({}, '', url);
                
                // Setup pagination links after content update
                setupPaginationLinks();
                
            } catch (innerError) {
                console.error('Error updating DOM with response data:', innerError);
                throw new Error('Failed to update page with response data: ' + innerError.message);
            }
            
            loadingSpinner.classList.add('d-none');
        })
        .catch(error => {
            console.error('Error fetching products:', error);
            loadingSpinner.classList.add('d-none');
            
            // Try again with debug mode enabled
            console.log('Retrying with debug mode...');
            const debugFormData = new FormData(filterForm);
            debugFormData.append('page', currentPage);
            debugFormData.append('ajax', 'true');
            debugFormData.append('debug_mode', 'true');
            
            const debugUrl = '{{ route('inventory.stock-report') }}?' + new URLSearchParams(debugFormData).toString();
            console.log('Debug URL:', debugUrl);
            
            fetch(debugUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(debugData => {
                console.log('Debug response:', debugData);
            })
            .catch(debugError => {
                console.error('Debug request also failed:', debugError);
            });
            
            // Create an error alert to inform the user
            const alertContainer = document.createElement('div');
            alertContainer.className = 'alert alert-danger alert-dismissible fade show mt-3';
            alertContainer.role = 'alert';
            
            alertContainer.innerHTML = `
                <strong>خطأ!</strong> حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Add the alert to the page
            const productsContainer = document.getElementById('products-table-container');
            productsContainer.parentNode.insertBefore(alertContainer, productsContainer);
            
            // Automatically remove the alert after 5 seconds
            setTimeout(() => {
                alertContainer.remove();
            }, 5000);
        });
    }
    
    // Setup event listeners for filter inputs
    filterInputs.forEach(input => {
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', () => {
                currentPage = 1;
                loadProducts();
            });
        } else if (input.type === 'text') {
            input.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadProducts();
                }, 500);
            });
        }
    });
    
    // Reset filters
    resetButton.addEventListener('click', () => {
        filterForm.reset();
        currentPage = 1;
        loadProducts();
    });
    
    // Function to setup pagination links
    function setupPaginationLinks() {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                const page = url.searchParams.get('page') || 1;
                currentPage = page;
                loadProducts(page);
                
                // Scroll to top of table
                document.getElementById('products-table-container').scrollIntoView({ behavior: 'smooth' });
            });
        });
    }
    
    // Initial setup of pagination links
    setupPaginationLinks();

    document.querySelectorAll('.unit-selector').forEach(function(select) {
        select.addEventListener('change', function() {
            var productId = this.getAttribute('data-product-id');
            var unitId = this.value;
            fetch(`/inventory/get-stock-quantity/${productId}/${unitId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('stock-quantity-' + productId).textContent = data.quantity;
                });
        });
    });
});
</script>
@endpush
@endsection 