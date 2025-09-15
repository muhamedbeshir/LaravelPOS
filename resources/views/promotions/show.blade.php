@extends('layouts.app')

@section('title', 'تفاصيل العرض الترويجي')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">تفاصيل العرض الترويجي: {{ $promotion->name }}</h3>
                    <div>
                        <a href="{{ route('promotions.edit', $promotion->id) }}" class="btn btn-info">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        <a href="{{ route('promotions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @include('partials.flash_messages')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <h5 class="info-box-text">معلومات أساسية</h5>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">الاسم</th>
                                            <td>{{ $promotion->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>نوع العرض</th>
                                            <td>
                                                @switch($promotion->promotion_type)
                                                    @case('simple_discount')
                                                        خصم بسيط
                                                        @break
                                                    @case('buy_x_get_y')
                                                        اشتر X واحصل على Y
                                                        @break
                                                    @case('spend_x_save_y')
                                                        أنفق X ووفر Y
                                                        @break
                                                    @case('coupon_code')
                                                        كوبون خصم
                                                        @break
                                                    @default
                                                        {{ $promotion->promotion_type }}
                                                @endswitch
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>ينطبق على</th>
                                            <td>
                                                @switch($promotion->applies_to)
                                                    @case('product')
                                                        منتج محدد
                                                        @break
                                                    @case('category')
                                                        تصنيف
                                                        @break
                                                    @case('all')
                                                        جميع المنتجات
                                                        @break
                                                    @default
                                                        {{ $promotion->applies_to }}
                                                @endswitch
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>قيمة الخصم</th>
                                            <td>
                                                @if($promotion->discount_value)
                                                    {{ $promotion->discount_value }}
                                                    @if($promotion->promotion_type == 'simple_discount')
                                                        %
                                                    @else
                                                        ريال
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>الحد الأدنى للشراء</th>
                                            <td>{{ $promotion->minimum_purchase ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>الحد الأقصى للخصم</th>
                                            <td>{{ $promotion->maximum_discount ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <h5 class="info-box-text">معلومات إضافية</h5>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">تاريخ البدء</th>
                                            <td>{{ $promotion->start_date ? $promotion->start_date->format('Y-m-d') : 'غير محدد' }}</td>
                                        </tr>
                                        <tr>
                                            <th>تاريخ الانتهاء</th>
                                            <td>{{ $promotion->end_date ? $promotion->end_date->format('Y-m-d') : 'غير محدد' }}</td>
                                        </tr>
                                        <tr>
                                            <th>حد الاستخدام</th>
                                            <td>{{ $promotion->usage_limit ?? 'غير محدود' }}</td>
                                        </tr>
                                        <tr>
                                            <th>عدد مرات الاستخدام</th>
                                            <td>{{ $promotion->used_count }}</td>
                                        </tr>
                                        <tr>
                                            <th>الحالة</th>
                                            <td>
                                                <span class="badge badge-{{ $promotion->is_active ? 'success' : 'danger' }}">
                                                    {{ $promotion->is_active ? 'نشط' : 'غير نشط' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>تاريخ الإنشاء</th>
                                            <td>{{ $promotion->created_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">الوصف</h3>
                                </div>
                                <div class="card-body">
                                    {{ $promotion->description ?? 'لا يوجد وصف' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($promotion->applies_to == 'product' && $promotion->products->count() > 0)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">المنتجات المرتبطة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>اسم المنتج</th>
                                                    <th>الباركود</th>
                                                    <th>السعر</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($promotion->products as $product)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $product->name }}</td>
                                                    <td>{{ $product->barcode }}</td>
                                                    <td>{{ $product->price ?? '-' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($promotion->customers->count() > 0)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">العملاء المرتبطون</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>اسم العميل</th>
                                                    <th>رقم الهاتف</th>
                                                    <th>العنوان</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($promotion->customers as $customer)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $customer->name }}</td>
                                                    <td>{{ $customer->phone }}</td>
                                                    <td>{{ $customer->address ?? '-' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        $('.table').DataTable({
            "language": {
                "url": "{{ asset('js/datatables/i18n/ar.json') }}"
            },
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });
    });
</script>
@endsection 