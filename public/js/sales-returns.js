// Global variables
let selectedInvoice = null;
let returnItems = [];

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeEvents();
});

// Initialize event listeners
function initializeEvents() {
    // Search invoice button
    const searchInvoiceBtn = document.getElementById('search-invoice-btn');
    if (searchInvoiceBtn) {
        searchInvoiceBtn.addEventListener('click', searchInvoice);
    }

    // Invoice number input - search on enter
    const invoiceNumberInput = document.getElementById('invoice-number');
    if (invoiceNumberInput) {
        invoiceNumberInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchInvoice();
            }
        });
    }

    // Process return button
    const processReturnBtn = document.getElementById('process-return-btn');
    if (processReturnBtn) {
        processReturnBtn.addEventListener('click', function() {
            const returnMode = document.querySelector('input[name="return_mode"]:checked').value;
            processReturn(returnMode);
        });
    }
}

// Search for an invoice by number
function searchInvoice() {
    const invoiceNumber = document.getElementById('invoice-number').value.trim();
    if (!invoiceNumber) {
        showAlert('يرجى إدخال رقم الفاتورة', 'warning');
        return;
    }

    // Clear previous results
    resetReturnForm();

    // Show loading indicator
    const resultsContainer = document.getElementById('invoice-results');
    if (resultsContainer) {
        resultsContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> جاري البحث...</div>';
    }

    // Fetch invoice data
    fetchOriginalInvoice(invoiceNumber);
}

// Display invoice details in the UI
function displayInvoiceDetails(invoice) {
    const resultsContainer = document.getElementById('invoice-results');
    if (!resultsContainer) return;

    let html = `
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">تفاصيل الفاتورة #${invoice.invoice_number}</h5>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>العميل:</strong> ${invoice.customer_name || 'عميل نقدي'}
                    </div>
                    <div class="col-md-6">
                        <strong>التاريخ:</strong> ${invoice.date}
                    </div>
                </div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المنتج</th>
                            <th>الوحدة</th>
                            <th>الكمية</th>
                            <th>السعر</th>
                            <th>المرتجع سابقاً</th>
                            <th>المتاح للإرجاع</th>
                            <th>اختيار</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    invoice.items.forEach((item, index) => {
        const availableForReturn = item.quantity_sold - item.quantity_returned_previously;
        
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${item.product_name}</td>
                <td>${item.unit_name}</td>
                <td>${item.quantity_sold}</td>
                <td>${item.price}</td>
                <td>${item.quantity_returned_previously}</td>
                <td>${availableForReturn}</td>
                <td>
                    <div class="form-check">
                        <input class="form-check-input item-checkbox" type="checkbox" 
                               value="${item.id}" id="item-${item.id}"
                               data-product-id="${item.product_id}"
                               data-unit-id="${item.unit_id || 1}"
                               data-price="${item.price}"
                               data-product-name="${item.product_name}"
                               data-unit-name="${item.unit_name}"
                               data-available="${availableForReturn}"
                               ${availableForReturn <= 0 ? 'disabled' : ''}>
                        <label class="form-check-label" for="item-${item.id}">
                            اختيار
                        </label>
                    </div>
                </td>
            </tr>
        `;
    });

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    resultsContainer.innerHTML = html;

    // Add event listeners to checkboxes
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // If full return is selected, check all items
                if (document.getElementById('return-full').checked) {
                    document.querySelectorAll('.item-checkbox:not(:disabled)').forEach(cb => {
                        cb.checked = true;
                    });
                }
                
                // Show quantity input for partial returns
                if (document.getElementById('return-partial').checked) {
                    showReturnQuantityInputs();
                }
            } else {
                // If any item is unchecked, switch to partial return
                document.getElementById('return-partial').checked = true;
                
                // Show quantity inputs
                showReturnQuantityInputs();
            }
        });
    });

    // Add event listeners to return mode radio buttons
    document.querySelectorAll('input[name="return_mode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'full') {
                // Check all items for full return
                document.querySelectorAll('.item-checkbox:not(:disabled)').forEach(cb => {
                    cb.checked = true;
                });
                document.getElementById('return-quantities').classList.add('d-none');
            } else {
                // Show quantity inputs for partial return
                showReturnQuantityInputs();
            }
        });
    });
}

