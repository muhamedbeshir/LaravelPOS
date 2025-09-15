@extends('layouts.app')

@section('title', 'تفاصيل معاملة الدليفري')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">تفاصيل معاملة الدليفري #{{ $transaction->id }}</h3>
                    <div>
                        <a href="{{ route('delivery-transactions.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-right ml-1"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h5 class="card-title">معلومات المعاملة</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <tr>
                                            <th>رقم الفاتورة</th>
                                            <td>{{ $transaction->invoice->invoice_number }}</td>
                                        </tr>
                                        <tr>
                                            <th>العميل</th>
                                            <td>{{ $transaction->customer->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>موظف التوصيل</th>
                                            <td>{{ $transaction->employee->name }}</td>
                                        </tr>
                                        @if($transaction->shipping_company)
                                        <tr>
                                            <th>شركة الشحن</th>
                                            <td>
                                                <a href="{{ route('shipping-companies.show', $transaction->shipping_company_id) }}">
                                                    {{ $transaction->shippingCompany->name }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>حالة الشحن</th>
                                            <td>
                                                @if($transaction->shippingStatus)
                                                <span class="badge" style="background-color: {{ $transaction->shippingStatus->color }}">
                                                    {{ $transaction->shippingStatus->name }}
                                                </span>
                                                @else
                                                <span class="text-muted">غير محددة</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>تكلفة الشحن</th>
                                            <td>{{ number_format($transaction->shipping_cost, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>رقم التتبع</th>
                                            <td>{{ $transaction->tracking_number ?? 'غير متوفر' }}</td>
                                        </tr>
                                        <tr>
                                            <th>تاريخ الشحن</th>
                                            <td>
                                                @if($transaction->shipped_at)
                                                    {{ $transaction->shipped_at->format('Y-m-d H:i:s') }}
                                                @else
                                                    <span class="text-muted">لم يتم الشحن بعد</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>تاريخ التسليم المتوقع</th>
                                            <td>
                                                @if($transaction->estimated_delivery_date)
                                                    {{ $transaction->estimated_delivery_date->format('Y-m-d') }}
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th>المبلغ</th>
                                            <td>{{ number_format($transaction->amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>المبلغ المحصل</th>
                                            <td>{{ number_format($transaction->collected_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>المبلغ المتبقي</th>
                                            <td>{{ number_format($transaction->remaining_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>الحالة</th>
                                            <td>
                                                <span class="badge" style="background-color: {{ $transaction->status->color }}">
                                                    {{ $transaction->status->name }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>وقت التوصيل</th>
                                            <td>
                                                @if($transaction->delivery_date)
                                                    {{ $transaction->delivery_date->format('Y-m-d H:i:s') }}
                                                @else
                                                    <span class="text-muted">لم يتم التوصيل بعد</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>وقت الدفع</th>
                                            <td>
                                                @if($transaction->payment_date)
                                                    {{ $transaction->payment_date->format('Y-m-d H:i:s') }}
                                                @else
                                                    <span class="text-muted">لم يتم الدفع بعد</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>وقت الإرجاع</th>
                                            <td>
                                                @if($transaction->return_date)
                                                    {{ $transaction->return_date->format('Y-m-d H:i:s') }}
                                                @else
                                                    <span class="text-muted">لم يتم الإرجاع</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>ملاحظات</th>
                                            <td>{{ $transaction->notes ?? 'لا توجد ملاحظات' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h5 class="card-title">تفاصيل الفاتورة</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <tr>
                                            <th>رقم الفاتورة</th>
                                            <td>{{ $transaction->invoice->invoice_number }}</td>
                                        </tr>
                                        <tr>
                                            <th>نوع الفاتورة</th>
                                            <td>
                                                @if($transaction->invoice->type == 'cash')
                                                    <span class="badge badge-success">كاش</span>
                                                @else
                                                    <span class="badge badge-warning">آجل</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>الإجمالي</th>
                                            <td>{{ number_format($transaction->invoice->total, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>حالة الدفع</th>
                                            <td>
                                                @if($transaction->invoice->payment_status == 'paid')
                                                    <span class="badge badge-success">مدفوع</span>
                                                @elseif($transaction->invoice->payment_status == 'partially_paid')
                                                    <span class="badge badge-warning">مدفوع جزئيًا</span>
                                                @else
                                                    <span class="badge badge-danger">غير مدفوع</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>حالة الفاتورة</th>
                                            <td>
                                                @if($transaction->invoice->status == 'completed')
                                                    <span class="badge badge-success">مكتملة</span>
                                                @elseif($transaction->invoice->status == 'pending')
                                                    <span class="badge badge-warning">قيد الانتظار</span>
                                                @else
                                                    <span class="badge badge-danger">ملغاة</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <h6 class="mt-4">المنتجات</h6>
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>المنتج</th>
                                                <th>الكمية</th>
                                                <th>السعر</th>
                                                <th>الإجمالي</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($transaction->invoice->items as $item)
                                                <tr>
                                                    <td>{{ $item->product->name }}</td>
                                                    <td>{{ $item->quantity }} {{ $item->unit->name ?? '' }}</td>
                                                    <td>{{ number_format($item->unit_price, 2) }}</td>
                                                    <td>{{ number_format($item->total_price, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <h5 class="card-title">الإجراءات</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- تحديث حالة التوصيل -->
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="card-title">تحديث حالة التوصيل</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form action="{{ route('delivery-transactions.update-status', $transaction) }}" method="POST">
                                                        @csrf
                                                        <div class="form-group">
                                                            <label for="status">الحالة</label>
                                                            <select name="status" id="status" class="form-control">
                                                                @foreach(\App\Models\DeliveryStatus::active()->ordered()->get() as $status)
                                                                    <option value="{{ $status->code }}" {{ $transaction->status_id == $status->id ? 'selected' : '' }}>
                                                                        {{ $status->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary">تحديث الحالة</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- تحديث وقت التوصيل -->
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="card-title">تحديث وقت التوصيل</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form action="{{ route('delivery-transactions.update-delivery-time', $transaction) }}" method="POST">
                                                        @csrf
                                                        <div class="form-group">
                                                            <label for="delivery_time">وقت التوصيل</label>
                                                            <input type="datetime-local" name="delivery_time" id="delivery_time" class="form-control" 
                                                                value="{{ $transaction->delivery_date ? $transaction->delivery_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}" required>
                                                        </div>
                                                        <button type="submit" class="btn btn-info">تحديث وقت التوصيل</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- إضافة دفعة -->
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="card-title">إضافة دفعة</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form action="{{ route('delivery-transactions.add-payment', $transaction) }}" method="POST">
                                                        @csrf
                                                        <div class="form-group">
                                                            <label for="amount">المبلغ</label>
                                                            <input type="number" step="0.01" name="amount" id="amount" class="form-control" 
                                                                max="{{ $transaction->remaining_amount }}" required>
                                                            <small class="text-muted">المبلغ المتبقي: {{ number_format($transaction->remaining_amount, 2) }}</small>
                                                        </div>
                                                        <button type="submit" class="btn btn-success" {{ $transaction->is_paid || $transaction->remaining_amount <= 0 ? 'disabled' : '' }}>
                                                            إضافة دفعة
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- تحديث معلومات الشحن -->
                                        <div class="col-md-4 mt-3">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="card-title">معلومات الشحن</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form action="{{ route('delivery-transactions.update', $transaction) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="form-group mb-3">
                                                            <label for="shipping_company_id">شركة الشحن</label>
                                                            <select name="shipping_company_id" id="shipping_company_id" class="form-control">
                                                                <option value="">-- اختر شركة الشحن --</option>
                                                                @foreach(\App\Models\ShippingCompany::active()->orderBy('name')->get() as $company)
                                                                    <option value="{{ $company->id }}" {{ $transaction->shipping_company_id == $company->id ? 'selected' : '' }}
                                                                        data-default-cost="{{ $company->default_cost }}">
                                                                        {{ $company->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="form-group mb-3">
                                                            <label for="shipping_cost">تكلفة الشحن</label>
                                                            <input type="number" step="0.01" name="shipping_cost" id="shipping_cost" class="form-control" 
                                                                value="{{ $transaction->shipping_cost }}">
                                                        </div>
                                                        
                                                        <div class="form-group mb-3">
                                                            <label for="tracking_number">رقم التتبع</label>
                                                            <input type="text" name="tracking_number" id="tracking_number" class="form-control" 
                                                                value="{{ $transaction->tracking_number }}">
                                                        </div>
                                                        
                                                        <div class="form-group mb-3">
                                                            <label for="shipped_at">تاريخ الشحن</label>
                                                            <input type="datetime-local" name="shipped_at" id="shipped_at" class="form-control" 
                                                                value="{{ $transaction->shipped_at ? $transaction->shipped_at->format('Y-m-d\TH:i') : '' }}">
                                                        </div>
                                                        
                                                        <div class="form-group mb-3">
                                                            <label for="estimated_delivery_date">تاريخ التسليم المتوقع</label>
                                                            <input type="date" name="estimated_delivery_date" id="estimated_delivery_date" class="form-control" 
                                                                value="{{ $transaction->estimated_delivery_date ? $transaction->estimated_delivery_date->format('Y-m-d') : '' }}">
                                                        </div>
                                                        
                                                        <button type="submit" class="btn btn-primary">تحديث معلومات الشحن</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- تحديث حالة الشحن -->
                                        <div class="col-md-4 mt-3">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="card-title">حالة الشحن</h6>
                                                </div>
                                                <div class="card-body">
                                                    @if($transaction->shippingStatus)
                                                    <div class="alert" style="background-color: {{ $transaction->shippingStatus->color }}; color: #fff;">
                                                        <strong>الحالة الحالية:</strong> {{ $transaction->shippingStatus->name }}
                                                    </div>
                                                    @else
                                                    <div class="alert alert-secondary">
                                                        <strong>الحالة الحالية:</strong> غير محددة
                                                    </div>
                                                    @endif

                                                    <form action="{{ route('delivery-transactions.update-shipping-status', $transaction) }}" method="POST">
                                                        @csrf
                                                        <div class="form-group mb-3">
                                                            <label for="shipping_status">تحديث الحالة</label>
                                                            <select name="shipping_status" id="shipping_status" class="form-control">
                                                                <option value="">-- اختر حالة الشحن --</option>
                                                                @foreach(\App\Models\ShippingStatus::active()->ordered()->get() as $status)
                                                                    <option value="{{ $status->code }}" {{ $transaction->shipping_status_id == $status->id ? 'selected' : '' }}
                                                                        style="background-color: {{ $status->color }}20;">
                                                                        {{ $status->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        
                                                        <button type="submit" class="btn btn-success">تحديث حالة الشحن</button>
                                                    </form>

                                                    @if($transaction->shippingStatus)
                                                    <hr>
                                                    <h6>تاريخ الحالات</h6>
                                                    <div class="timeline small">
                                                        <div>
                                                            <i class="fas fa-circle text-primary"></i>
                                                            <div class="timeline-item">
                                                                <span class="time"><i class="fas fa-clock"></i> {{ $transaction->updated_at->format('Y-m-d H:i') }}</span>
                                                                <h3 class="timeline-header">{{ $transaction->shippingStatus->name }}</h3>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- إرجاع الطلبية -->
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <div class="card card-outline card-danger">
                                                <div class="card-header">
                                                    <h6 class="card-title">إرجاع الطلبية</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form action="{{ route('delivery-transactions.return', $transaction) }}" method="POST">
                                                        @csrf
                                                        <div class="form-group">
                                                            <label for="notes">ملاحظات الإرجاع</label>
                                                            <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                                                        </div>
                                                        <button type="submit" class="btn btn-danger" {{ $transaction->is_returned ? 'disabled' : '' }}
                                                            onclick="return confirm('هل أنت متأكد من إرجاع الطلبية؟ سيتم إلغاء الفاتورة وإعادة المنتجات للمخزن.')">
                                                            إرجاع الطلبية
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تعبئة تكلفة الشحن الافتراضية عند اختيار شركة الشحن
        const shippingCompanySelect = document.getElementById('shipping_company_id');
        const shippingCostInput = document.getElementById('shipping_cost');
        
        if (shippingCompanySelect && shippingCostInput) {
            shippingCompanySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    const defaultCost = selectedOption.getAttribute('data-default-cost');
                    if (defaultCost) {
                        shippingCostInput.value = defaultCost;
                    }
                } else {
                    shippingCostInput.value = '';
                }
            });
        }
    });
</script>
@endpush 