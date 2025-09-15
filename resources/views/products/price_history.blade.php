@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">تاريخ أسعار المنتج: {{ $product->name }}</h5>
                    <a href="{{ route('products.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-right me-1"></i>
                        رجوع
                    </a>
                </div>

                <div class="card-body">
                    <!-- معلومات المنتج -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>المجموعة:</strong> {{ $product->category->name }}</p>
                            <p><strong>الباركود:</strong> {{ $product->barcode ?? 'غير محدد' }}</p>
                            <p><strong>الحالة:</strong> 
                                <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $product->is_active ? 'نشط' : 'غير نشط' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            @if($product->image)
                            <img src="{{ asset('storage/products/' . $product->image) }}" 
                                 alt="{{ $product->name }}" 
                                 class="img-thumbnail" 
                                 style="max-height: 100px">
                            @endif
                        </div>
                    </div>

                    <!-- تاريخ الأسعار لكل وحدة -->
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs nav-fill mb-3">
                            @foreach($product->units as $productUnit)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                   href="#tab_{{ $productUnit->id }}" 
                                   data-bs-toggle="tab">
                                    {{ $productUnit->unit->name }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="tab-content">
                        @foreach($product->units as $productUnit)
                        <div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="tab_{{ $productUnit->id }}">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        {{ $productUnit->unit->name }}
                                        @if($productUnit->is_main_unit)
                                        <span class="badge bg-primary">الوحدة الرئيسية</span>
                                        @endif
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>التاريخ</th>
                                                    <th>نوع السعر</th>
                                                    <th>السعر القديم</th>
                                                    <th>السعر الجديد</th>
                                                    <th>نسبة التغير</th>
                                                    <th>نوع التغير</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($productUnit->priceHistory()->orderBy('created_at', 'desc')->get() as $history)
                                                <tr>
                                                    <td>{{ $history->created_at->format('Y-m-d H:i') }}</td>
                                                    <td>
                                                        @switch($history->price_type)
                                                            @case('main_price')
                                                                السعر الرئيسي
                                                                @break
                                                            @case('app_price')
                                                                سعر التطبيق
                                                                @break
                                                            @case('other_price')
                                                                سعر آخر
                                                                @break
                                                        @endswitch
                                                    </td>
                                                    <td>{{ number_format($history->old_price, 2) }}</td>
                                                    <td>{{ number_format($history->new_price, 2) }}</td>
                                                    <td>
                                                        <span class="badge {{ $history->change_type == 'increase' ? 'bg-danger' : 'bg-success' }}">
                                                            {{ number_format($history->change_percentage, 2) }}%
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($history->change_type == 'increase')
                                                        <span class="text-danger">
                                                            <i class="fas fa-arrow-up me-1"></i>
                                                            زيادة
                                                        </span>
                                                        @else
                                                        <span class="text-success">
                                                            <i class="fas fa-arrow-down me-1"></i>
                                                            نقصان
                                                        </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">
                                                        لا يوجد تاريخ للأسعار
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- الأسعار الحالية -->
                                    <div class="mt-3">
                                        <h6>الأسعار الحالية:</h6>
                                        <ul class="list-unstyled">
                                            <li>السعر الرئيسي: {{ number_format($productUnit->main_price, 2) }}</li>
                                            @if($productUnit->app_price)
                                            <li>سعر التطبيق: {{ number_format($productUnit->app_price, 2) }}</li>
                                            @endif
                                            @if($productUnit->other_price)
                                            <li>سعر آخر: {{ number_format($productUnit->other_price, 2) }}</li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 