// Show quantity inputs for partial returns
function showReturnQuantityInputs() {
    const returnQuantitiesDiv = document.getElementById('return-quantities');
    if (!returnQuantitiesDiv) return;

    // Clear previous inputs
    returnQuantitiesDiv.innerHTML = '';
    
    // Get selected items
    const selectedItems = [];
    document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
        // Ensure unit_id is a valid number
        const unitId = parseInt(checkbox.dataset.unitId);
        if (isNaN(unitId)) {
            return;
        }
        
        selectedItems.push({
            id: checkbox.value,
            product_id: checkbox.dataset.productId,
            unit_id: unitId,
            product_name: checkbox.dataset.productName,
            unit_name: checkbox.dataset.unitName,
            price: parseFloat(checkbox.dataset.price),
            available: parseFloat(checkbox.dataset.available)
        });
    });

    if (selectedItems.length === 0) {
        returnQuantitiesDiv.classList.add('d-none');
        return;
    }

    // Create inputs for each selected item
    let html = `
        <h5 class="mt-3">تحديد الكميات المرتجعة</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>الوحدة</th>
                        <th>السعر</th>
                        <th>المتاح</th>
                        <th>الكمية المرتجعة</th>
                        <th>سبب الإرجاع</th>
                    </tr>
                </thead>
                <tbody>
    `;

    selectedItems.forEach(item => {
        html += `
            <tr>
                <td>${item.product_name}</td>
                <td>${item.unit_name}</td>
                <td>${item.price}</td>
                <td>${item.available}</td>
                <td>
                    <input type="number" class="form-control return-quantity" 
                           id="return-quantity-${item.id}" 
                           data-item-id="${item.id}"
                           data-product-id="${item.product_id}"
                           data-unit-id="${item.unit_id}"
                           min="0.01" max="${item.available}" step="0.01" value="1">
                </td>
                <td>
                    <select class="form-control return-reason" id="reason-${item.id}">
                        <option value="defective">معيب</option>
                        <option value="wrong_item">صنف خاطئ</option>
                        <option value="customer_request">طلب العميل</option>
                        <option value="other">أخرى</option>
                    </select>
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
        <div class="form-group mt-3">
            <label for="return-notes">ملاحظات الإرجاع</label>
            <textarea class="form-control" id="return-notes" rows="2"></textarea>
        </div>
        <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="restock-items" checked>
            <label class="form-check-label" for="restock-items">
                إعادة المنتجات للمخزون
            </label>
        </div>
    `;

    returnQuantitiesDiv.innerHTML = html;
    returnQuantitiesDiv.classList.remove('d-none');

    // Add event listeners to quantity inputs
    document.querySelectorAll('.return-quantity').forEach(input => {
        input.addEventListener('change', function() {
            const max = parseFloat(this.getAttribute('max'));
            const value = parseFloat(this.value);
            if (value > max) {
                this.value = max;
                showAlert(`الكمية المرتجعة لا يمكن أن تتجاوز ${max}`, 'warning');
            } else if (value <= 0) {
                this.value = 0.01;
            }
        });
    });
}

// Process the return based on selected mode
function processReturn(mode) {
    if (!selectedInvoice) {
        showAlert('يرجى البحث عن فاتورة أولاً', 'warning');
        return;
    }

    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    if (selectedItems.length === 0) {
        showAlert('يرجى اختيار صنف واحد على الأقل للإرجاع', 'warning');
        return;
    }

    // Get user ID from hidden field or default to 1
    const userId = document.getElementById('user-id') ? 
                  document.getElementById('user-id').value : 1;

    // Get notes
    const notes = document.getElementById('return-notes') ? 
                 document.getElementById('return-notes').value : '';
    
    // Get restock option
    const shouldRestock = document.getElementById('restock-items') ? 
                         document.getElementById('restock-items').checked : true;

    // For full invoice return
    if (mode === 'full') {
        const confirmFullReturn = confirm('هل أنت متأكد من إرجاع الفاتورة بالكامل؟');
        if (!confirmFullReturn) return;

        // Show loading
        document.getElementById('process-return-btn').disabled = true;
        document.getElementById('process-return-btn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';

        // API call for full invoice return
        fetch('/api/sales-returns/invoice/full', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                invoice_id: selectedInvoice.id,
                notes: notes,
                restock_items: shouldRestock,
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('process-return-btn').disabled = false;
            document.getElementById('process-return-btn').innerHTML = 'معالجة المرتجع';

            if (data.success) {
                showAlert('تم إرجاع الفاتورة بنجاح', 'success');
                resetReturnForm();
                // Optionally redirect to a receipt or confirmation page
            } else {
                showAlert(data.message || 'حدث خطأ أثناء معالجة المرتجع', 'error');
            }
        })
        .catch(error => {
            document.getElementById('process-return-btn').disabled = false;
            document.getElementById('process-return-btn').innerHTML = 'معالجة المرتجع';
            showAlert('حدث خطأ أثناء معالجة المرتجع', 'error');
        });
    } 
    // For partial invoice return
    else {
        // Collect return items data
        const returnItems = [];
        let isValid = true;

        selectedItems.forEach(checkbox => {
            const itemId = checkbox.value;
            const productId = parseInt(checkbox.dataset.productId);
            const unitId = parseInt(checkbox.dataset.unitId);
            const price = parseFloat(checkbox.dataset.price);
            const available = parseFloat(checkbox.dataset.available);
            
            // Validate unit_id
            if (isNaN(unitId)) {
                showAlert(`وحدة القياس غير صالحة للمنتج ${checkbox.dataset.productName}`, 'error');
                isValid = false;
                return;
            }
            
            // Get quantity input
            const quantityInput = document.getElementById(`return-quantity-${itemId}`);
            if (!quantityInput) {
                isValid = false;
                return;
            }
            
            const quantity = parseFloat(quantityInput.value);
            
            // Validate quantity
            if (isNaN(quantity) || quantity <= 0) {
                showAlert(`يرجى إدخال كمية صالحة للمنتج ${checkbox.dataset.productName}`, 'warning');
                isValid = false;
                return;
            }
            
            if (quantity > available) {
                showAlert(`الكمية المدخلة للمنتج ${checkbox.dataset.productName} أكبر من المتاح للإرجاع`, 'warning');
                isValid = false;
                return;
            }
            
            // Add to return items
            returnItems.push({
                product_id: productId,
                unit_id: unitId, // This is units.id, the controller will convert it to product_units.id
                quantity: quantity,
                unit_price: price
            });
        });
        
        if (!isValid) return;
        
        const confirmPartialReturn = confirm(`هل أنت متأكد من إرجاع ${returnItems.length} صنف من الفاتورة؟`);
        if (!confirmPartialReturn) return;

        // Show loading
        document.getElementById('process-return-btn').disabled = true;
        document.getElementById('process-return-btn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';

        // Prepare payload
        const payload = {
            invoice_id: selectedInvoice.id,
            notes: notes,
            restock_items: shouldRestock,
            items: returnItems,
            user_id: userId
        };

        // API call for partial invoice return
        fetch('/api/sales-returns/invoice/partial', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('process-return-btn').disabled = false;
            document.getElementById('process-return-btn').innerHTML = 'معالجة المرتجع';

            if (data.success) {
                showAlert('تم إرجاع الأصناف المحددة بنجاح', 'success');
                resetReturnForm();
                // Optionally redirect to a receipt or confirmation page
            } else {
                showAlert(data.message || 'حدث خطأ أثناء معالجة المرتجع', 'error');
            }
        })
        .catch(error => {
            document.getElementById('process-return-btn').disabled = false;
            document.getElementById('process-return-btn').innerHTML = 'معالجة المرتجع';
            showAlert(error.message || 'حدث خطأ أثناء معالجة المرتجع', 'error');
        });
    }
}

// Reset the return form
function resetReturnForm() {
    selectedInvoice = null;
    returnItems = [];
    
    const resultsContainer = document.getElementById('invoice-results');
    if (resultsContainer) {
        resultsContainer.innerHTML = '';
    }
    
    const returnOptions = document.getElementById('return-options');
    if (returnOptions) {
        returnOptions.classList.add('d-none');
    }
    
    const returnQuantities = document.getElementById('return-quantities');
    if (returnQuantities) {
        returnQuantities.classList.add('d-none');
    }
}

// Show alert message
function showAlert(message, type = 'info') {
    // Use SweetAlert2 if available
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: type === 'error' ? 'خطأ' : (type === 'success' ? 'نجاح' : 'تنبيه'),
            text: message,
            icon: type,
            confirmButtonText: 'موافق'
        });
    } else {
        // Fallback to basic alert
        alert(message);
    }
}

