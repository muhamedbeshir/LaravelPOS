<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الوردية {{ $shift->shift_number }}</title>
    <!-- Cairo Font (local) -->
    <style>
        @font-face {
            font-family: 'Cairo';
            src: url('/fonts/cairo/Cairo-Regular.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Cairo';
            src: url('/fonts/cairo/Cairo-Bold.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'Cairo';
            src: url('/fonts/cairo/Cairo-Black.ttf') format('truetype');
            font-weight: 900;
            font-style: normal;
        }
        * {
            box-sizing: border-box;
        }
        html, body, * {
            font-family: 'Cairo', sans-serif !important;
            font-weight: bold !important;
            color: black !important;
        }
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
                orphans: 0;
                widows: 0;
            }
            body {
                width: 70mm;
                margin: 0 auto !important;
                padding: 1mm 2mm !important;
            }
            .no-break {
                page-break-inside: avoid;
            }
            .page-footer {
                page-break-after: always;
            }
            html, body {
                height: auto !important;
                overflow: hidden !important;
            }
            table, th, td {
                border-color: black !important;
            }
            .print-btn {
                display: none;
            }
        }
        body {
            margin: 0 auto;
            padding: 1mm 2mm;
            font-size: 10px;
            line-height: 1.3;
            max-width: 70mm;
            background-color: white;
        }
        .main-container {
            width: 100%;
            display: flex;
            flex-direction: column;
        }
        .section {
            margin-bottom: 2mm;
            width: 100%;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 2mm;
        }
        .invoice-header img {
            max-width: 100%;
            height: auto;
            max-height: 15mm;
            margin: 0 auto 1mm;
            display: block;
        }
        .store-name {
            font-size: 14px;
            margin: 1mm 0;
        }
        .invoice-title {
            font-size: 13px;
            margin: 2mm 0;
            border-bottom: 1px solid #000;
            border-top: 1px solid #000;
            padding: 1mm 0;
            text-align: center;
        }
        .invoice-number-box {
            border: 1.5px solid #888;
            padding: 2mm;
            text-align: center;
            margin: 0 auto 2mm;
            background-color: #444 !important;
            border-radius: 2px;
            color: #fff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .invoice-number-value {
            font-size: 18px;
            color: #fff !important;
        }
        .meta-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            font-size: 9px;
            margin-bottom: 2mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 1mm;
        }
        .meta-item {
            margin-bottom: 1mm;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
            font-size: 9px;
            table-layout: fixed;
        }
        .items-table th {
            background-color: #e0e0e0;
            border: 1.5px solid black;
            padding: 1mm;
            text-align: center;
            white-space: nowrap;
        }
        .items-table td {
            border: 1px solid black;
            padding: 1mm;
            text-align: center;
        }
        .items-table td:first-child {
            text-align: right;
        }
        .items-table td:last-child {
            text-align: left;
            direction: ltr;
        }
        .totals-section {
            margin-bottom: 2mm;
        }
        .totals-header {
            font-size: 11px;
            text-align: center;
            margin-bottom: 1mm;
            background-color: #f0f0f0;
            padding: 1mm 0;
            border: 1px solid #000;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            border: 1.5px solid #000;
        }
        .totals-table td {
            padding: 1.5mm;
            border: 1px solid #000;
        }
        .totals-table td:first-child {
            width: 60%;
        }
        .totals-table td:last-child {
            text-align: left;
            direction: ltr;
            width: 40%;
        }
        .row-highlight {
            background-color: #f0f0f0;
        }
        .row-total {
            font-size: 12px;
            background-color: #e8e8e8;
        }
        .row-total td {
            border-top: 2px solid black;
            border-bottom: 2px solid black;
        }
        .store-info-container {
            text-align: center;
            margin-top: 3mm;
            padding-top: 1mm;
            border-top: 1px dashed #000;
        }
        .store-info {
            font-size: 9px;
            margin-bottom: 1mm;
        }
        .footer-text {
            text-align: center;
            font-size: 10px;
            margin: 2mm 0;
        }
        .footer-logo {
            max-width: 100%;
            height: auto;
            max-height: 10mm;
            display: block;
            margin: 1mm auto;
        }
        .print-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            z-index: 100;
        }
        .difference-positive {
            color: #10b981 !important;
        }
        .difference-negative {
            color: #ef4444 !important;
        }
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 10px;
            color: white !important;
            margin-bottom: 1mm;
        }
        .status-open {
            background-color: #10b981;
        }
        .status-closed {
            background-color: #ef4444;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">طباعة التقرير</button>
    
    <div class="main-container">
        <!-- Header Logo and Info -->
        <div class="invoice-header section">
            @if(\App\Models\Setting::get('show_header_logo', true) && \App\Models\Setting::get('header_logo'))
                <img src="{{ asset('storage/' . \App\Models\Setting::get('header_logo')) }}" alt="شعار المحل" onerror="this.style.display='none'">
            @endif
            
            @if(\App\Models\Setting::get('header_text_below_logo'))
                <div class="store-name">{{ \App\Models\Setting::get('header_text_below_logo') }}</div>
            @endif
            
            @if(\App\Models\Setting::get('show_store_info', true) && !\App\Models\Setting::get('store_info_at_bottom', true) && \App\Models\Setting::get('print_store_name'))
                <div class="store-name">{{ \App\Models\Setting::get('print_store_name') }}</div>
            @endif

            <!-- Receipt Header Text -->
            @if(\App\Models\Setting::get('receipt_header'))
                <div style="font-size: 10px; margin: 1mm 0;">
                    {{ \App\Models\Setting::get('receipt_header') }}
                </div>
            @endif
        </div>
        
        <!-- Shift Title -->
        <div class="invoice-title">
            تقرير الوردية
            <div style="text-align: center; margin-top: 1mm;">
                <span class="status {{ $shift->is_closed ? 'status-closed' : 'status-open' }}">
                    {{ $shift->is_closed ? 'مغلقة' : 'مفتوحة' }}
                </span>
            </div>
        </div>
        
        <!-- Shift Number -->
        <div class="section">
            <div class="invoice-number-box">
                <div class="invoice-number-value">
                    {{ $shift->shift_number }}
                </div>
            </div>
        </div>
        
        <!-- Meta Information -->
        <div class="meta-info section">
            <div class="meta-item"><strong>الكاشير:</strong> {{ $shift->mainCashier->name }}</div>
            <div class="meta-item"><strong>البدء:</strong> {{ $shift->start_time->format('Y/m/d H:i') }}</div>
            @if($shift->end_time)
                <div class="meta-item"><strong>الإغلاق:</strong> {{ $shift->end_time->format('Y/m/d H:i') }}</div>
            @endif
        </div>
        
        <!-- Financial Summary -->
        <div class="totals-section section">
            <div class="totals-header">الملخص المالي</div>
            <table class="totals-table no-break">
                <tr>
                    <td>رصيد الإفتتاح:</td>
                    <td>{{ number_format($shift->opening_balance, 2) }}</td>
                </tr>
                <tr>
                    <td>إجمالي المبيعات:</td>
                    <td>{{ number_format($shift->total_sales ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>إجمالي الأرباح:</td>
                    <td>{{ number_format($shift->total_profit ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>إجمالي الإيداعات:</td>
                    <td>{{ number_format($shift->total_deposits ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>إجمالي المرتجعات:</td>
                    <td>{{ number_format($shift->returns_amount ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>إجمالي المسحوبات:</td>
                    <td>{{ number_format($shift->total_withdrawals ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>إجمالي المصروفات:</td>
                    <td>{{ number_format($shift->total_expenses ?? 0, 2) }}</td>
                </tr>
                <tr class="row-total">
                    <td>الرصيد المتوقع في الدرج:</td>
                    <td>{{ number_format($shift->expected_closing_balance ?? 0, 2) }}</td>
                </tr>
                @if($shift->is_closed)
                    <tr>
                        <td>الرصيد الفعلي:</td>
                        <td>{{ number_format($shift->actual_closing_balance, 2) }}</td>
                    </tr>
                    <tr class="row-highlight">
                        <td>الفرق (العجز/الزيادة):</td>
                        <td class="{{ $shift->cash_shortage_excess == 0 ? '' : ($shift->cash_shortage_excess > 0 ? 'difference-positive' : 'difference-negative') }}">
                            {{ number_format($shift->cash_shortage_excess, 2) }}
                            @if($shift->cash_shortage_excess != 0)
                                ({{ $shift->cash_shortage_excess > 0 ? 'زيادة' : 'عجز' }})
                            @endif
                        </td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Sales by Payment Method -->
        <div class="totals-section section">
            <div class="totals-header">ملخص المبيعات حسب طريقة الدفع</div>
            <table class="totals-table no-break">
                @foreach($paymentMethodsWithCounts as $method)
                    <tr>
                        <td>
                            @if($method->payment_method == 'cash') نقداً @elseif($method->payment_method == 'visa') فيزا @elseif($method->payment_method == 'transfer') تحويلات مالية @elseif($method->payment_method == 'credit') آجل @elseif($method->payment_method == 'multiple_payment') دفع متعدد @else {{ $method->payment_method }} @endif
                            <small>({{ $method->invoice_count }} فاتورة)</small>
                        </td>
                        <td>{{ number_format($method->total_amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="row-total">
                    <td>إجمالي المبيعات: <small>({{ $paymentMethodsWithCounts->sum('invoice_count') }} فاتورة)</small></td>
                    <td>{{ number_format($totalSales, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Sales by Order/Invoice Type -->
        <div class="totals-section section">
            <div class="totals-header">ملخص المبيعات حسب النوع</div>
             <table class="totals-table no-break">
                @php
                    $cashInvoiceTotal = ($salesByPaymentMethod['cash'] ?? 0) + ($salesByPaymentMethod['visa'] ?? 0) + ($salesByPaymentMethod['transfer'] ?? 0);
                    $creditInvoiceTotal = $salesByPaymentMethod['credit'] ?? 0;
                    $mixedInvoiceTotal = $salesByPaymentMethod['multiple_payment'] ?? 0;
                    
                    $cashInvoiceCount = 0;
                    $creditInvoiceCount = 0;
                    
                    foreach($paymentMethodsWithCounts as $method) {
                        if($method->payment_method == 'credit') {
                            $creditInvoiceCount = $method->invoice_count;
                        } elseif($method->payment_method != 'multiple_payment') {
                            $cashInvoiceCount += $method->invoice_count;
                        }
                    }
                    
                    $takeawayCount = DB::table('invoices')->where('shift_id', $shift->id)->where('order_type', 'takeaway')->whereIn('status', ['paid', 'completed'])->count();
                    $deliveryCount = DB::table('invoices')->where('shift_id', $shift->id)->where('order_type', 'delivery')->whereIn('status', ['paid', 'completed'])->count();
                @endphp
                <tr>
                    <td colspan="2" style="background-color: #f0f0f0; text-align:center;">حسب نوع الفاتورة</td>
                </tr>
                <tr>
                    <td>كاش: <small>({{ $cashInvoiceCount }} فاتورة)</small></td>
                    <td>{{ number_format($cashInvoiceTotal, 2) }}</td>
                </tr>
                <tr>
                    <td>آجل: <small>({{ $creditInvoiceCount }} فاتورة)</small></td>
                    <td>{{ number_format($creditInvoiceTotal, 2) }}</td>
                </tr>
                @if($mixedInvoiceTotal > 0)
                <tr>
                    <td>دفع متعدد: <small>({{ $mixedSalesCount ?? 0 }} فاتورة)</small></td>
                    <td>{{ number_format($mixedInvoiceTotal, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="2" style="background-color: #f0f0f0; text-align:center;">حسب نوع الطلب</td>
                </tr>
                <tr>
                    <td>تيك أواي: <small>({{ $takeawayCount }} فاتورة)</small></td>
                    <td>{{ number_format($salesByOrderType['takeaway'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>دليفري: <small>({{ $deliveryCount }} فاتورة)</small></td>
                    <td>{{ number_format($salesByOrderType['delivery'] ?? 0, 2) }}</td>
                </tr>
                <tr class="row-total">
                    <td>إجمالي المبيعات: <small>({{ $paymentMethodsWithCounts->sum('invoice_count') }} فاتورة)</small></td>
                    <td>{{ number_format($totalSales, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Shift Employees -->
        @if($shift->users && $shift->users->count() > 1)
        <div class="section">
            <div class="totals-header">الموظفين في الوردية</div>
            <table class="items-table no-break">
                <thead>
                    <tr>
                        <th width="40%">الاسم</th>
                        <th width="30%">الانضمام</th>
                        <th width="30%">المغادرة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shift->users as $user)
                    <tr>
                        <td style="text-align:right;">
                            {{ $user->name }}
                            @if($user->id == $shift->main_cashier_id)
                                <small>(رئيسي)</small>
                            @endif
                        </td>
                        <td>{{ $user->pivot->join_time ? \Carbon\Carbon::parse($user->pivot->join_time)->format('H:i') : '-' }}</td>
                        <td>{{ $user->pivot->leave_time ? \Carbon\Carbon::parse($user->pivot->leave_time)->format('H:i') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        
        <!-- Withdrawals -->
        @if($shift->withdrawals && $shift->withdrawals->isNotEmpty())
        <div class="section">
            <div class="totals-header">عمليات السحب من الدرج</div>
            <table class="items-table no-break">
                <thead>
                    <tr>
                        <th width="50%">السبب</th>
                        <th width="25%">الموظف</th>
                        <th width="25%">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shift->withdrawals as $withdrawal)
                    <tr>
                        <td style="text-align:right;">{{ $withdrawal->reason }}</td>
                        <td>{{ $withdrawal->user->name ?? 'غير محدد' }}</td>
                        <td style="text-align:left; direction:ltr;">{{ number_format($withdrawal->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        
        <!-- Sold Products -->
        @if(isset($soldProducts) && $soldProducts->count() > 0 && isset($withProducts) && $withProducts)
        <div class="section">
            <div class="totals-header">الأصناف المباعة</div>
            <table class="items-table no-break">
                <thead>
                    <tr>
                        <th width="35%">المنتج</th>
                        <th width="15%">الوحدة</th>
                        <th width="15%">الكمية</th>
                        <th width="17%">المبيعات</th>
                        <th width="18%">الربح</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($soldProducts as $product)
                    <tr>
                        <td style="text-align:right;">{{ $product['product_name'] }}</td>
                        <td>{{ $product['unit_name'] }}</td>
                        <td>{{ number_format($product['quantity'], 2) }}</td>
                        <td style="text-align:left; direction:ltr;">{{ number_format($product['total_price'], 2) }}</td>
                        <td style="text-align:left; direction:ltr;">{{ number_format($product['total_profit'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #f0f0f0;">
                        <td colspan="3" style="text-align: center;">الإجمالي</td>
                        <td style="text-align:left; direction:ltr;">{{ number_format($soldProducts->sum('total_price'), 2) }}</td>
                        <td style="text-align:left; direction:ltr;">{{ number_format($soldProducts->sum('total_profit'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
        
        <!-- Notes -->
        @if($shift->notes || $shift->closing_notes)
        <div class="section">
            <div class="totals-header">الملاحظات</div>
            <div style="border: 1px solid black; padding: 2mm; font-size:9px;">
                @if($shift->notes)
                <div><strong>ملاحظات البدء:</strong> {{ $shift->notes }}</div>
                @endif
                @if($shift->closing_notes)
                <div><strong>ملاحظات الإغلاق:</strong> {{ $shift->closing_notes }}</div>
                @endif
            </div>
        </div>
        @endif
        
        <!-- Footer Area -->
        <div class="store-info-container">
            <!-- Receipt Footer -->
            @if(\App\Models\Setting::get('receipt_footer'))
                <div class="footer-text">
                    {{ \App\Models\Setting::get('receipt_footer') }}
                </div>
            @endif
            
            <!-- Footer Text Above Logo -->
            @if(\App\Models\Setting::get('footer_text_above_logo'))
                <div class="footer-text">
                    {{ \App\Models\Setting::get('footer_text_above_logo') }}
                </div>
            @endif
            
            <!-- Store Info at Bottom -->
            @if(\App\Models\Setting::get('show_store_info', true) && \App\Models\Setting::get('store_info_at_bottom', true))
                @if(\App\Models\Setting::get('print_store_name'))
                    <div class="store-info">
                        {{ \App\Models\Setting::get('print_store_name') }}
                    </div>
                @endif
                
                @if(\App\Models\Setting::get('print_store_address'))
                    <div class="store-info">
                        {{ \App\Models\Setting::get('print_store_address') }}
                    </div>
                @endif
                
                @if(\App\Models\Setting::get('print_store_phone'))
                    <div class="store-info">
                        {{ \App\Models\Setting::get('print_store_phone') }}
                    </div>
                @endif
            @endif
            
            <!-- Footer Logo -->
            @if(\App\Models\Setting::get('show_footer_logo', true) && \App\Models\Setting::get('footer_logo'))
                <img src="{{ asset('storage/' . \App\Models\Setting::get('footer_logo')) }}" alt="شعار المحل السفلي" class="footer-logo" onerror="this.style.display='none'">
            @endif

            <!-- Default Footer -->
            <div class="footer-text page-footer">
                تم إنشاء هذا التقرير بتاريخ {{ date('Y-m-d H:i') }}<br>
                @if(!\App\Models\Setting::get('receipt_footer') && !\App\Models\Setting::get('footer_text_above_logo'))
                    شكراً لكم
                @endif
            </div>
        </div>
    </div>

    <script>
        const isElectron = navigator.userAgent.includes('Electron');
        if (!isElectron) {
            window.onload = function() {
                setTimeout(() => {
                    window.print();
                }, 300);
            }

            window.onafterprint = function() {
                // You can add a command to close the window here if needed, e.g., window.close();
                // but this is often blocked by browsers.
            }
        }
    </script>
</body>
</html> 