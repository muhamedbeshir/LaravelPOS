<!-- Products Table Partial -->
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
                    <td class="fw-bold {{ $product->stock_quantity <= 0 ? 'text-danger' : '' }}">
                        {{ number_format($product->stock_quantity, 2) }}
                    </td>
                    <td>{{ $product->mainUnit ? $product->mainUnit->name : '-' }}</td>
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