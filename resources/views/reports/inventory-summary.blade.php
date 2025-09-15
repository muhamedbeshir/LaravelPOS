@extends('layouts.app')

@section('title', 'تقرير إجمالي قيمة المخزون')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>تقرير إجمالي قيمة المخزون</h5>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" action="{{ route('reports.inventory-summary') }}" class="mb-4 p-3 bg-light border rounded">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="category_id" class="form-label">المجموعة</label>
                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">كل المجموعات</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="price_type_id" class="form-label">حساب سعر البيع بناءً على</label>
                        <select name="price_type_id" id="price_type_id" class="form-select">
                            @foreach($priceTypes as $priceType)
                                <option value="{{ $priceType->id }}" {{ $selectedPriceTypeId == $priceType->id ? 'selected' : '' }}>
                                    {{ $priceType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>تصفية</button>
                    </div>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card text-center text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-dollar-sign me-2"></i>إجمالي قيمة الشراء</h5>
                            <p class="card-text fs-4 fw-bold">{{ number_format($totalPurchaseValue, 2) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card text-center text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-cash-register me-2"></i>إجمالي قيمة البيع</h5>
                            <p class="card-text fs-4 fw-bold">{{ number_format($totalSaleValue, 2) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="card text-center text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>إجمالي الربح المتوقع</h5>
                            <p class="card-text fs-4 fw-bold">{{ number_format($expectedProfit, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>المنتج</th>
                            <th>المجموعة</th>
                            <th>الكمية بالمخزون (وحدة أساسية)</th>
                            <th>تكلفة الوحدة</th>
                            <th>سعر بيع الوحدة</th>
                            <th>إجمالي التكلفة</th>
                            <th>إجمالي البيع</th>
                            <th>الربح المتوقع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            @php
                                $mainUnit = $product->units->firstWhere('is_main_unit', true);
                                $cost = $mainUnit->cost ?? 0;
                                $price = $mainUnit->prices->first()->value ?? 0;
                                $totalCost = $product->stock_quantity * $cost;
                                $totalSale = $product->stock_quantity * $price;
                            @endphp
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                <td>{{ $product->stock_quantity }}</td>
                                <td>{{ number_format($cost, 2) }}</td>
                                <td>{{ number_format($price, 2) }}</td>
                                <td>{{ number_format($totalCost, 2) }}</td>
                                <td>{{ number_format($totalSale, 2) }}</td>
                                <td>{{ number_format($totalSale - $totalCost, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">لا توجد منتجات مطابقة للبحث.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 