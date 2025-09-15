@extends('layouts.app')

@section('title', 'سجل المنتج: ' . $product->name)

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">سجل المنتج: {{ $product->name }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الحدث</th>
                            <th>الكمية</th>
                            <th>المرجع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $log->event }}</td>
                                <td>{{ $log->quantity }}</td>
                                <td>{{ $log->reference }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">لا يوجد سجل لهذا المنتج</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> العودة إلى قائمة المنتجات</a>
        </div>
    </div>
</div>
@endsection 