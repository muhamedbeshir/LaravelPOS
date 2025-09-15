// إضافة وحدة جديدة
function addUnit() {
    // التحقق من عدم تكرار الوحدات
    let selectedUnitIds = [];
    $('.unit-select').each(function() {
        let unitId = $(this).val();
        if (unitId) {
            selectedUnitIds.push(unitId);
        }
    });
    
    // إنشاء وحدة جديدة
    const unitIndex = $('.unit-row').length;
    
    // إنشاء عنصر الوحدة الجديدة
    const unitHtml = `
        <div class="unit-row mb-3 p-3 border rounded">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">الوحدة</label>
                        <select name="units[${unitIndex}][unit_id]" class="form-control unit-select" required>
                            <option value="">اختر الوحدة</option>
                            ${unitsOptions}
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">الباركود</label>
                        <input type="text" name="units[${unitIndex}][barcode]" class="form-control" placeholder="الباركود (اختياري)">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="mb-3 me-2">
                        <div class="form-check">
                            <input class="form-check-input main-unit-radio" type="radio" name="main_unit_index" value="${unitIndex}" id="main_unit_${unitIndex}">
                            <label class="form-check-label" for="main_unit_${unitIndex}">
                                وحدة رئيسية
                            </label>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger mb-3" onclick="removeUnit(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="prices-container">
                <h6 class="mb-2">الأسعار</h6>
                <div class="row">
                    ${priceTypesHtml(unitIndex)}
                </div>
            </div>
        </div>
    `;
    
    // إضافة عنصر الوحدة إلى الصفحة
    $('#units-container').append(unitHtml);
    
    // إذا كانت هذه أول وحدة، حددها كوحدة رئيسية
    if (unitIndex === 0) {
        $(`#main_unit_${unitIndex}`).prop('checked', true);
    }
    
    // إضافة حدث لمراقبة تغيير الوحدة للتحقق من التكرار
    $('.unit-select').on('change', function() {
        checkDuplicateUnits();
    });
    
    // التحقق من تكرار الوحدات بعد الإضافة
    checkDuplicateUnits();
}

// حذف وحدة
function removeUnit(button) {
    // حذف صف الوحدة
    $(button).closest('.unit-row').remove();
    
    // إعادة ترقيم الوحدات
    $('.unit-row').each(function(index) {
        $(this).find('.main-unit-radio').val(index).attr('id', `main_unit_${index}`);
        $(this).find('.form-check-label').attr('for', `main_unit_${index}`);
        
        // تحديث أسماء الحقول
        $(this).find('[name^="units["]').each(function() {
            const name = $(this).attr('name').replace(/units\[\d+\]/, `units[${index}]`);
            $(this).attr('name', name);
        });
    });
    
    // التأكد من وجود وحدة رئيسية محددة
    if ($('.main-unit-radio:checked').length === 0 && $('.main-unit-radio').length > 0) {
        $('.main-unit-radio').first().prop('checked', true);
    }
    
    // التحقق من تكرار الوحدات بعد الحذف
    checkDuplicateUnits();
}

// إنشاء HTML لأنواع الأسعار
function priceTypesHtml(unitIndex) {
    let html = '';
    
    if (typeof priceTypes !== 'undefined' && priceTypes.length > 0) {
        priceTypes.forEach(function(priceType) {
            html += `
                <div class="col-md-3 mb-2">
                    <label class="form-label">${priceType.name}</label>
                    <input type="hidden" name="units[${unitIndex}][prices][${priceType.id}][price_type_id]" value="${priceType.id}">
                    <input type="number" name="units[${unitIndex}][prices][${priceType.id}][value]" class="form-control" step="0.01" min="0" placeholder="السعر" required>
                </div>
            `;
        });
    }
    
    return html;
}

// تهيئة الصفحة
$(document).ready(function() {
    // تفعيل التحقق من الوحدات المكررة عند تحميل الصفحة
    checkDuplicateUnits();
    
    // إضافة مراقبة لتغيير الوحدات
    $('.unit-select').on('change', function() {
        checkDuplicateUnits();
    });
});

// التحقق من تكرار الوحدات
function checkDuplicateUnits() {
    let unitCounts = {};
    let hasDuplicates = false;
    
    // جمع جميع الوحدات المختارة
    $('.unit-select').each(function() {
        let unitId = $(this).val();
        if (unitId) {
            if (!unitCounts[unitId]) {
                unitCounts[unitId] = 1;
            } else {
                unitCounts[unitId]++;
                hasDuplicates = true;
                
                // تمييز الوحدات المكررة باللون الأحمر
                if (unitCounts[unitId] > 1) {
                    $(this).addClass('is-invalid');
                }
            }
        }
    });
    
    // إزالة التمييز من الوحدات غير المكررة
    $('.unit-select').each(function() {
        let unitId = $(this).val();
        if (unitId && unitCounts[unitId] === 1) {
            $(this).removeClass('is-invalid');
        }
    });
    
    // إظهار أو إخفاء تحذير
    if (hasDuplicates) {
        if ($('#duplicate-units-warning').length === 0) {
            $('<div id="duplicate-units-warning" class="alert alert-danger mt-3">' +
              '<i class="fas fa-exclamation-circle me-2"></i>' +
              'تنبيه: تم اختيار وحدات مكررة. سيتم تجاهل الوحدات المكررة عند الحفظ.' +
              '</div>').insertBefore('#units-container');
        }
    } else {
        $('#duplicate-units-warning').remove();
    }
    
    return !hasDuplicates;
} 