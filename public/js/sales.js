// التأكد من وجود المتغيرات المطلوبة
if (typeof window !== 'undefined') {
    window.browser = window.browser || {};
}

// المتغيرات العامة
let selectedProduct = null;
let unitsModal = null;

// Track if user manually edited paid amount
let paidAmountManuallyEdited = false;

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة المودال
    const unitsModalElement = document.getElementById('units-modal');
    if (unitsModalElement) {
        unitsModal = new bootstrap.Modal(unitsModalElement);
    }
    
    // تهيئة الأحداث
    initializeEvents();
});

// تهيئة أحداث الصفحة
function initializeEvents() {
    const invoiceType = document.getElementById('invoice-type');
    if (invoiceType) {
        invoiceType.addEventListener('change', function() {
            const customerSection = document.querySelector('.customer-section');
            if (this.value === 'credit') {
                customerSection.classList.remove('d-none');
            } else {
                customerSection.classList.add('d-none');
            }
        });
    }

    const orderType = document.getElementById('order-type');
    if (orderType) {
        orderType.addEventListener('change', function() {
            const deliverySection = document.querySelector('.delivery-section');
            if (this.value === 'delivery') {
                deliverySection.classList.remove('d-none');
            } else {
                deliverySection.classList.add('d-none');
            }
        });
    }

    const showCategories = document.getElementById('show-categories');
    if (showCategories) {
        showCategories.addEventListener('click', function() {
            document.getElementById('categories-section').classList.remove('d-none');
            document.getElementById('products-section').classList.add('d-none');
        });
    }

    const showProducts = document.getElementById('show-products');
    if (showProducts) {
        showProducts.addEventListener('click', function() {
            document.getElementById('categories-section').classList.add('d-none');
            document.getElementById('products-section').classList.remove('d-none');
            loadAllProducts();
        });
    }

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.trim();
            if (searchTerm) {
                searchProducts(searchTerm);
            } else {
                document.getElementById('categories-section').classList.remove('d-none');
                document.getElementById('products-section').classList.add('d-none');
            }
        }, 300));
    }

    document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', function() {
            const categoryId = this.dataset.id;
            loadCategoryProducts(categoryId);
        });
    });

    const discount = document.getElementById('discount');
    if (discount) {
        discount.addEventListener('input', calculateTotals);
    }
    const discountType = document.getElementById('discount-type');
    if (discountType) {
        discountType.addEventListener('change', calculateTotals);
    }
    const paidAmount = document.getElementById('paid-amount');
    if (paidAmount) {
        paidAmount.addEventListener('input', calculateRemaining);
    }
    const saveInvoiceBtn = document.getElementById('save-invoice');
    if (saveInvoiceBtn) {
        saveInvoiceBtn.addEventListener('click', function() {
            alert('DEBUG: save-invoice button clicked');
            console.log('DEBUG: save-invoice button clicked');
            saveInvoice(false);
        });
    }
    const savePrintInvoiceBtn = document.getElementById('save-print-invoice');
    if (savePrintInvoiceBtn) {
        savePrintInvoiceBtn.addEventListener('click', function() {
            alert('DEBUG: save-print-invoice button clicked');
            console.log('DEBUG: save-print-invoice button clicked');
            saveInvoice(true);
        });
    }

    // Listen for manual edits to paid-amount
    const paidAmountInput = document.getElementById('paid-amount');
    if (paidAmountInput) {
        paidAmountInput.addEventListener('input', function() {
            paidAmountManuallyEdited = true;
        });
    }
}

