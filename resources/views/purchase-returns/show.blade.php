@extends('layouts.app')

@section('title', 'تفاصيل مرتجع المشتريات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">تفاصيل مرتجع المشتريات: {{ $purchaseReturn->return_number }}</h3>
                    <div>
                        <a href="{{ route('purchase-returns.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> العودة للقائمة
                        </a>
                        <a href="{{ route('purchase-returns.pdf', $purchaseReturn->id) }}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-print"></i> طباعة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-3">معلومات المرتجع</h5>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th>رقم المرتجع</th>
                                    <td>{{ $purchaseReturn->return_number }}</td>
                                </tr>
                                <tr>
                                    <th>تاريخ المرتجع</th>
                                    <td>{{ $purchaseReturn->return_date->format('Y-m-d') }}</td>
                                </tr>
                                <tr>
                                    <th>نوع المرتجع</th>
                                    <td>
                                        @if($purchaseReturn->return_type == 'full')
                                            <span class="badge bg-danger">مرتجع كامل</span>
                                        @elseif($purchaseReturn->return_type == 'partial')
                                            <span class="badge bg-warning">مرتجع جزئي</span>
                                        @else
                                            <span class="badge bg-info">مرتجع مباشر</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>المبلغ الإجمالي</th>
                                    <td>{{ number_format($purchaseReturn->total_amount, 2) }}</td>
                                </tr>
                                @if($purchaseReturn->notes)
                                <tr>
                                    <th>ملاحظات</th>
                                    <td>{{ $purchaseReturn->notes }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">معلومات المورد والموظف</h5>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th>المورد</th>
                                    <td>
                                        @if($purchaseReturn->supplier)
                                            <a href="{{ route('suppliers.show', $purchaseReturn->supplier_id) }}">
                                                {{ $purchaseReturn->supplier->name }}
                                            </a>
                                        @else
                                            غير محدد
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>الموظف المستلم</th>
                                    <td>
                                        @if($purchaseReturn->employee)
                                            <a href="{{ route('employees.show', $purchaseReturn->employee_id) }}">
                                                {{ $purchaseReturn->employee->name }}
                                            </a>
                                        @else
                                            غير محدد
                                        @endif
                                    </td>
                                </tr>
                                @if($purchaseReturn->purchase)
                                <tr>
                                    <th>فاتورة المشتريات الأصلية</th>
                                    <td>
                                        <a href="{{ route('purchases.show', $purchaseReturn->purchase_id) }}">
                                            {{ $purchaseReturn->purchase->invoice_number }}
                                        </a>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <h5 class="mt-4">الأصناف المرتجعة</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المنتج</th>
                                    <th>الوحدة</th>
                                    <th>الكمية</th>
                                    <th>سعر الشراء</th>
                                    <th>الإجمالي</th>
                                    <th>السبب</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseReturn->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product->name ?? 'غير محدد' }}</td>
                                    <td>{{ $item->unit->name ?? 'غير محدد' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->purchase_price, 2) }}</td>
                                    <td>{{ number_format($item->quantity * $item->purchase_price, 2) }}</td>
                                    <td>{{ $item->reason ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد أصناف</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">المجموع</th>
                                    <th>{{ number_format($purchaseReturn->total_amount, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <h5 class="mt-4">حركات المخزون</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>الوحدة</th>
                                    <th>الكمية</th>
                                    <th>نوع الحركة</th>
                                    <th>ملاحظات</th>
                                    <th>تاريخ الحركة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseReturn->stockMovements as $movement)
                                <tr>
                                    <td>{{ $movement->product->name ?? 'غير محدد' }}</td>
                                    <td>{{ $movement->unit->name ?? 'غير محدد' }}</td>
                                    <td>{{ $movement->quantity }}</td>
                                    <td>
                                        @if($movement->movement_type == 'in')
                                            <span class="badge bg-success">إضافة</span>
                                        @else
                                            <span class="badge bg-danger">خصم</span>
                                        @endif
                                    </td>
                                    <td>{{ $movement->notes }}</td>
                                    <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">لا توجد حركات مخزون</td>
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