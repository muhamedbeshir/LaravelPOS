@extends('layouts.app')

@section('title', 'إدارة العروض الترويجية')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">العروض الترويجية</h3>
                    <a href="{{ route('promotions.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إضافة عرض جديد
                    </a>
                </div>
                <div class="card-body">
                    @include('partials.flash_messages')
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th>نوع العرض</th>
                                    <th>ينطبق على</th>
                                    <th>قيمة الخصم</th>
                                    <th>تاريخ البدء</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($promotions as $promotion)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $promotion->name }}</td>
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
                                    <td>{{ $promotion->start_date ? $promotion->start_date->format('Y-m-d') : 'غير محدد' }}</td>
                                    <td>{{ $promotion->end_date ? $promotion->end_date->format('Y-m-d') : 'غير محدد' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $promotion->is_active ? 'success' : 'danger' }}">
                                            {{ $promotion->is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('promotions.edit', $promotion->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('promotions.show', $promotion->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('promotions.destroy', $promotion->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العرض؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('promotions.toggle-active', $promotion->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('POST')
                                                <button type="submit" class="btn btn-sm btn-{{ $promotion->is_active ? 'warning' : 'success' }}">
                                                    <i class="fas fa-{{ $promotion->is_active ? 'ban' : 'check' }}"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">لا توجد عروض ترويجية</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
            "order": [[ 0, "desc" ]],
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