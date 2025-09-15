<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة #{{ $invoice->invoice_number }}</title>
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
        /* INVOICE NUMBER BOX */
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
        /* REFERENCE INFO */
        .reference-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1mm;
            font-size: 10px;
            margin-bottom: 2mm;
            border: none;
            padding-bottom: 1mm;
        }
        .reference-item {
            padding: 1mm 2mm;
            border: 1px dashed #888;
            border-radius: 4px;
            background: #f7f7f7;
            font-size: 12px;
            color: black !important;
            margin: 0 1mm;
            display: flex;
            align-items: center;
        }
        .reference-item strong {
            font-size: 12px;
            color: black !important;
            margin-left: 4px;
        }
        .customer-info {
            margin-bottom: 2mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 1mm;
            font-size: 9px;
        }
        .info-row {
            display: flex;
            margin-bottom: 1mm;
        }
        .info-label {
            width: 30%;
        }
        .info-value {
            width: 70%;
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
        .items-table td:nth-child(4),
        .items-table td:nth-child(5) {
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
            background-color: #444 !important;
            font-size: 16px;
        }
        .row-total td {
            border-top: 1.5px solid #888;
            border-bottom: 1.5px solid #888;
            color: #fff !important;
        }
        tr.row-total,
        tr.row-total td {
            background-color: #444 !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
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
        .barcode-area {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 30px;
            margin: 2mm 0 0;
        }
        .barcode-area svg {
            width: auto;
            height: 25px;
        }
    </style>
</head>
<body>
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
        
        <!-- Invoice Title -->
        <div class="invoice-title">
            @if($invoice->type == 'credit')
                فاتورة مبيعات: آجل
            @else
                فاتورة مبيعات: نقدي
            @endif
        </div>
        
        <!-- Invoice Number -->
        <div class="section">
            <div class="invoice-number-box">
                <div class="invoice-number-value">
                    {{ $invoice->invoice_number_in_shift ?? ($invoice->shift ? $invoice->id : $invoice->invoice_number) }}
                </div>
            </div>
        </div>
        
        <!-- Reference Information -->
        <div class="reference-info">
            <div class="reference-item">
                <strong>الرقم المرجعي:</strong> {{ $invoice->reference_number ?? $invoice->invoice_number }}
            </div>
            
            @if($invoice->customer_id && $invoice->customer_id != 1 && $invoice->customer && $invoice->customer->name != 'عميل نقدي')
                <div class="reference-item">
                    <strong>العميل:</strong> {{ $invoice->customer->name }}
                </div>
            @endif
        </div>
        
        <!-- Customer Information -->
        @if($invoice->customer_id && $invoice->customer_id != 1 && $invoice->customer && $invoice->customer->name != 'عميل نقدي')
            <div class="customer-info section">
                <div class="info-row">
                    <div class="info-label">العميل:</div>
                    <div class="info-value">{{ $invoice->customer->name }}</div>
                </div>
                
                @if($invoice->customer->phone)
                    <div class="info-row">
                        <div class="info-label">الهاتف:</div>
                        <div class="info-value">{{ $invoice->customer->phone }}</div>
                    </div>
                @endif
                
                @if($invoice->customer->address)
                    <div class="info-row">
                        <div class="info-label">العنوان:</div>
                        <div class="info-value">{{ $invoice->customer->address }}</div>
                    </div>
                @endif
                
                @if($invoice->delivery_employee_id)
                    <div class="info-row">
                        <div class="info-label">مندوب التوصيل:</div>
                        <div class="info-value">{{ $invoice->deliveryEmployee->name ?? '-' }}</div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Delivery Employee (Cash Customer) -->
        @if(!($invoice->customer_id && $invoice->customer_id != 1 && $invoice->customer && $invoice->customer->name != 'عميل نقدي') && $invoice->delivery_employee_id)
            <div class="customer-info section">
                <div class="info-row">
                    <div class="info-label">مندوب التوصيل:</div>
                    <div class="info-value">{{ $invoice->deliveryEmployee->name ?? '-' }}</div>
                </div>
            </div>
        @endif

        <!-- Meta Information -->
        <div class="meta-info section">
            <div class="meta-item">
                <strong>الوردية:</strong> {{ $invoice->shift->name ?? ($invoice->shift_id ?? '-') }}
            </div>
            
            <div class="meta-item">
                <strong>التاريخ:</strong> {{ $invoice->created_at->format('Y/m/d') }}
            </div>
            
            <div class="meta-item">
                <strong>الوقت:</strong> {{ $invoice->created_at->format('h:i A') }}
            </div>
            
            <div class="meta-item">
                <strong>الكاشير:</strong> {{ $invoice->user->name ?? (auth()->user()->name ?? 'Admin') }}
            </div>
        </div>

        <!-- Invoice Notes -->
        @if($invoice->notes)
            <div class="section" style="margin-bottom: 2mm; border-bottom: 1px dashed #000; padding-bottom: 1mm;">
                <div style="display: flex">
                    <div style="width: 30%; font-weight: 700;">ملاحظات:</div>
                    <div style="width: 70%;">{{ $invoice->notes }}</div>
                </div>
            </div>
        @endif
        
        <!-- Items Table -->
        <div class="section">
            <table class="items-table no-break">
                <thead>
                    <tr>
                        <th width="30%">الصنف</th>
                        <th width="15%">الكمية</th>
                        <th width="15%">الوحدة</th>
                        <th width="20%">السعر</th>
                        <th width="20%">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format($item->quantity, 3) }}</td>
                            <td>
                                @if(isset($item->productUnit) && $item->productUnit->unit)
                                    {{ $item->productUnit->unit->name }}
                                @elseif(isset($item->unit) && $item->unit)
                                    {{ $item->unit->name }}
                                @elseif(isset($item->unit_name))
                                    {{ $item->unit_name }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Invoice Summary -->
        <div class="totals-section section">
            <div class="totals-header">ملخص الفاتورة</div>
            <table class="totals-table no-break">
                <tr>
                    <td>عدد الأصناف:</td>
                    <td>{{ count($invoice->items) }}</td>
                </tr>
                
                <tr>
                    <td>إجمالي المبيعات:</td>
                    <td>{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                
                @if($invoice->discount_value > 0)
                    <tr>
                        <td>إجمالي الخصومات:</td>
                        <td>{{ number_format($invoice->discount_value, 2) }}</td>
                    </tr>
                @endif
                
                @if(config('settings.tax_enabled', false))
                    <tr>
                        <td>ضريبة القيمة المضافة:</td>
                        <td>{{ number_format($invoice->tax_amount ?? ($invoice->total * 0.14), 2) }}</td>
                    </tr>
                @endif
                
                <tr class="row-total" style="background-color: #444 !important; color: #fff !important;">
                    <td style="color: #fff !important;">الإجمالي المستحق:</td>
                    <td style="color: #fff !important;">{{ number_format($invoice->total, 2) }}</td>
                </tr>
                
                <tr>
                    <td>المبلغ المدفوع:</td>
                    <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                
                <tr class="row-highlight">
                    <td>المبلغ المتبقي:</td>
                    <td>{{ number_format($invoice->remaining_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Payment Details (Mixed Payment) -->
        @if($invoice->type === 'mixed')
            <div class="totals-section section">
                <div class="totals-header">تفاصيل الدفعات</div>
                <table class="totals-table no-break">
                    <thead>
                        <tr>
                            <td>الطريقة</td>
                            <td>المبلغ</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                            <tr>
                                <td>
                                    @switch(strtolower($payment->method))
                                        @case('cash')
                                            كاش
                                            @break
                                        @case('credit')
                                            آجل
                                            @break
                                        @case('visa')
                                            فيزا
                                            @break
                                        @case('transfer')
                                            تحويل
                                            @break
                                        @case('card')
                                            بطاقة
                                            @break
                                        @default
                                            {{ ucfirst($payment->method) }}
                                    @endswitch
                                </td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Store Contact Info -->
        @if(\App\Models\Setting::get('show_store_info', true) && !\App\Models\Setting::get('store_info_at_bottom', true) && 
            (\App\Models\Setting::get('print_store_address') || \App\Models\Setting::get('print_store_phone')))
            <div class="section" style="text-align: center; border: 1px solid #000; padding: 1mm; margin-top: 1mm;">
                @if(\App\Models\Setting::get('print_store_address'))
                    <div style="font-size: 9px; margin-bottom: 1mm;">
                        <strong>العنوان:</strong> {{ \App\Models\Setting::get('print_store_address') }}
                    </div>
                @endif
                
                @if(\App\Models\Setting::get('print_store_phone'))
                    <div style="font-size: 9px;">
                        <strong>الهاتف:</strong> {{ \App\Models\Setting::get('print_store_phone') }}
                    </div>
                @endif
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
            @if(!\App\Models\Setting::get('receipt_footer') && !\App\Models\Setting::get('footer_text_above_logo'))
                <div class="footer-text page-footer">
                    نشكركم لزيارتنا ويسعدنا خدمتكم دائماً
                </div>
            @endif

            <!-- Barcode -->
            <div class="barcode-area no-break" style="width:100%; margin:12mm 0 0; padding:0 0 0 0; position:relative; z-index:10;">
                @php
                    $barcodeValue = $invoice->reference_number ?: $invoice->invoice_number;
                @endphp
                @if($barcodeValue)
                    <div style="width:100%;">
                        {!! str_replace('<svg', '<svg style="width:100%;height:50px;max-width:100%;display:block;"', DNS1D::getBarcodeSVG($barcodeValue, 'C128', 2.7, 50, 'black', false)) !!}
                    </div>
                @endif
            </div>
            <!-- Ensure nothing appears after barcode -->
            <div style="height:1mm;"></div>
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
                window.close();
            }
        }
    </script>
</body>
</html> 