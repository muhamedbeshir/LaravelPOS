@extends('layouts.app')

@section('title', 'طباعة باركود - ' . $product->name)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-barcode me-2"></i>
                        طباعة باركود - {{ $product->name }}
                    </h5>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>
                        العودة للمنتجات
                    </a>
                </div>
                <div class="card-body">
                    @if(!$product->barcode && $product->units->every(fn($u) => empty($u->barcode)))
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        هذا المنتج لا يحتوي على باركود. الرجاء إضافة باركود للمنتج أولاً.
                    </div>
                    @else
                    
                    <!-- إعدادات سريعة -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="copiesCount" class="form-label">عدد النسخ</label>
                            <input type="number" id="copiesCount" class="form-control" value="1" min="1" max="100">
                        </div>
                        <div class="col-md-4" style="display: none;">
                            <label for="barcodeType" class="form-label">نوع الباركود</label>
                            <select id="barcodeType" class="form-select">
                                <option value="C128">Code 128</option>
                                <option value="C39">Code 39</option>
                                <option value="EAN13">EAN 13</option>
                                <option value="UPCA">UPC-A</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="priceType" class="form-label">نوع السعر</label>
                            <select id="priceType" class="form-select">
                                @foreach($priceTypes as $priceType)
                                    <option value="{{ $priceType->id }}" {{ $priceType->is_default ? 'selected' : '' }}>
                                        {{ $priceType->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if($product->units && count($product->units) > 0)
                        <div class="col-md-4">
                            <label for="selectedBarcode" class="form-label">الوحدة والباركود</label>
                            <select id="selectedBarcode" class="form-select">
                                @foreach($product->units as $unit)
                                    @if($unit->barcodes->isNotEmpty())
                                        @foreach($unit->barcodes as $barcode)
                                            <option value="{{ $barcode->id }}" data-unit-id="{{ $unit->id }}">
                                                {{ $unit->unit->name ?? 'وحدة' }} - {{ $barcode->barcode }}
                                            </option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>

                    <!-- معاينة الباركود -->
                    <div class="alert alert-info">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6>معاينة الباركود:</h6>
                                <p><strong>المنتج:</strong> {{ $product->name }}</p>
                                <p><strong>الباركود:</strong> {{ $product->barcode }}</p>
                                <p><strong>حجم الطباعة:</strong> 38×25 مم</p>
                            </div>
                            <div class="col-md-6 text-center">
                                <div id="barcodePreview" style="background-color: white; padding: 10px; display: inline-block; border: 1px solid #ddd; min-height: 80px; min-width: 150px;">
                                    <!-- سيتم إضافة معاينة الباركود هنا -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- أزرار الطباعة -->
                    <div class="text-center mb-4">
                        <button class="btn btn-success btn-lg" onclick="startPrint()">
                            <i class="fas fa-print me-2"></i>
                            طباعة
                        </button>
                    </div>
                    
                    <div class="alert alert-info text-center">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>تعليمات الطباعة:</strong>
                            سيتم طباعة الباركود بحجم 38×25 مم. تأكد من تعيين حجم الورق الصحيح في الطابعة.
                        </small>
                    </div>

                    <!-- منطقة عرض الباركود -->
                    <div id="barcodesContainer" class="text-center" style="min-height: 100px;">
                        <!-- سيتم إضافة الباركود هنا -->
                    </div>
                    
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- قوالب الباركود المخفية -->
<div id="barcode-templates" style="display: none;">
    @foreach(['C128', 'C39', 'EAN13', 'UPCA'] as $type)
    <div id="template-{{ $type }}">
        @if($product->barcode)
            @php
                $cleanBarcode = preg_replace('/[^0-9A-Za-z]/', '', $product->barcode);
                if (empty($cleanBarcode) || strlen($cleanBarcode) < 4) {
                    $cleanBarcode = '1234567890';
                }
                
                if ($type === 'EAN13') {
                    $cleanBarcode = str_pad(substr($cleanBarcode, 0, 12), 12, '0', STR_PAD_LEFT);
                    $sum = 0;
                    for ($i = 0; $i < 12; $i++) {
                        $sum += (int)$cleanBarcode[$i] * (($i % 2 === 0) ? 1 : 3);
                    }
                    $checkDigit = (10 - ($sum % 10)) % 10;
                    $cleanBarcode .= $checkDigit;
                }
            @endphp
            
            @php
                $barcodeGenerated = false;
                $barcodeOutput = '';
                
                try {
                    if (class_exists('Milon\Barcode\DNS1D')) {
                        $barcodeOutput = DNS1D::getBarcodeHTML($cleanBarcode, $type, 2, 40);
                        if (!empty($barcodeOutput) && strlen($barcodeOutput) > 50) {
                            $barcodeGenerated = true;
                        }
                    }
                } catch (Exception $e) {
                    $barcodeGenerated = false;
                }
            @endphp
            
            @if($barcodeGenerated)
                {!! $barcodeOutput !!}
            @else
                <div style="font-family: Arial, sans-serif; font-size: 10px; border: 1px solid #000; padding: 5px; text-align: center; background: white;">
                    <div style="display: flex; height: 30px; margin-bottom: 5px;">
                        @for($i = 0; $i < min(strlen($cleanBarcode), 20); $i++)
                            <div style="flex: 1; background: {{ $i % 2 === 0 ? '#000' : '#fff' }}; margin-right: 0.5px;"></div>
                        @endfor
                    </div>
                    <div style="font-size: 8px; font-weight: bold; letter-spacing: 1px;">{{ $cleanBarcode }}</div>
                </div>
            @endif
        @else
            <div class="text-danger" style="font-size: 10px;">لا يوجد باركود</div>
        @endif
    </div>
    @endforeach
</div>

<!-- تخزين البيانات -->
<input type="hidden" id="productId" value="{{ $product->id }}">
<input type="hidden" id="productName" value="{{ $product->name }}">
<input type="hidden" id="productBarcode" value="{{ $product->barcode ?? '' }}">
<input type="hidden" id="settingsData" value="{{ base64_encode(json_encode($settings)) }}">
<input type="hidden" id="unitsData" value="{{ base64_encode(json_encode($product->units->keyBy('id'))) }}">

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const unitsData = JSON.parse(atob(document.getElementById('unitsData').value));
    const selectedBarcodeEl = document.getElementById('selectedBarcode');
    const priceTypeEl = document.getElementById('priceType');

    function updatePriceTypeOptions() {
        if (!selectedBarcodeEl || !priceTypeEl) return;
        
        const selectedOption = selectedBarcodeEl.options[selectedBarcodeEl.selectedIndex];
        if (!selectedOption) return;

        const unitId = selectedOption.dataset.unitId;
        const selectedUnit = unitsData[unitId];
        
        if (!selectedUnit || !selectedUnit.prices) {
            for (const option of priceTypeEl.options) {
                option.style.display = 'block';
            }
            return;
        }

        const availablePriceTypeIds = selectedUnit.prices.map(p => p.price_type_id.toString());
        
        let isCurrentSelectionVisible = false;

        for (const option of priceTypeEl.options) {
            const isVisible = availablePriceTypeIds.includes(option.value);
            option.style.display = isVisible ? 'block' : 'none';
            if (isVisible && option.selected) {
                isCurrentSelectionVisible = true;
            }
        }

        if (!isCurrentSelectionVisible) {
            const firstVisibleOption = Array.from(priceTypeEl.options).find(opt => opt.style.display !== 'none');
            if (firstVisibleOption) {
                firstVisibleOption.selected = true;
            }
        }
    }
    
    function getPrintUrl(isPreview) {
        const productId = document.getElementById('productId').value;
        const copies = document.getElementById('copiesCount').value;
        const barcodeType = document.getElementById('barcodeType').value;
        const barcodeId = selectedBarcodeEl ? selectedBarcodeEl.value : '';
        const priceTypeId = document.getElementById('priceType').value;

        let url = `{{ route('products.print-labels') }}?product_id=${productId}&copies=${copies}&barcode_type=${barcodeType}&barcode_id=${barcodeId}&price_type_id=${priceTypeId}`;
        
        if (isPreview) {
            url += '&is_preview=1';
        }
        
        return url;
    }

    async function updatePreview() {
        const previewContainer = document.getElementById('barcodePreview');
        previewContainer.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';

        try {
            const response = await fetch(getPrintUrl(true));
            if (!response.ok) {
                let errorMsg = response.statusText;
                if (response.status === 422) {
                    try {
                        const errorData = await response.json();
                        errorMsg = errorData.error || 'Unprocessable Content';
                    } catch (e) {
                        // In case the response is not JSON
                        errorMsg = await response.text();
                    }
                }
                throw new Error('فشل تحميل المعاينة: ' + errorMsg);
            }
            const html = await response.text();
            
            // Inject the first sticker's HTML into the preview
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const label = tempDiv.querySelector('.label');
            if(label){
                label.style.border = '2px solid #007bff';
                label.style.display = 'inline-block';
                label.style.width = 'auto';
                label.style.height = 'auto';
                previewContainer.innerHTML = label.outerHTML;
            } else {
                 previewContainer.textContent = 'لا يمكن إنشاء معاينة. تأكد من وجود باركود للمنتج/الوحدة.';
            }
    
        } catch (error) {
            console.error('Preview Error:', error);
            previewContainer.textContent = 'حدث خطأ أثناء تحميل المعاينة.';
        }
    }
    
    window.startPrint = function() {
        const url = getPrintUrl(false);
        const printWindow = window.open(url, '_blank', 'width=800,height=600');
        if (!printWindow) {
            alert('يرجى السماح بالنوافذ المنبثقة لطباعة الباركود.');
        }
    }

    // Initial setup
    updatePriceTypeOptions();
    updatePreview();

    // Event listeners
    document.getElementById('copiesCount').addEventListener('input', updatePreview);
    document.getElementById('barcodeType').addEventListener('change', updatePreview);
    document.getElementById('priceType').addEventListener('change', updatePreview);
    
    if (selectedBarcodeEl) {
        selectedBarcodeEl.addEventListener('change', function() {
            updatePriceTypeOptions();
            updatePreview();
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.barcode-label {
    width: 38mm;
    height: 25mm;
    border: 1px dashed #ddd;
    margin: 5px;
    padding: 1mm;
    display: inline-block;
    vertical-align: top;
    box-sizing: border-box;
    text-align: center;
    background: white;
    overflow: hidden;
}

.barcode-label .store-name {
    font-size: 8pt;
    font-weight: bold;
    margin-bottom: 1px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.barcode-label .product-name {
    font-size: 9pt;
    font-weight: bold;
    margin-bottom: 2px;
    overflow: hidden;
    white-space: normal;
    word-wrap: break-word;
    word-break: break-word;
    hyphens: auto;
    max-height: 18pt;
    line-height: 1.1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.barcode-label .barcode-image {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 1px 0;
    max-height: 15mm;
}

.barcode-label .barcode-image svg,
.barcode-label .barcode-image img {
    max-width: 100%;
    max-height: 100%;
}

.barcode-label .barcode-number {
    font-size: 7pt;
    font-family: monospace;
    margin-top: 1px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.barcode-label .price {
    font-size: 8pt;
    font-weight: bold;
    color: #2563eb;
    margin-top: 1px;
}

#barcodePreview .barcode-label {
    border: 2px solid #007bff;
    transform: scale(1.2);
    margin: 10px;
}
</style>
@endpush