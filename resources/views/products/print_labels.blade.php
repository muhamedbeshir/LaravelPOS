<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة باركود - {{ $product->name }}</title>
    <style>
        @font-face {
            font-family: 'Cairo';
            src: url('/fonts/cairo/Cairo-Regular.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        
        @font-face {
            font-family: 'Cairo';
            src: url('/fonts/cairo/Cairo-Bold.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        
        @font-face {
            font-family: 'Cairo';
            src: url('/fonts/cairo/Cairo-Black.ttf') format('truetype');
            font-weight: 900;
            font-style: normal;
        }

        @page {
            size: 38mm 25mm;
            margin: 0 !important;
            padding: 0 !important;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: 'Cairo', 'DejaVu Sans', sans-serif;
            width: 38mm;
            height: 25mm;
            box-sizing: border-box;
            color: #000 !important;
        }

        .label-container, .label, .store-name, .barcode-section, .barcode-number, .product-name, .price {
            color: #000 !important;
            font-weight: 900 !important;
        }

        .label-container {
            width: 100%;
            height: 100%;
        }

        .label {
            width: 100%;
            height: 100%;
            padding: 0.5mm;
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

        .store-name { 
            font-size: 6.5pt; 
            line-height: 1.2;
            height: auto;
            overflow: visible;
            white-space: normal;
            text-overflow: clip;
            width: 100%;
            margin-bottom: 0.5mm;
            padding-top: 1.2mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .barcode-section {
            width: 100%;
            margin-bottom: 0.1mm;
            padding: 0 2mm;
            box-sizing: border-box;
        }

        .barcode-section.no-store-name {
            padding-top: 2mm;
        }

        .barcode-img { 
            width: 100%;
            height: auto;
            max-height: 10mm;
            min-height: 8mm;
            display: block;
            margin-bottom: 0;
            padding: 0.5mm;
            box-sizing: border-box;
        }
        
        .no-store-name .barcode-img {
            max-height: 11mm;
            min-height: 9mm;
        }

        .barcode-number { 
            font-size: 6pt;
            letter-spacing: 0.3px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            margin-bottom: 0;
        }

        .product-name {
            font-size: 7.5pt;
            width: 100%;
            line-height: 1;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            hyphens: auto;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 0.5mm;
        }

        .price { 
            font-size: 7.5pt;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            width: 100%;
            margin-bottom: 0;
        }

        @media screen {
            body {
                border: 1px dashed #000;
            }
        }

        @media print {
            body {
                border: none !important;
            }
        }
    </style>
</head>
<body>

<div class="label-container">
    @foreach ($products_to_print as $page_product)
        <div class="label">
            {{-- Check if store name will be shown --}}
            @php
                $showStoreName = !empty($print_settings['barcode_show_store_name']) && !empty($print_settings['store_name']);
            @endphp

            {{-- Shop name at top --}}
            @if($showStoreName)
                <div class="store-name">{{ $print_settings['store_name'] }}</div>
            @endif

            {{-- Barcode section --}}
            <div class="barcode-section {{ !$showStoreName ? 'no-store-name' : '' }}">
                <img class="barcode-img" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($page_product['barcode_value'], $barcode_type, 2, 50) }}">
                <div class="barcode-number">{{ $page_product['barcode_value'] }}</div>
            </div>

            {{-- Product name --}}
            <div class="product-name" data-product-name="{{ $page_product['name'] }}">
                {{ $page_product['name'] }}
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

<script>
    function adjustProductNameSize() {
        const productNames = document.querySelectorAll('.product-name');
        
        productNames.forEach(function(element) {
            const text = element.textContent.trim();
            // Count words by splitting on spaces and filtering out empty strings
            const wordCount = text.split(' ').filter(word => word.length > 0).length;
            
            // Get current font size and convert to number
            let currentSize = parseFloat(window.getComputedStyle(element).fontSize);
            
            // Adjust font size based on word count
            if (wordCount > 2) {
                // For 3-4 words
                if (wordCount <= 4) {
                    element.style.fontSize = '7pt';
                }
                // For 5-6 words
                else if (wordCount <= 6) {
                    element.style.fontSize = '6.5pt';
                }
                // For 7+ words
                else {
                    element.style.fontSize = '6pt';
                }
            }
            
            // For very long text (character count)
            if (text.length > 30) {
                element.style.fontSize = '5.5pt';
            }
        });
    }

    // Run when page is loaded
    window.addEventListener('DOMContentLoaded', adjustProductNameSize);
</script>

@if(!$is_preview)
<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
            window.close();
        }, 500);
    };
</script>
@endif
</body>
</html>
