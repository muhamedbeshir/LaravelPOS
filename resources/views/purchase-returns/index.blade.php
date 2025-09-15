@extends('layouts.app')

@section('title', 'مرتجع المشتريات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">مرتجع المشتريات</h3>
                    <div>
                        <a href="{{ route('purchase-returns.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إنشاء مرتجع جديد
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <div class="input-group">
                                    <input type="text" id="search-input" class="form-control" placeholder="بحث...">
                                    <button class="btn btn-primary" id="search-btn" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select id="supplier-filter" class="form-control">
                                    <option value="">كل الموردين</option>
                                    @foreach($suppliers ?? [] as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="input-group">
                                    <input type="date" id="start-date" class="form-control" placeholder="من تاريخ">
                                    <span class="input-group-text">إلى</span>
                                    <input type="date" id="end-date" class="form-control" placeholder="إلى تاريخ">
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select id="return-type-filter" class="form-control">
                                    <option value="">كل الأنواع</option>
                                    <option value="full">مرتجع كامل</option>
                                    <option value="partial">مرتجع جزئي</option>
                                    <option value="direct">مرتجع مباشر</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="purchase-returns-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>رقم المرتجع</th>
                                    <th>المورد</th>
                                    <th>فاتورة المشتريات</th>
                                    <th>تاريخ المرتجع</th>
                                    <th>المبلغ الإجمالي</th>
                                    <th>نوع المرتجع</th>
                                    <th>ملاحظات</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseReturns as $return)
                                <tr>
                                    <td>{{ $return->id }}</td>
                                    <td>{{ $return->return_number }}</td>
                                    <td>{{ $return->supplier->name ?? 'غير محدد' }}</td>
                                    <td>
                                        @if($return->purchase)
                                            <a href="{{ route('purchases.show', $return->purchase_id) }}">
                                                {{ $return->purchase->invoice_number }}
                                            </a>
                                        @else
                                            <span class="badge bg-warning">مرتجع مباشر</span>
                                        @endif
                                    </td>
                                    <td>{{ $return->return_date->format('Y-m-d') }}</td>
                                    <td class="text-primary">{{ number_format($return->total_amount, 2) }}</td>
                                    <td>
                                        @if($return->return_type == 'full')
                                            <span class="badge bg-danger">مرتجع كامل</span>
                                        @elseif($return->return_type == 'partial')
                                            <span class="badge bg-warning">مرتجع جزئي</span>
                                        @else
                                            <span class="badge bg-info">مرتجع مباشر</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($return->notes)
                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $return->notes }}">
                                                {{ \Illuminate\Support\Str::limit($return->notes, 20) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('purchase-returns.show', $return->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('purchase-returns.pdf', $return->id) }}" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">لا توجد مرتجعات مشتريات</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        {{ $purchaseReturns->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Handle search button click
        $('#search-btn').click(function() {
            applyFilters();
        });
        
        // Handle filter changes
        $('#supplier-filter, #return-type-filter').change(function() {
            applyFilters();
        });
        
        // Handle date filter changes
        $('#start-date, #end-date').change(function() {
            // Only filter if both dates are set or both are empty
            if (($('#start-date').val() && $('#end-date').val()) || (!$('#start-date').val() && !$('#end-date').val())) {
                applyFilters();
            }
        });
        
        // Search on pressing Enter
        $('#search-input').keypress(function(e) {
            if(e.which == 13) {
                applyFilters();
            }
        });
        
        // Function to apply all filters
        function applyFilters() {
            let url = '{{ route("purchase-returns.index") }}';
            let params = [];
            
            // Search term
            const search = $('#search-input').val().trim();
            if (search) {
                params.push('search=' + encodeURIComponent(search));
            }
            
            // Supplier filter
            const supplierId = $('#supplier-filter').val();
            if (supplierId) {
                params.push('supplier_id=' + supplierId);
            }
            
            // Date range
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();
            if (startDate && endDate) {
                params.push('start_date=' + startDate);
                params.push('end_date=' + endDate);
            }
            
            // Return type
            const returnType = $('#return-type-filter').val();
            if (returnType) {
                params.push('return_type=' + returnType);
            }
            
            // Build final URL and navigate
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            window.location.href = url;
        }
    });
</script>
@endpush 