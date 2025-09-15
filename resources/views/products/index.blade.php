@extends('layouts.app')

@section('title', 'المنتجات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-boxes text-primary"></i>
            إدارة المنتجات
        </h4>
        <div class="d-flex flex-wrap gap-2">
            @can('create-products')
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> إضافة منتج
            </a>
            <a href="{{ route('products.bulk-create') }}" class="btn btn-success">
                <i class="fas fa-bolt"></i> إضافة سريعة
            </a>
            @endcan
            @if(App\Models\Setting::get('show_colors_options', true))
            <a href="{{ route('colors.index') }}" class="btn" style="background: linear-gradient(90deg, #ff0000, #ffa500, #ffff00, #008000, #0000ff, #4b0082, #ee82ee); color: white; font-weight: bold;">
                <i class="fas fa-palette me-1"></i>
                الألوان
            </a>
            @endif
            @if(App\Models\Setting::get('show_sizes_options', true))
            <a href="{{ route('sizes.index') }}" class="btn btn-secondary">
                <i class="fas fa-ruler me-1"></i>
                المقاسات
            </a>
            @endif
            <a href="{{ route('products.bulk-edit-prices') }}" class="btn btn-warning text-white">
                <i class="fas fa-dollar-sign me-1"></i>
                تعديل الأسعار
            </a>
            <a href="{{ route('products.price-analytics') }}" class="btn btn-info text-white">
                <i class="fas fa-chart-line me-1"></i>
                تحليل الأسعار
            </a>
            <a href="{{ route('bulk-barcodes.index') }}" class="btn btn-info text-white">
                <i class="fas fa-barcode me-1"></i>
                طباعة باركود سريع
            </a>
            <a href="{{ route('products.export') }}" class="btn btn-dark">
                <i class="fas fa-file-export me-1"></i>
                تصدير
            </a>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-file-import me-1"></i>
                استيراد
            </button>
        </div>
    </div>

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

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">فلترة المنتجات</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('products.index') }}" method="GET" id="filter-form">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="search" class="form-label">بحث</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="search" name="search" 
                                            value="{{ request('search') }}" placeholder="اسم المنتج، الباركود، الرقم التسلسلي">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">المجموعة</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">جميع المجموعات</option>
                                        @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="status" class="form-label">الحالة</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">جميع الحالات</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غير نشط</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                                بحث
                            </button>
                            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
    <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">قائمة المنتجات</h5>
                    <span class="badge bg-primary fs-6">{{ $products->total() }} منتج</span>
                </div>
        <div class="card-body">
            <div class="table-responsive">
                        <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                                    <th width="80">الصورة</th>
                            <th>المنتج</th>
                            <th>المجموعة</th>
                                    <th width="150">الباركود</th>
                            <th>الوحدات والأسعار</th>
                                    <th width="100">الحالة</th>
                                    <th width="180">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr>
                                    <td class="text-center">
                                @if($product->image)
                                <img src="{{ asset('storage/products/' . $product->image) }}" 
                                     alt="{{ $product->name }}" 
                                     class="img-thumbnail" 
                                     style="width: 60px; height: 60px; object-fit: contain;">
                                @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-box fa-2x"></i>
                                </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $product->name }}</strong>
                                @if($product->has_serial)
                                <br>
                                <small class="text-muted">
                                            <i class="fas fa-fingerprint me-1"></i>{{ $product->serial_number }}
                                </small>
                                @endif
                                @if($product->alert_quantity > 0)
                                <br>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    حد التنبيه: {{ $product->alert_quantity }}
                                </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge" style="background-color: {{ $product->category->color ?? '#2563eb' }}">
                                    {{ $product->category->name }}
                                </span>
                            </td>
                            <td>
                                @if($product->barcode)
                                <div class="text-center">
                                            <span class="badge bg-light text-dark border fw-normal p-2">
                                                {{ $product->barcode }}
                                            </span>
                                </div>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @foreach($product->units as $productUnit)
                                        <div class="mb-2 p-2 {{ $loop->index > 0 ? 'border-top pt-2' : '' }}">
                                            <div class="d-flex align-items-center mb-1">
                                    <strong>{{ $productUnit->unit->name }}</strong>
                                    @if($productUnit->is_main_unit)
                                                <span class="badge bg-primary ms-2">رئيسية</span>
                                    @endif
                                            </div>
                                            <div class="row">
                                        @foreach($priceTypes as $priceType)
                                            @php
                                                $unitPrice = $productUnit->prices->firstWhere('price_type_id', $priceType->id);
                                            @endphp
                                                    <div class="col-md-4">
                                                        <small>
                                                <span class="text-muted">{{ $priceType->name }}:</span>
                                                            <span class="fw-bold">{{ $unitPrice ? number_format($unitPrice->value, 2) : '-' }}</span>
                                                        </small>
                                                    </div>
                                        @endforeach
                                            </div>
                                        @if($productUnit->barcodes->isNotEmpty())
                                            <div class="mt-2">
                                                <strong class="small text-muted d-block mb-1">الباركودات:</strong>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($productUnit->barcodes as $barcode)
                                                        <span class="badge bg-light text-dark border fw-normal">{{ $barcode->barcode }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                </div>
                                @endforeach
                            </td>
                            <td>
                                <form action="{{ route('products.toggle-active', $product) }}" method="POST" class="d-inline">
                                    @csrf
                                            <button type="submit" class="btn btn-sm {{ $product->is_active ? 'btn-success' : 'btn-danger' }} w-100">
                                        @if($product->is_active)
                                        <i class="fas fa-check-circle me-1"></i> نشط
                                        @else
                                        <i class="fas fa-times-circle me-1"></i> غير نشط
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info text-white show-product-details" 
                                                    data-product-id="{{ $product->id }}" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                            <a href="{{ route('products.edit-prices', $product) }}" class="btn btn-sm btn-info text-white" title="تعديل الأسعار">
                                        <i class="fas fa-dollar-sign"></i>
                                    </a>
                                            <a href="{{ route('products.print-barcode', $product) }}" class="btn btn-sm btn-secondary" title="طباعة الباركود">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="{{ route('products.price-history', $product) }}" class="btn btn-info btn-sm text-white" title="تاريخ الأسعار">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger delete-product" 
                                            data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->name }}" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="{{ route('products.log', $product) }}" class="btn btn-sm btn-info text-white" title="سجل المنتج">
                                        <i class="fas fa-list"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">لا توجد منتجات مضافة</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                    </div>
                    
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted">
                                عرض {{ $products->firstItem() ?? 0 }} إلى {{ $products->lastItem() ?? 0 }} من أصل {{ $products->total() }} منتج
                            </div>
                            <div>
                                <select class="form-select form-select-sm d-inline-block w-auto" id="pagination-size">
                                    <option value="50" {{ $products->perPage() == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $products->perPage() == 100 ? 'selected' : '' }}>100</option>
                                    <option value="200" {{ $products->perPage() == 200 ? 'selected' : '' }}>200</option>
                                </select>
                                <span class="ms-2">منتج في الصفحة</span>
                            </div>
                        </div>
                        
                        @if ($products->hasPages())
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                {{-- Previous Page Link --}}
                                @if ($products->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link" aria-hidden="true">&raquo;</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $products->previousPageUrl() }}" rel="prev" aria-label="السابق">&raquo;</a>
                                    </li>
                                @endif
                                
                                {{-- Pagination Elements --}}
                                @php
                                    $start = max(1, $products->currentPage() - 2);
                                    $end = min($start + 4, $products->lastPage());
                                    $start = max(1, $end - 4);
                                @endphp
                                
                                @if($start > 1)
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $products->url(1) }}">1</a>
                                    </li>
                                    @if($start > 2)
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    @endif
                                @endif
                                
                                @for ($i = $start; $i <= $end; $i++)
                                    <li class="page-item {{ $products->currentPage() == $i ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $products->url($i) }}">{{ $i }}</a>
                                    </li>
                                @endfor
                                
                                @if($end < $products->lastPage())
                                    @if($end < $products->lastPage() - 1)
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    @endif
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $products->url($products->lastPage()) }}">{{ $products->lastPage() }}</a>
                                    </li>
                                @endif
                                
                                {{-- Next Page Link --}}
                                @if ($products->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $products->nextPageUrl() }}" rel="next" aria-label="التالي">&laquo;</a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link" aria-hidden="true">&laquo;</span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal عرض تفاصيل المنتج -->