// تحميل منتجات المجموعة
function loadCategoryProducts(categoryId) {
    fetch(`/api/products?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            displayProducts(data.products);
            document.getElementById('categories-section').classList.add('d-none');
            document.getElementById('products-section').classList.remove('d-none');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء تحميل المنتجات');
        });
}

// تحميل كل المنتجات
function loadAllProducts() {
    fetch('/api/products')
        .then(response => response.json())
        .then(data => {
            displayProducts(data.products);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء تحميل المنتجات');
        });
}

// البحث عن المنتجات
function searchProducts(term) {
    fetch(`/api/products?search=${encodeURIComponent(term)}`)
        .then(response => response.json())
        .then(data => {
            displayProducts(data.products);
            document.getElementById('categories-section').classList.add('d-none');
            document.getElementById('products-section').classList.remove('d-none');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء البحث عن المنتجات');
        });
}

// عرض المنتجات
function displayProducts(products) {
    const container = document.getElementById('products-container');
    container.innerHTML = '';

    products.forEach(product => {
        const div = document.createElement('div');
        div.className = 'col-md-2 col-sm-3 col-4';
        div.innerHTML = `
            <div class="card product-card" data-id="${product.id}">
                <div class="card-body p-2 text-center">
                    ${product.image 
                        ? `<img src="${product.image_url}" class="img-fluid mb-2">` 
                        : `<i class="fas fa-box fa-2x mb-2"></i>`
                    }
                    <div class="small">${product.name}</div>
                </div>
            </div>
        `;
        
        div.querySelector('.product-card').addEventListener('click', () => {
            selectedProduct = product;
            showProductUnits(product.id);
        });

        container.appendChild(div);
    });
}

// عرض وحدات المنتج
function showProductUnits(productId) {
    if (!productId) {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'لم يتم تحديد المنتج بشكل صحيح'
        });
        return;
    }

    fetch(`/sales/products/units/${productId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            logToLaravel('showProductUnits data', { productId, units: data.units });
            if (!data.success) {
                throw new Error(data.message || 'حدث خطأ في جلب بيانات الوحدات');
            }
            if (!data.units || !Array.isArray(data.units) || data.units.length === 0) {
                throw new Error('لا توجد وحدات متاحة لهذا المنتج');
            }
            if (data.units.length === 1) {
                let unit = data.units[0];
                if (!unit.price && unit.main_price) unit.price = unit.main_price;
                if (!unit.name && unit.unit_name) unit.name = unit.unit_name;
                if (!unit.stock && typeof unit.stock_quantity !== 'undefined') unit.stock = unit.stock_quantity;
                logToLaravel('Direct addProductToInvoice for single unit', { unit, productId });
                addProductToInvoice(unit, productId);
                return;
            }

            const tbody = document.getElementById('units-table');
            tbody.innerHTML = '';
            data.units.forEach(unit => {
                // Ensure required fields for modal
                if (!unit.price && unit.main_price) unit.price = unit.main_price;
                if (!unit.name && unit.unit_name) unit.name = unit.unit_name;
                if (!unit.stock && typeof unit.stock_quantity !== 'undefined') unit.stock = unit.stock_quantity;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${unit.name}</td>
                    <td>${unit.stock || 0}</td>
                    <td>${unit.price}</td>
                    <td>
                        <button class="btn btn-sm btn-primary select-unit" 
                                data-unit-id="${unit.id}"
                                data-price="${unit.price}"
                                ${unit.stock <= 0 ? 'disabled' : ''}>
                            ${unit.stock <= 0 ? 'نفذت الكمية' : 'اختيار'}
                        </button>
                    </td>
                `;
                console.log('DEBUG: Attaching select-unit event for unit', unit);
                tr.querySelector('.select-unit')?.addEventListener('click', () => {
                    try {
                        addProductToInvoice(unit, productId);
                        unitsModal.hide();
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في إضافة المنتج',
                            text: error.message
                        });
                    }
                });
                tbody.appendChild(tr);
            });
            unitsModal.show();
        })
        .catch(error => {
            console.error('Error in showProductUnits:', error);
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: error.message || 'حدث خطأ أثناء تحميل وحدات المنتج'
            });
        });
}

// إضافة منتج للفاتورة
function addProductToInvoice(unit, productId) {
    // Fetch the full product object via AJAX, then call invoiceManager.addProductToInvoice
    fetch(`/sales/product/${productId}?price_type=retail`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.product) {
                invoiceManager.addProductToInvoice(data.product, unit);
        } else {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
                    text: 'تعذر جلب بيانات المنتج بالكامل لإضافته للفاتورة.'
                });
            }
        })
        .catch(error => {
                Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'حدث خطأ أثناء جلب بيانات المنتج.'
        });
    });
}

// حساب إجماليات الفاتورة
function calculateTotals() {
    // Use item.total if present, otherwise item.total_price
    let subtotal = window.cart.reduce((sum, item) => sum + (item.total !== undefined ? item.total : (item.total_price || 0)), 0);
    let discount = parseFloat(document.getElementById('discount').value) || 0;
    const discountType = document.getElementById('discount-type').value;
    if (discountType === 'percentage') {
        discount = (subtotal * discount) / 100;
    }
    const total = subtotal - discount;
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('total').textContent = total.toFixed(2);
    calculateRemaining();
    updateInvoiceSummary();
    // Reset manual edit flag when total changes
    paidAmountManuallyEdited = false;
}

// حساب المبلغ المتبقي
function calculateRemaining() {
    const total = parseFloat(document.getElementById('total').textContent);
    const paidAmount = parseFloat(document.getElementById('paid-amount').value) || 0;
    const remaining = total - paidAmount;

    document.getElementById('remaining').textContent = remaining.toFixed(2);
    updateInvoiceSummary();
}

// حفظ الفاتورة
function saveInvoice(printAfterSave) {
    // Ensure paid-amount is set to total if empty or zero, with strict rounding
    const paidAmountInput = document.getElementById('paid-amount');
    const total = parseFloat(document.getElementById('total').textContent) || 0;
    let paidAmount = parseFloat(paidAmountInput.value);
    if (isNaN(paidAmount) || paidAmount === 0) {
        paidAmount = Math.round(total * 100) / 100;
        paidAmountInput.value = paidAmount.toFixed(2);
    }
    alert('DEBUG: saveInvoice function called');
    console.log('DEBUG: saveInvoice function called', printAfterSave);
    console.log('DEBUG: cart before save', window.cart);
    const discountValue = parseFloat(document.getElementById('discount')?.value) || 0;
    const discountType = document.getElementById('discount-type')?.value || 'value';
    let discountPercentage = 0;
    let discountValueFinal = 0;
    if (discountType === 'percentage') {
        discountPercentage = discountValue;
        discountValueFinal = 0;
    } else {
        discountValueFinal = discountValue;
        discountPercentage = 0;
    }
    const invoiceType = document.getElementById('invoice-type').value;
    const totalInvoice = parseFloat(document.getElementById('total').textContent) || 0;
    console.log('DEBUG: invoiceType', invoiceType, 'total', totalInvoice, 'paidAmount', paidAmount, 'paidAmountInput.value', paidAmountInput.value);
    // Map cart to backend-required structure
    const items = window.cart.map(item => ({
        product_id: item.product_id || item.productId,
        unit_id: item.unit_id,
        quantity: item.quantity,
        unit_price: item.price,
        discount_value: item.discount_value || 0,
        discount_percentage: item.discount_percentage || 0
    }));
    console.log('DEBUG: mapped items for backend', items);
    // Frontend validation: must have at least one item
    if (items.length === 0) {
        alert('يجب إضافة منتج واحد على الأقل للفاتورة');
        return;
    }
    // Optionally, validate required fields for each item
    for (const item of items) {
        if (!item.product_id || !item.unit_id || !item.quantity || !item.unit_price) {
            alert('تأكد من أن جميع المنتجات تحتوي على بيانات صحيحة (المنتج، الوحدة، الكمية، السعر)');
            return;
        }
    }
    const invoiceData = {
        invoice_type: invoiceType,
        order_type: document.getElementById('order-type').value,
        customer_id: document.getElementById('customer-id').value || null,
        delivery_employee_id: document.getElementById('delivery-employee').value || null,
        price_type: document.getElementById('price-type').value,
        items: items,
        paid_amount: paidAmount,
        discount_value: discountValueFinal,
        discount_percentage: discountPercentage,
        notes: ''
    };
    console.log('DEBUG: invoiceData', invoiceData);
    fetch('/api/sales/invoices', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ invoice: invoiceData })
    })
    .then(response => {
        alert('DEBUG: fetch response received');
        console.log('DEBUG: fetch response received', response);
        return response.json();
    })
    .then(data => {
        alert('DEBUG: fetch response data: ' + JSON.stringify(data));
        console.log('DEBUG: fetch response data', data);
        if (data.success) {
            alert('تم حفظ الفاتورة بنجاح');
            if (printAfterSave) {
                // TODO: تنفيذ عملية الطباعة
            }
            // إعادة تعيين الفاتورة
            window.cart = [];
            calculateTotals();
        } else {
            alert('حدث خطأ: ' + data.message);
        }
    })
    .catch(error => {
        alert('DEBUG: fetch error: ' + error);
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ الفاتورة');
    });
}

// دالة مساعدة للتأخير
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

function logToLaravel(message, data) {
    fetch('/api/debug-log', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ message, data })
    });
}

window.saveInvoice = saveInvoice;

function updateInvoiceSummary() {
    console.log('DEBUG: updateInvoiceSummary called');
    // إجمالي المنتجات
    const productCount = window.cart.length;
    // إجمالي السعر
    const subtotal = window.cart.reduce((sum, item) => sum + (item.total !== undefined ? item.total : (item.total_price || 0)), 0);
    // خصم الفاتورة
    const discountInput = document.getElementById('discount');
    const discountTypeInput = document.getElementById('discount-type');
    let discount = parseFloat(discountInput?.value) || 0;
    const discountType = discountTypeInput?.value || 'value';
    let discountAmount = 0;
    if (discountType === 'percentage') {
        discountAmount = (subtotal * discount) / 100;
    } else {
        discountAmount = discount;
    }
    // المطلوب دفعه
    const totalDue = subtotal - discountAmount;
    // المدفوع
    const paidAmountInput = document.getElementById('paid-amount');
    let paidAmount = parseFloat(paidAmountInput?.value) || 0;
    // الباقي
    const remaining = totalDue - paidAmount;
    // Update UI
    const subtotalEl = document.getElementById('subtotal');
    if (subtotalEl) { subtotalEl.textContent = subtotal.toFixed(2); console.log('DEBUG: subtotal set to', subtotal.toFixed(2)); }
    const itemsCountEl = document.getElementById('items-count');
    if (itemsCountEl) { itemsCountEl.textContent = productCount; console.log('DEBUG: items-count set to', productCount); }
    const totalEl = document.getElementById('total');
    if (totalEl) { totalEl.textContent = totalDue.toFixed(2); console.log('DEBUG: total set to', totalDue.toFixed(2)); }
    const remainingEl = document.getElementById('remaining');
    if (remainingEl) { remainingEl.textContent = remaining.toFixed(2); console.log('DEBUG: remaining set to', remaining.toFixed(2)); }
    // If cash invoice, auto-fill paid amount
    const invoiceType = document.getElementById('invoice-type').value;
    if (invoiceType === 'cash') {
        if (paidAmountInput) { paidAmountInput.value = totalDue.toFixed(2); console.log('DEBUG: paid-amount set to', totalDue.toFixed(2)); }
        if (remainingEl) { remainingEl.textContent = '0.00'; console.log('DEBUG: remaining set to 0.00 (cash)'); }
    }
}

// Ensure updateInvoiceSummary is called on page load
updateInvoiceSummary(); 