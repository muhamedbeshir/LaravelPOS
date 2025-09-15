<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة #{{ $invoice->invoice_number }}</title>
    <!-- إضافة خط Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
                orphans: 0;
                widows: 0;
            }
            body {
                width: 72mm; /* تقليل العرض لمنع القص من الجانبين */
                margin: 0 auto !important;
                padding: 4mm !important; /* هوامش متساوية من جميع الجهات */
            }
            /* منع قطع الصفحة داخل الجداول */
            .no-break {
                page-break-inside: avoid;
            }
            /* إيقاف الطباعة بعد المحتوى */
            .page-footer {
                page-break-after: always;
            }
            /* إضافة تباعدات معقولة بين العناصر */
            .section {
                margin-bottom: 2mm !important;
            }
            .main-container > * {
                padding: 0.5mm 0 !important;
            }
            td, th {
                padding: 1px !important;
                font-weight: bold !important;
                border-color: black !important;
            }
            .invoice-box {
                padding: 1px !important;
                margin: 0 1mm 1mm !important;
                border-color: black !important;
                border-width: 1.5px !important;
            }
            .store-info {
                padding: 1px !important;
                margin-bottom: 1mm !important;
                border-color: black !important;
                border-width: 1.5px !important;
            }
            /* منع ظهور المساحات البيضاء في أسفل الصفحة */
            html, body {
                height: auto !important;
                overflow: hidden !important;
            }
            /* تأكيد ظهور حدود الجداول عند الطباعة */
            table, th, td {
                border-color: black !important;
                border-style: solid !important;
            }
            .items-table, .totals-table {
                border-width: 2px !important;
            }
            .items-table th {
                border-width: 1.5px !important;
            }
        }

        body {
            font-family: 'Cairo', sans-serif;
            margin: 0 auto;
            padding: 4mm; /* هوامش متساوية من كل الجهات */
            font-size: 12px;
            line-height: 1.3;
            font-weight: bold; /* جعل كل النصوص بولد */
            max-width: 72mm; /* تقليل العرض لمنع القص */
            background-color: white;
            box-sizing: border-box;
            color: black !important;
        }
        
        /* جعل كل العناصر بولد */
        * {
            font-weight: bold !important;
            color: black !important;
        }

        .main-container {
            width: 100%;
            padding: 0;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center; /* توسيط العناصر أفقياً */
        }

        .section {
            margin-bottom: 2mm;
            width: 100%;
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 2mm;
            width: 100%;
        }

        .invoice-header img {
            max-width: 450px;
            max-height: 75px;
            margin-bottom: 1mm;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Footer logo styling */
        .footer-logo {
            max-width: 110px;
            max-height: 45px;
            display: block;
            margin: 1mm auto;
        }
        
        .store-name {
            font-size: 16px;
            font-weight: bold;
            margin: 1mm 0;
        }
        
        .invoice-title {
            font-size: 14px;
            font-weight: bold;
            margin: 1mm 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 1mm;
        }
        
        .invoice-info {
            width: 100%;
            margin-bottom: 2mm;
            display: flex;
            justify-content: center; /* توسيط المحتوى */
        }
        
        .invoice-box {
            border: 1.5px solid black; /* حدود سوداء سميكة */
            padding: 2px;
            text-align: center;
            margin: 0 1mm 1mm;
            flex: 1;
            max-width: 45%; /* تحديد أقصى عرض */
        }
        
        .invoice-box-label {
            font-size: 10px;
            margin-bottom: 0.5mm;
        }
        
        .invoice-box-value {
            font-size: 11px;
            font-weight: bold;
        }
        
        .invoice-meta {
            display: flex;
            justify-content: space-around; /* توزيع المساحة بشكل متساوٍ */
            margin-bottom: 2mm;
            font-size: 8px; /* تصغير حجم الخط */
            width: 100%;
        }

        /* نمط خاص لمعلومات الوردية والتاريخ والكاشير */
        .meta-info {
            font-size: 8px;
            color: #000;
        }
        
        .meta-info strong {
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
            font-size: 9px;
            table-layout: fixed; /* تحديد عرض ثابت للأعمدة */
            border: 2px solid black; /* حدود خارجية سميكة */
        }

        .items-table th {
            background-color: #e0e0e0; /* خلفية أغمق قليلاً */
            border: 1.5px solid black; /* حدود سميكة وسوداء */
            padding: 2px 1px;
            text-align: center;
            font-weight: 900; /* أكثر سماكة */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis; /* قطع النص الزائد واستبداله بنقاط */
        }

        .items-table td {
            border: 1px solid black; /* حدود سوداء */
            padding: 2px 1px;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis; /* قطع النص الزائد واستبداله بنقاط */
        }
        
        /* تنسيق الكمية والوحدة */
        .items-table td:nth-child(2), 
        .items-table td:nth-child(3) {
            font-size: 8px;
        }
        
        /* تنسيق السعر والإجمالي - جعل الأرقام من اليسار إلى اليمين */
        .items-table td:nth-child(4),
        .items-table td:nth-child(5) {
            text-align: left;
            direction: ltr;
        }

        .totals-table {
            width: 100%;
            margin-bottom: 2mm;
            border-collapse: collapse;
            font-size: 9px;
            table-layout: fixed;
            border: 2px solid black;
        }
        
        .totals-table td {
            padding: 3px 2px;
            border: 1px solid black;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* 标题单元格样式 */
        .totals-table td:first-child {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        
        /* 数值单元格样式 */
        .totals-table td:last-child {
            text-align: left;
            direction: ltr;
            font-weight: bold;
        }
        
        /* 突出显示净额行 */
        .total-net {
            background-color: #e8e8e8;
        }
        
        .total-net td {
            border-top: 2px solid black;
            border-bottom: 2px solid black;
            font-size: 10px;
        }
        
        /* 突出显示剩余金额行 */
        .total-remaining {
            background-color: #f0f0f0;
        }
        
        .total-remaining td {
            font-size: 10px;
        }
        
        /* 总计标题 */
        .totals-header {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1mm;
            padding: 1px 0;
            border-bottom: 1px solid #aaa;
        }
        
        /* 左侧区域和右侧区域的样式 */
        .total-area-left {
            width: 60%;
            vertical-align: top;
        }
        
        .total-area-right {
            width: 40%;
            vertical-align: top;
        }
        
        /* 添加分隔符 */
        .totals-separator {
            height: 1px;
            background-color: #ddd;
            margin: 1mm 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 2mm;
            margin-bottom: 1mm;
            font-size: 10px;
            width: 100%;
        }
        
        .store-info {
            text-align: center;
            border: 1.5px solid black; /* حدود سوداء سميكة */
            padding: 2px;
            margin-bottom: 1mm;
            font-size: 10px;
            width: 100%;
        }

        .invoice-ref-box {
            border: 2px solid black; /* حدود سوداء سميكة */
            border-radius: 3px;
            padding: 2px;
            text-align: center;
            margin: 0 auto 2mm;
            width: 100%;
            background-color: #f8f8f8;
        }
        
        .invoice-ref-box-label {
            font-size: 10px;
            margin-bottom: 0.5mm;
            color: black !important;
        }
        
        .invoice-ref-box-value {
            font-size: 14px; /* 增大字体 */
            font-weight: bold;
        }

        /* إضافة خط فاصل سميك بين الإجماليات */
        .totals-table tr:nth-child(4) td {
            border-top: 2px solid black; /* خط سميك قبل صافي المستحق */
        }
        
        /* تمييز صف الإجمالي النهائي */
        .totals-table tr:nth-child(4) {
            background-color: #f0f0f0;
            color: black !important;
        }

        .reference-number {
            text-align: center;
            font-size: 9px;
            color: black !important;
            margin: 1mm 0;
            padding-bottom: 1mm;
            width: 100%;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .reference-number span {
            display: inline-block;
            margin: 0 2px;
        }
        
        .reference-number strong {
            font-weight: 900;
            color: black !important;
        }
        .barcode-area {
    display: flex;             /* مهم جداً: تفعيل Flexbox على العنصر الأب */
    justify-content: center;   /* لتوسيط الباركود أفقياً */
    align-items: center;       /* لتوسيط الباركود رأسياً */
    height: 40px;              /* مثال: ارتفاع الحاوية عشان التوسيط الرأسي يبان.
                                  تقدر تعدلها حسب اللي يناسبك، بس لازم تكون أكبر من ارتفاع الـ SVG */
    /* border: 1px solid #ccc; */ /* أزِل هذا السطر إذا كنت تريد إزالة المربع */
    margin-top: 0.01mm;           /* المسافة من العنصر اللي فوقه (يمكن تعديلها) */
}

        .barcode-area svg {
            width: auto;
            justify-content: center;   /* توسيط المحتوى أفقياً (يمين ويسار) */
    align-items: center;       /* توسيط المحتوى رأسياً (فوق وتحت) */
            height: 20px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header Logo and Info -->
        @if(\App\Models\Setting::get('show_header_logo', true) && \App\Models\Setting::get('header_logo'))
        <div class="invoice-header section">
            <img src="{{ asset('storage/' . \App\Models\Setting::get('header_logo')) }}" alt="شعار المحل" onerror="this.style.display='none'">
            
            @if(\App\Models\Setting::get('header_text_below_logo'))
            <div class="store-name">{{ \App\Models\Setting::get('header_text_below_logo') }}</div>
            @endif
            
            @if(\App\Models\Setting::get('show_store_info', true) && !\App\Models\Setting::get('store_info_at_bottom', true) && \App\Models\Setting::get('print_store_name'))
            <div class="store-name">{{ \App\Models\Setting::get('print_store_name') }}</div>
            @endif
        </div>
        @elseif(\App\Models\Setting::get('header_text_below_logo') || (\App\Models\Setting::get('show_store_info', true) && !\App\Models\Setting::get('store_info_at_bottom', true)))
        <div class="invoice-header section">
            @if(\App\Models\Setting::get('header_text_below_logo'))
            <div class="store-name">{{ \App\Models\Setting::get('header_text_below_logo') }}</div>
            @endif
            
            @if(\App\Models\Setting::get('show_store_info', true) && !\App\Models\Setting::get('store_info_at_bottom', true) && \App\Models\Setting::get('print_store_name'))
            <div class="store-name">{{ \App\Models\Setting::get('print_store_name') }}</div>
            @endif
        </div>
        @endif
        
        <!-- Additional Receipt Header -->
        @if(\App\Models\Setting::get('receipt_header'))
        <div class="section" style="text-align: center; font-size: 11px; margin-bottom: 2mm; padding: 2px; border-bottom: 1px dashed #ccc;">
            {{ \App\Models\Setting::get('receipt_header') }}
        </div>
        @endif
        
        <!-- Invoice Title -->
        <div class="section" style="text-align: center;">
            <div class="invoice-title">
                @if($invoice->type == 'credit')
                    فاتورة مبيعات: آجل
                @else
                    فاتورة مبيعات: نقدي
                @endif
            </div>
        </div>
        
        <!-- معلومات الوردية ورقم الفاتورة -->
        <div class="section" style="text-align: center;">
            <div class="invoice-ref-box" style="display: inline-block; width: auto; padding: 2px 10px; margin: 0 auto 1mm; min-width: 40%; background-color: #f0f0f0; border-width: 1.5px;">
                <div class="invoice-ref-box-value" style="margin: 0; font-size: 15px;">
                    {{ $invoice->invoice_number_in_shift ?? ($invoice->shift ? $invoice->id : $invoice->invoice_number) }}
                </div>
            </div>
            <!-- الرقم المرجعي خارج المربع -->
            <div class="reference-number" style="margin-top: 1mm;">
                <span style="border-bottom: 1px dashed #999; padding: 0 2mm; font-weight: 900; font-size: 11px;">
                    <strong>الرقم المرجعي:</strong> {{ $invoice->reference_number ?? $invoice->invoice_number }}
                </span>
                @if($invoice->customer_id && $invoice->customer_id != 1 && $invoice->customer && $invoice->customer->name != 'عميل نقدي')
                <span style="border-bottom: 1px dashed #999; padding: 0 2mm; color: #000;">
                    <strong>العميل:</strong> {{ $invoice->customer->name }}
                </span>
                @endif
            </div>
        </div>
        
        <!-- معلومات العميل لغير العملاء النقدي -->
        @if($invoice->customer_id && $invoice->customer_id != 1 && $invoice->customer && $invoice->customer->name != 'عميل نقدي')
        <div class="section" style="margin-bottom: 2mm; border-bottom: 1px dashed #aaa;">
            <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                <tr>
                    <td style="width: 20%; font-weight: bold; padding: 1mm 0;">العميل:</td>
                    <td style="width: 80%; padding: 1mm 0;">{{ $invoice->customer->name }}</td>
                </tr>
                @if($invoice->customer->phone)
                <tr>
                    <td style="width: 20%; font-weight: bold; padding: 1mm 0;">الهاتف:</td>
                    <td style="width: 80%; padding: 1mm 0;">{{ $invoice->customer->phone }}</td>
                </tr>
                @endif
                @if($invoice->customer->address)
                <tr>
                    <td style="width: 20%; font-weight: bold; padding: 1mm 0;">العنوان:</td>
                    <td style="width: 80%; padding: 1mm 0;">{{ $invoice->customer->address }}</td>
                </tr>
                @endif
                @if($invoice->delivery_employee_id)
                <tr>
                    <td style="width: 20%; font-weight: bold; padding: 1mm 0;">مندوب التوصيل:</td>
                    <td style="width: 80%; padding: 1mm 0;">{{ $invoice->deliveryEmployee->name ?? '-' }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif

        <!-- معلومات موظف الدليفري للعميل النقدي -->
        @if(!($invoice->customer_id && $invoice->customer_id != 1 && $invoice->customer && $invoice->customer->name != 'عميل نقدي') && $invoice->delivery_employee_id)
        <div class="section" style="margin-bottom: 2mm; border-bottom: 1px dashed #aaa;">
            <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                <tr>
                    <td style="width: 30%; font-weight: bold; padding: 1mm 0;">مندوب التوصيل:</td>
                    <td style="width: 70%; padding: 1mm 0;">{{ $invoice->deliveryEmployee->name ?? '-' }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- معلومات الوردية -->
        <div class="invoice-meta section" style="margin-top: 0; margin-bottom: 2mm; border-bottom: 1px dashed #aaa;">
            <div class="meta-info"><strong>الوردية:</strong> {{ $invoice->shift->name ?? ($invoice->shift_id ?? '-') }}</div>
            <div class="meta-info">
                <strong>التاريخ:</strong> {{ $invoice->created_at->format('Y/m/d') }}
                <strong style="margin-right: 3px;">الوقت:</strong> {{ $invoice->created_at->format('h:i A') }}
            </div>
            <div class="meta-info"><strong>الكاشير:</strong> {{ $invoice->user->name ?? (auth()->user()->name ?? 'Admin') }}</div>
        </div>

        <!-- ملاحظات الفاتورة -->
        @if($invoice->notes)
        <div class="section" style="margin-bottom: 2mm; border-bottom: 1px dashed #aaa;">
            <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                <tr>
                    <td style="width: 20%; font-weight: bold; padding: 1mm 0;">ملاحظات:</td>
                    <td style="width: 80%; padding: 1mm 0;">{{ $invoice->notes }}</td>
                </tr>
            </table>
        </div>
        @endif
        
        <!-- جدول المنتجات -->
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
                            @if(isset($item->unit_name))
                                {{ $item->unit_name }}
                            @elseif(isset($item->unit) && $item->unit)
                                {{ $item->unit->name }}
                            @elseif(isset($item->product->mainUnit) && $item->product->mainUnit)
                                {{ $item->product->mainUnit->name }}
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

        <!-- الإجماليات -->
        <div class="section">
            <div class="totals-header">ملخص الفاتورة</div>
            <table class="totals-table no-break">
                <!-- معلومات إضافية -->
                <tr>
                    <td width="50%">عدد الأصناف:</td>
                    <td width="50%">{{ count($invoice->items) }}</td>
                </tr>
                
                <!-- المعلومات الأساسية للفاتورة -->
                <tr>
                    <td>إجمالي المبيعات:</td>
                    <td>{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                
                <!-- معلومات الخصم -->
                @if($invoice->discount_value > 0)
                <tr>
                    <td>إجمالي الخصومات:</td>
                    <td>{{ number_format($invoice->discount_value, 2) }}</td>
                </tr>
                @endif
                
                <!-- معلومات الضريبة -->
                @if(config('settings.tax_enabled', false))
                <tr>
                    <td>ضريبة القيمة المضافة:</td>
                    <td>{{ number_format($invoice->tax_amount ?? ($invoice->total * 0.14), 2) }}</td>
                </tr>
                @endif
                
                <!-- إجمالي المستحق -->
                <tr class="total-net">
                    <td>الإجمالي المستحق:</td>
                    <td>{{ number_format($invoice->total, 2) }}</td>
                </tr>
                
                <!-- معلومات الدفع -->
                <tr>
                    <td>المبلغ المدفوع:</td>
                    <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                
                <!-- المبلغ المتبقي -->
                <tr class="total-remaining">
                    <td>المبلغ المتبقي:</td>
                    <td>{{ number_format($invoice->remaining_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($invoice->type === 'mixed')
        <!-- تفصيل الدفعات للفاتورة متعددة الدفعات -->
        <div class="section">
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
                        <td>{{ strtoupper($payment->method) }}</td>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Store Address and Phone Box -->
        @if(\App\Models\Setting::get('show_store_info', true) && !\App\Models\Setting::get('store_info_at_bottom', true) && (\App\Models\Setting::get('print_store_address') || \App\Models\Setting::get('print_store_phone')))
        <div class="section" style="text-align: center;">
            <div class="store-info-box" style="display: inline-block; padding: 3mm; margin: 2mm auto; border: 1px solid #333; background-color: #f9f9f9; min-width: auto; width: auto;">
                @if(\App\Models\Setting::get('print_store_address'))
                <div style="font-size: 11px; margin-bottom: 1mm; white-space: nowrap;">
                    <strong>العنوان:</strong> {{ \App\Models\Setting::get('print_store_address') }}
                </div>
                @endif
                @if(\App\Models\Setting::get('print_store_phone'))
                <div style="font-size: 11px; white-space: nowrap;">
                    <strong>الهاتف:</strong> {{ \App\Models\Setting::get('print_store_phone') }}
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Additional Receipt Footer -->
        @if(\App\Models\Setting::get('receipt_footer'))
        <div class="section" style="text-align: center; font-size: 11px; margin-bottom: 2mm; padding: 2px; border-top: 1px dashed #ccc;">
            {{ \App\Models\Setting::get('receipt_footer') }}
        </div>
        @endif
        
        <!-- Footer Text Above Logo -->
        @if(\App\Models\Setting::get('footer_text_above_logo'))
        <div class="section" style="text-align: center; font-size: 10px; margin-bottom: 1mm;">
            {{ \App\Models\Setting::get('footer_text_above_logo') }}
        </div>
        @endif
        
        <!-- Store Info at Bottom -->
        @if(\App\Models\Setting::get('show_store_info', true) && \App\Models\Setting::get('store_info_at_bottom', true))
        <div class="section">
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
        </div>
        @endif
        
        <!-- Footer Logo -->
        @if(\App\Models\Setting::get('show_footer_logo', true) && \App\Models\Setting::get('footer_logo'))
        <div class="section" style="text-align: center;">
            <img src="{{ asset('storage/' . \App\Models\Setting::get('footer_logo')) }}" alt="شعار المحل السفلي" class="footer-logo" onerror="this.style.display='none'">
        </div>
        @endif

        <!-- Default Footer if no custom footer -->
        @if(!\App\Models\Setting::get('receipt_footer') && !\App\Models\Setting::get('footer_text_above_logo'))
        <div class="footer page-footer">
            نشكركم لزيارتنا ويسعدنا خدمتكم دائماً
        </div>
        @endif

        <!-- Barcode -->
        <div class="section barcode-area" style="text-align: center; margin-top: 0.1mm; page-break-inside: avoid;">
            @php
                $barcodeValue = $invoice->reference_number ?? $invoice->invoice_number;
            @endphp
            @if($barcodeValue)
                <div>
                    {!! DNS1D::getBarcodeSVG($barcodeValue, 'C128', 2, 35, 'black', false) !!}
                </div>
            
            @endif
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