<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة باركود متعدد</title>
    <style>
        @page {
            size: 38mm 25mm;
            margin: 0 !important;
            padding: 0 !important;
        }
        body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: 'DejaVu Sans', sans-serif;
            width: 38mm;
            height: 25mm;
            box-sizing: border-box;
        }
        .label-container {
            width: 100%;
            height: 100%;
        }
        .label {
            width: 100%;
            height: 100%;
            padding: 1mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            page-break-after: always;
            position: relative;
        }
        .label:last-child {
            page-break-after: avoid;
        }
        
        /* Shop name at top */
        .store-name { 
            font-size: 6pt; 
            font-weight: bold; 
            line-height: 1.4;
            height: auto;
            overflow: visible;
            white-space: normal;
            text-overflow: clip;
            width: 100%;
            margin-bottom: 1mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Barcode section */
        .barcode-section {
            width: 100%;
            margin-bottom: 1.5mm;
            padding: 0 2mm;
            box-sizing: border-box;
        }
        
        .barcode-img { 
            width: 100%;
            height: auto;
            max-height: 8mm;
            min-height: 6mm;
            display: block;
            margin-bottom: 0.5mm;
        }
        
        .barcode-number { 
            font-size: 5pt; 
            letter-spacing: 0.3px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-weight: bold;
        }
        
        /* Product name */
        .product-name {
            font-size: 7pt;
            font-weight: bold;
            width: 100%;
            line-height: 1.2;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            hyphens: auto;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 1mm;
        }
        
        /* Price at bottom */
        .price { 
            font-size: 7pt; 
            font-weight: bold; 
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            width: 100%;
        }

        @media screen {
            body {
                border: 1px dashed #ccc;
            }
        }
        @media print {
            body {
                border: none !important;
            }
        }
        
        #page-info {
            display: none;
        }
        @media screen {
            #page-info {
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 9999;
                background-color: rgba(255, 255, 255, 0.9);
                padding: 10px 15px;
                border-radius: 4px;
                border: 1px solid #ddd;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                font-family: Arial, sans-serif;
                display: block;
            }
        }
    </style>
</head>
<body>

<!-- معلومات الصفحة - تظهر فقط في المعاينة -->
@if(isset($is_preview) && $is_preview)
<div id="page-info">
    <h4 style="margin-top: 0; margin-bottom: 10px;">معاينة الباركود</h4>
    <p style="margin: 5px 0;">
        <strong>عدد الملصقات:</strong> {{ count($products_to_print) }}
    </p>
    <p style="margin: 5px 0;">
        <strong>نوع الباركود:</strong> {{ $barcode_type }}
    </p>
    <p style="margin-bottom: 10px;">
        <strong>الحجم:</strong> 38×25 ملم
    </p>
    <div style="text-align: center;">
        <button onclick="printLabels(event)" style="padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer;">
            طباعة
        </button>
        <button onclick="closeWindow(event)" style="padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer;">
            إغلاق
        </button>
    </div>
</div>
@endif

<div class="label-container">
    @foreach ($products_to_print as $page_product)
        <div class="label">
            {{-- Shop name at top --}}
            @if(!empty($print_settings['barcode_show_store_name']) && !empty($print_settings['store_name']))
                <div class="store-name">{{$print_settings['store_name']}}</div>
            @endif
            
            {{-- Barcode section --}}
            <div class="barcode-section">
                <img class="barcode-img" src="data:image/png;base64,{{DNS1D::getBarcodePNG($page_product['barcode_value'], $barcode_type, 1.5, 40)}}">
                <div class="barcode-number">{{$page_product['barcode_value']}}</div>
            </div>

            {{-- Product name --}}
            <div class="product-name">
                {{$page_product['name']}}
            </div>

            {{-- Price at bottom --}}
            @if(!empty($print_settings['barcode_show_price']) && !empty($page_product['price']))
                <div class="price">
                    السعر - {{ number_format($page_product['price'], 0) }}
                </div>
            @endif
        </div>
    @endforeach
</div>

@if(!isset($is_preview) || !$is_preview)
<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
            window.close();
        }, 500);
    };
</script>
@else
<script>
    function printLabels(event) {
        event.preventDefault();
        window.print();
    }
    
    function closeWindow(event) {
        event.preventDefault();
        window.close();
    }
</script>
@endif
</body>
</html> 