// Return a single item directly (not from an invoice)
function returnSingleItem(productId, unitId, quantity, price, notes = '') {
    if (!productId || !quantity) {
        showAlert('يرجى تحديد المنتج والكمية', 'warning');
        return;
    }

    // Validate unit_id
    if (unitId === null || unitId === undefined || isNaN(parseInt(unitId))) {
        showAlert('وحدة القياس غير صالحة', 'error');
        return;
    }

    // Get user ID from hidden field or default to 1
    const userId = document.getElementById('user-id') ? 
                  document.getElementById('user-id').value : 1;

    // Get restock option
    const shouldRestock = document.getElementById('restock-items')?.checked ?? true;

    // Prepare data
    const data = {
        product_id: parseInt(productId),
        unit_id: parseInt(unitId),
        quantity: parseFloat(quantity),
        unit_price: parseFloat(price),
        notes: notes,
        restock_items: shouldRestock,
        user_id: userId
    };

    // Show loading
    const submitButton = document.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';
    }

    // Send API request
    fetch('/api/sales-returns/item', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(errorData => {
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = 'إرجاع المنتج';
        }

        if (data.success) {
            showAlert('تم إرجاع المنتج بنجاح', 'success');
            // Reset form or redirect as needed
            document.querySelector('form')?.reset();
        } else {
            showAlert(data.message || 'حدث خطأ أثناء معالجة المرتجع', 'error');
        }
    })
    .catch(error => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = 'إرجاع المنتج';
        }
        showAlert(error.message || 'حدث خطأ أثناء معالجة المرتجع', 'error');
    });
}

// Make functions available globally
window.searchInvoice = searchInvoice;
window.processReturn = processReturn;
window.returnSingleItem = returnSingleItem;

function fetchOriginalInvoice(invoiceNumber) {
    showLoading('جاري البحث عن الفاتورة...');
    
    const fetchUrl = `/api/sales/invoices/by-number/${invoiceNumber}`;
    console.log('Fetching invoice from URL:', fetchUrl); // Log the URL

    // Actual API call using the new endpoint
    fetch(fetchUrl, { 
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        credentials: 'same-origin' // Include cookies in the request
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Invoice data received:", data); // for debugging
            if (data.success && data.invoice) {
                selectedInvoice = data.invoice; // Correctly access the nested invoice object
                displayInvoiceDetails(selectedInvoice);
                document.getElementById('return-options').classList.remove('d-none');
            } else {
                showAlert(data.message || 'لم يتم العثور على الفاتورة', 'danger');
                resetReturnForm();
            }
        })
        .catch(error => {
            showAlert('حدث خطأ أثناء البحث عن الفاتورة', 'error');
            resetReturnForm();
        });
} 