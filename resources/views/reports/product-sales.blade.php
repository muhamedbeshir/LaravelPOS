@extends('layouts.app')

@section('title', 'تقرير مبيعات المنتجات')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-box-open me-2"></i>تقرير مبيعات المنتجات</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.product-sales') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="product_id">المنتج</label>
                            <select class="form-control" id="product_id" name="product_id">
                                <option value="">الكل</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category_id">الفئة</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">الكل</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="hour">الساعة</label>
                            <select class="form-control" id="hour" name="hour">
                                <option value="">الكل</option>
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ $i }}" {{ request('hour') == $i ? 'selected' : '' }}>{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">من تاريخ</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">                            
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">إلى تاريخ</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> تصفية</button>
                <a href="{{ route('reports.product-sales') }}" class="btn btn-secondary"><i class="fas fa-undo"></i> إعادة تعيين</a>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>عدد المبيعات</th>
                            <th>إجمالي قيمة المبيعات</th>
                            <th>إجمالي الربح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productSales as $sale)
                            <tr>
                                <td>{{ $sale->product_name }}</td>
                                <td>{{ $sale->sales_count }}</td>
                                <td>{{ number_format($sale->total_sales_value, 2) }}</td>
                                <td>{{ number_format($sale->total_profit, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">لا توجد مبيعات مطابقة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 