<div class="modal fade" id="productDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل المنتج</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img id="productImage" src="" alt="صورة المنتج" class="img-fluid mb-3 d-none">
                        <div id="noImage" class="text-muted">
                            <i class="fas fa-image fa-4x mb-3"></i>
                            <p>لا توجد صورة</p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h4 id="productName"></h4>
                        <p class="text-muted" id="productCategory"></p>
                        
                        <div class="mb-3">
                            <strong>الباركود:</strong>
                            <span id="productBarcode"></span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>الرقم التسلسلي:</strong>
                            <span id="productSerial"></span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>حد التنبيه:</strong>
                            <span id="productAlert"></span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>الحالة:</strong>
                            <span id="productStatus"></span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>آخر سعر شراء:</strong>
                            <span id="lastPurchasePrice"></span>
                        </div>
                        
                        <div>
                            <strong>الوحدات والأسعار:</strong>
                            <div id="productUnits" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تأكيد الحذف -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد حذف المنتج</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف المنتج: <strong id="deleteProductName"></strong>؟</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    هذا الإجراء لا يمكن التراجع عنه
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-1"></i>
                    تأكيد الحذف
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal استيراد المنتجات -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">استيراد المنتجات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">ملف Excel</label>
                        <input type="file" class="form-control" id="importFile" name="file" 
                               accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        يجب أن يكون الملف بتنسيق Excel (.xlsx, .xls) أو CSV
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-import me-1"></i>
                        استيراد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Helper function to format numbers
    function number_format(number, decimals = 2) {
        return parseFloat(number).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // عرض تفاصيل المنتج
    const productDetailsModal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
    
    $('.show-product-details').on('click', function() {
        const productId = $(this).data('product-id');
        
        $.ajax({
            url: `/products/${productId}`,
            method: 'GET',
            success: function(data) {
                const product = data.product;
                
                // Debug the response structure
                console.log("Product data:", data);
                
                // تحديث الصورة
                const imageElement = $('#productImage');
                const noImageElement = $('#noImage');
                if (data.imageUrl) {
                    imageElement.attr('src', data.imageUrl).removeClass('d-none');
                    noImageElement.addClass('d-none');
                } else {
                    imageElement.addClass('d-none');
                    noImageElement.removeClass('d-none');
                }
                
                // تحديث المعلومات الأساسية
                $('#productName').text(product.name);
                $('#productCategory').text(product.category.name);
                $('#productBarcode').text(product.barcode || 'غير محدد');
                $('#productSerial').text(product.serial_number || 'غير محدد');
                $('#productAlert').text(product.alert_quantity > 0 ? product.alert_quantity : 'غير محدد');
                $('#productStatus').html(product.is_active ? 
                    '<span class="badge bg-success">نشط</span>' : 
                    '<span class="badge bg-danger">غير نشط</span>');
                
                // Get main unit ID
                let mainUnitId = null;
                for (const unit of product.units) {
                    if (unit.is_main_unit) {
                        mainUnitId = unit.id;
                        break;
                    }
                }
                
                if (mainUnitId) {
                    // Fetch the last purchase price directly from API like in create.blade.php
                    $.get(`/api/product-units/${mainUnitId}/last-purchase-price`, function(response) {
                        console.log("Last purchase API response:", response);
                        
                        if (response.success && response.lastPurchasePrice) {
                            $('#lastPurchasePrice').html(`
                                <span class="fw-bold text-primary">${number_format(response.lastPurchasePrice, 2)}</span>
                                <small class="text-muted ms-2">(${product.units.find(u => u.is_main_unit).unit.name || 'الوحدة الافتراضية'})</small>
                            `);
                        } else {
                            $('#lastPurchasePrice').text('غير محدد');
                        }
                    });
                } else {
                    $('#lastPurchasePrice').text('غير محدد');
                }
                
                // تحديث الوحدات
                const unitsContainer = $('#productUnits');
                unitsContainer.empty();
                
                product.units.forEach(unit => {
                    const unitElement = $('<div>').addClass('card mb-2');
                    unitElement.html(`
                        <div class="card-body">
                            <h6 class="card-title">
                                ${unit.unit_name || unit.unit.name}
                                ${unit.is_main_unit ? '<span class="badge bg-primary">رئيسية</span>' : ''}
                            </h6>
                            <p class="card-text">
                                السعر الرئيسي: ${unit.main_price}<br>
                                ${unit.app_price ? `سعر التطبيق: ${unit.app_price}<br>` : ''}
                                ${unit.other_price ? `سعر آخر: ${unit.other_price}<br>` : ''}
                                ${unit.barcode ? `الباركود: ${unit.barcode}` : ''}
                            </p>
                        </div>
                    `);
                    unitsContainer.append(unitElement);
                });
                
                productDetailsModal.show();
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحميل تفاصيل المنتج');
            }
        });
    });

    // حذف المنتج
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    let productToDelete = null;
    
    $('.delete-product').on('click', function() {
        productToDelete = $(this).data('product-id');
        $('#deleteProductName').text($(this).data('product-name'));
        deleteModal.show();
    });
    
    $('#confirmDelete').on('click', function() {
        const password = $('#deletePassword').val();
        
        // التحقق من كلمة المرور
        $.ajax({
            url: '/settings/verify-delete-password',
            method: 'POST',
            data: JSON.stringify({ password: password }),
            contentType: 'application/json',
            success: function(data) {
                if (data.valid) {
                    // إرسال طلب حذف المنتج
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': `/products/${productToDelete}`
                    });
                    
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': $('meta[name="csrf-token"]').attr('content')
                    }));
                    
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_method',
                        'value': 'DELETE'
                    }));
                    
                    form.appendTo('body').submit();
                } else {
                    alert('كلمة المرور غير صحيحة');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء التحقق من كلمة المرور');
            }
        });
    });
    
    // تحديث الفلاتر تلقائياً عند التغيير
    $('#category_id, #status').on('change', function() {
        $('#filter-form').submit();
    });
    
    // تغيير عدد العناصر في الصفحة
    $('#pagination-size').on('change', function() {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('perPage', $(this).val());
        window.location.href = currentUrl.toString();
    });
    
    // إضافة query string للروابط
    $('.pagination .page-link').each(function() {
        if ($(this).attr('href')) {
            const url = new URL($(this).attr('href'));
            const currentUrl = new URL(window.location.href);
            
            // نقل جميع المعلمات من عنوان URL الحالي إلى رابط الصفحة
            for (const [key, value] of currentUrl.searchParams.entries()) {
                if (key !== 'page') {
                    url.searchParams.set(key, value);
                }
            }
            
            $(this).attr('href', url.toString());
        }
    });
});
</script>
@endpush
@endsection 