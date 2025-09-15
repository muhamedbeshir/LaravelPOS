@extends('layouts.app')

@section('title', 'تفاصيل المورد: ' . $supplier->name)

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">تفاصيل المورد: {{ $supplier->name }}</h1>
        <div>
            <a href="{{ route('supplier-payments.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-success shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> تسجيل دفعة
            </a>
            <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> تعديل المورد
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">إجمالي الفواتير</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($supplier->total_amount, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">إجمالي المدفوعات</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($supplier->paid_amount, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">الرصيد المستحق</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($supplier->remaining_amount, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale-right fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statement of Account -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">كشف حساب المورد</h6>
        </div>
        <div class="card-body">
            <!-- Date Filter Form -->
            <form method="GET" action="{{ route('suppliers.show', $supplier->id) }}" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">فلترة</button>
                        <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-secondary">إعادة تعيين</a>
                        <a href="{{ route('suppliers.statement.pdf', ['supplier' => $supplier->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                        <a href="{{ route('suppliers.statement.excel', ['supplier' => $supplier->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success"><i class="fas fa-file-excel"></i> Excel</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th>مدين (فاتورة)</th>
                            <th>دائن (دفعة)</th>
                            <th>الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statement as $transaction)
                        <tr>
                            <td>{{ $transaction['transaction_date'] }}</td>
                            <td>{{ $transaction['description'] }}</td>
                            <td>{{ $transaction['debit'] > 0 ? number_format($transaction['debit'], 2) : '-' }}</td>
                            <td>{{ $transaction['credit'] > 0 ? number_format($transaction['credit'], 2) : '-' }}</td>
                            <td>{{ number_format($transaction['balance'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">لا توجد معاملات لعرضها.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 