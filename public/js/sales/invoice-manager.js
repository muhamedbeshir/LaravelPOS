/**
 * Invoice Manager - Handles all invoice-related operations in the POS system
 * 
 * This module handles:
 * - Adding products to invoice
 * - Updating quantities and prices
 * - Calculating totals
 * - Processing discounts
 * - Managing invoice operations (save, print, suspend)
 */

export default class InvoiceManager {
    constructor() {
        // DOM Elements
        this.invoiceTable = document.getElementById('invoice-items');
        this.subtotalElement = document.getElementById('subtotal');
        this.totalElement = document.getElementById('total');
        this.itemsCountElement = document.getElementById('items-count');
        this.totalProfitElement = document.getElementById('total-profit');
        this.profitPercentageElement = document.getElementById('profit-percentage');
        this.discountInput = document.getElementById('discount');
        this.discountTypeSelect = document.getElementById('discount-type');
        this.paidAmountInput = document.getElementById('paid-amount');
        this.remainingElement = document.getElementById('remaining');
        
        // Totals in quick panel
        this.totalsFinalElement = document.getElementById('totals-final');
        this.totalsSubtotalElement = document.getElementById('totals-subtotal');
        this.totalsItemsElement = document.getElementById('totals-items');
        this.totalsDiscountElement = document.getElementById('totals-discount');
        
        // Invoice operation buttons
        this.saveInvoiceBtn = document.getElementById('save-invoice');
        this.savePrintInvoiceBtn = document.getElementById('save-print-invoice');
        this.suspendInvoiceBtn = document.getElementById('suspend-invoice');
        this.quickSaveBtn = document.getElementById('quick-save');
        this.quickPrintBtn = document.getElementById('quick-print');
        this.quickSuspendBtn = document.getElementById('quick-suspend');
        
        // Store item counts
        this.itemCount = 0;
        
        // Bind events
        this.bindEvents();
    }

    /**
     * Initialize event listeners
     */
    bindEvents() {
        // Discount events
        this.discountInput.addEventListener('input', () => this.updateTotals());
        this.discountTypeSelect.addEventListener('change', () => this.updateTotals());
        
        // Paid amount events
        this.paidAmountInput.addEventListener('input', () => this.calculateRemaining());
        
        // Invoice operation button events
        if (this.saveInvoiceBtn) {
            this.saveInvoiceBtn.addEventListener('click', () => this.saveInvoice(false));
        }
        
        if (this.savePrintInvoiceBtn) {
            this.savePrintInvoiceBtn.addEventListener('click', () => this.saveInvoice(true));
        }
        
        if (this.suspendInvoiceBtn) {
            this.suspendInvoiceBtn.addEventListener('click', () => this.suspendInvoice());
        }
        
        // Quick buttons
        if (this.quickSaveBtn) {
            this.quickSaveBtn.addEventListener('click', () => this.saveInvoice(false));
        }
        
        if (this.quickPrintBtn) {
            this.quickPrintBtn.addEventListener('click', () => this.saveInvoice(true));
        }
        
        if (this.quickSuspendBtn) {
            this.quickSuspendBtn.addEventListener('click', () => this.suspendInvoice());
        }
    }

    /**
     * Add a product to the invoice
     * @param {Object} product - Product data
     * @param {Object} unit - Product unit data
     */
    addProductToInvoice(product, unit) {
        // --- DEBUGGING START ---
        console.log('--- Add Product to Invoice Triggered ---');
        console.log('Setting "allowNegativeInventory":', window.settings.allowNegativeInventory);
        console.log('Product Object:', JSON.parse(JSON.stringify(product)));
        console.log('Unit Object:', JSON.parse(JSON.stringify(unit)));
        // --- DEBUGGING END ---

        // Enforce negative inventory restriction (robust)
        let stockQty = 0;
        if (typeof unit.stock_quantity !== 'undefined') {
            stockQty = parseFloat(unit.stock_quantity) || 0;
        } else if (typeof unit.stock !== 'undefined') {
            stockQty = parseFloat(unit.stock) || 0;
        }
        if (!window.settings.allowNegativeInventory && stockQty <= 0) {
            if (typeof window.showError === 'function') {
                window.showError('لا يمكن بيع هذا المنتج لأن الكمية في المخزون غير كافية.');
            } else {
                alert('لا يمكن بيع هذا المنتج لأن الكمية في المخزون غير كافية.');
            }
            return;
        }
        
        // Close product units modal if open
        $('#product-units-modal').modal('hide');
        
        // Debug log to see input data
        console.log('Adding product to invoice:', product, unit);
        
        // Get existing row if this product-unit combination exists
        const existingRow = this.findExistingProductRow(product.id, unit.id);
        
        if (existingRow) {
            // If row exists, increment quantity
            const quantityInput = existingRow.querySelector('.quantity-input');
            const currentQuantity = parseFloat(quantityInput.value) || 0;
            quantityInput.value = currentQuantity + 1;
            
            // Trigger the input event to update calculations
            quantityInput.dispatchEvent(new Event('input'));
            
            // Add highlight effect
            existingRow.classList.add('highlight-animation');
            setTimeout(() => {
                existingRow.classList.remove('highlight-animation');
            }, 1000);
            
            // Ensure the row is visible by scrolling to it
            existingRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }
        
        // Create new row
        this.itemCount++;
        const row = this.invoiceTable.querySelector('tbody').insertRow();
        row.dataset.productId = product.id;
        row.dataset.productUnitId = unit.id;
        row.dataset.unitId = unit.unit_id;   // Correct ID from units table
        row.dataset.cost = unit.cost_price || 0;
        
        // Get unit name - handle different data structures
        let unitName = '';
        if (unit.unit && unit.unit.name) {
            unitName = unit.unit.name;
        } else if (unit.name) {
            unitName = unit.name;
        }
        
        // Create row HTML
        row.innerHTML = `
            <td class="align-middle text-center">
                ${product.image_url ? 
                    `<img src="${product.image_url}" alt="${product.name}" width="40" height="40" class="img-thumbnail">` :
                    '<i class="fas fa-box text-primary fa-2x"></i>'
                }
            </td>
            <td class="product-name">
                <div class="fw-bold">${product.name || 'منتج بدون اسم'}</div>
                <small class="text-muted">${unitName}</small>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm price-input" value="${this.getUnitPrice(unit).toFixed(2)}" min="0" step="0.01" readonly>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm quantity-input" value="1" min="0.01" step="0.01">
            </td>
            <td class="subtotal">${this.getUnitPrice(unit).toFixed(2)}</td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control form-control-sm discount-input" value="0" min="0">
                    <select class="form-select form-select-sm discount-type">
                        <option value="percentage">%</option>
                        <option value="fixed">جنيه</option>
                    </select>
                </div>
            </td>
            <td class="total">${this.getUnitPrice(unit).toFixed(2)}</td>
            <td class="profit-column" style="${!window.settings.showProfitInSalesTable ? 'display:none;' : ''}">0.00</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="invoiceManager.removeInvoiceRow(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        
        // Add event listeners to inputs in the new row
        const quantityInput = row.querySelector('.quantity-input');
        const priceInput = row.querySelector('.price-input');
        const discountInput = row.querySelector('.discount-input');
        const discountTypeSelect = row.querySelector('.discount-type');
        
        quantityInput.addEventListener('input', () => this.calculateRowTotal(row));
        priceInput.addEventListener('input', () => this.calculateRowTotal(row));
        discountInput.addEventListener('input', () => this.calculateRowTotal(row));
        discountTypeSelect.addEventListener('change', () => this.calculateRowTotal(row));
        
        // Calculate initial row total
        this.calculateRowTotal(row);
        
        // Update invoice totals
        this.updateTotals();
        
        // Scroll to the new row if needed
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Add highlight effect
        row.classList.add('highlight-animation');
        setTimeout(() => {
            row.classList.remove('highlight-animation');
        }, 1000);
        
        // Focus on quantity input for easy editing
        quantityInput.focus();
        quantityInput.select();
    }

    /**
     * Find existing row for a product-unit combination
     * @param {number} productId - Product ID
     * @param {number} productUnitId - Product Unit ID
     * @returns {Element|null} - Existing row element or null
     */
    findExistingProductRow(productId, productUnitId) {
        const rows = this.invoiceTable.querySelectorAll('tbody tr');
        for (const row of rows) {
            if (row.dataset.productId == productId && row.dataset.productUnitId == productUnitId) {
                return row;
            }
        }
        return null;
    }

    /**
     * Remove a row from the invoice
     * @param {Element} button - The remove button element
     */
    removeInvoiceRow(button) {
        const row = button.closest('tr');
        row.classList.add('fadeOut');
        
        // Use animation to remove row smoothly
        setTimeout(() => {
            row.remove();
            this.updateTotals();
        }, 300);
    }

    /**
     * Calculate total for a single row
     * @param {Element} row - The table row element
     */
    calculateRowTotal(row) {
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const discountValue = parseFloat(row.querySelector('.discount-input').value) || 0;
        const discountType = row.querySelector('.discount-type').value;

        const subtotal = price * quantity;
        let total = subtotal;
        let perItemDiscount = 0;

        if (discountType === 'percentage') {
            perItemDiscount = price * (discountValue / 100);
        } else { // fixed
            perItemDiscount = discountValue;
        }

        // Ensure discount doesn't exceed price
        if (perItemDiscount > price) {
            perItemDiscount = price;
            // Optionally, update the input to reflect the change
            if (discountType === 'fixed') {
                 row.querySelector('.discount-input').value = price.toFixed(2);
            }
        }
        
        total = (price - perItemDiscount) * quantity;

        row.querySelector('.subtotal').textContent = subtotal.toFixed(2);
        row.querySelector('.total').textContent = total.toFixed(2);

        this.calculateRowProfit(row);
        this.updateTotals();
    }

    /**
     * Calculate profit for a single row
     * @param {Element} row - The table row element
     */
    calculateRowProfit(row) {
        // Get values
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
        const discountType = row.querySelector('.discount-type').value;
        const cost = parseFloat(row.dataset.cost) || 0;
        
        // Cost validation
        let unitCost = cost;
        if (unitCost <= 0 || isNaN(unitCost)) {
            // Fallback cost as 70% of sale price if no cost available
            unitCost = price * 0.7;
        } else if (unitCost > price && price > 0) {
            // If cost is higher than price, use 70% of sale price
            unitCost = price * 0.7;
        }
        
        // Calculate subtotal and discount amount
        const subtotal = quantity * price;
        let discountAmount = 0;
        if (discountType === 'percentage') {
            discountAmount = subtotal * (discount / 100);
        } else {
            discountAmount = discount;
        }
        
        // Calculate total after discount
        const total = Math.max(subtotal - discountAmount, 0);
        
        // Calculate total cost
        const totalCost = quantity * unitCost;
        
        // Calculate profit
        const profit = total - totalCost;
        const profitCell = row.querySelector('.profit-column');
        
        profitCell.textContent = profit.toFixed(2);
        
        // Add color based on profit
        if (profit < 0) {
            profitCell.classList.remove('text-success');
            profitCell.classList.add('text-danger');
        } else {
            profitCell.classList.remove('text-danger');
            profitCell.classList.add('text-success');
        }
        
        return profit;
    }

    /**
     * Update all totals for the invoice
     */
    updateTotals() {
        const rows = this.invoiceTable.querySelectorAll('tbody tr');
        let subtotal = 0;
        let totalProfit = 0;
        let itemsCount = rows.length;
        
        // Calculate subtotal and profits from all rows
        rows.forEach(row => {
            subtotal += parseFloat(row.querySelector('.total').textContent) || 0;
            
            if (window.settings.showProfitInSalesTable || window.settings.showProfitInSummary) {
                totalProfit += parseFloat(row.querySelector('.profit-column').textContent) || 0;
            }
        });
        
        // Apply invoice level discount
        const invoiceDiscount = parseFloat(this.discountInput.value) || 0;
        const invoiceDiscountType = this.discountTypeSelect.value;
        
        let invoiceDiscountAmount = 0;
        if (invoiceDiscountType === 'percentage') {
            invoiceDiscountAmount = subtotal * (invoiceDiscount / 100);
        } else {
            invoiceDiscountAmount = invoiceDiscount;
        }
        
        // Calculate final total
        const total = Math.max(subtotal - invoiceDiscountAmount, 0);
        
        // Update profit percentage
        let profitPercentage = 0;
        if (total > 0) {
            profitPercentage = (totalProfit / total) * 100;
        }
        
        // Update DOM elements
        this.subtotalElement.textContent = subtotal.toFixed(2);
        this.totalElement.textContent = total.toFixed(2);
        this.itemsCountElement.textContent = itemsCount;
        
        // Update quick panel totals
        this.totalsSubtotalElement.textContent = subtotal.toFixed(2);
        this.totalsFinalElement.textContent = total.toFixed(2);
        this.totalsItemsElement.textContent = itemsCount;
        this.totalsDiscountElement.textContent = invoiceDiscountAmount.toFixed(2);
        
        // Update profit elements if shown
        if (window.settings.showProfitInSummary) {
            this.totalProfitElement.textContent = totalProfit.toFixed(2);
            this.profitPercentageElement.textContent = profitPercentage.toFixed(2) + '%';
            
            // Add color based on profit
            if (totalProfit < 0) {
                this.totalProfitElement.classList.remove('text-success');
                this.totalProfitElement.classList.add('text-danger');
                this.profitPercentageElement.classList.remove('text-success');
                this.profitPercentageElement.classList.add('text-danger');
            } else {
                this.totalProfitElement.classList.remove('text-danger');
                this.totalProfitElement.classList.add('text-success');
                this.profitPercentageElement.classList.remove('text-danger');
                this.profitPercentageElement.classList.add('text-success');
            }
        }
        
        // Calculate remaining amount if paid amount is entered
        this.calculateRemaining();
    }

    /**
     * Calculate the remaining amount based on paid amount
     */
    calculateRemaining() {
        const total = parseFloat(this.totalElement.textContent) || 0;
        const paidAmount = parseFloat(this.paidAmountInput.value) || 0;
        
        const remaining = paidAmount - total;
        this.remainingElement.textContent = remaining.toFixed(2);
        
        // Add color based on remaining amount
        if (remaining < 0) {
            this.remainingElement.classList.remove('text-success');
            this.remainingElement.classList.add('text-danger');
        } else {
            this.remainingElement.classList.remove('text-danger');
            this.remainingElement.classList.add('text-success');
        }
    }

    /**
     * Get the correct price for a unit based on price type
     * @param {Object} unit - Product unit data
     * @returns {number} - Price
     */
    getUnitPrice(unit) {
        const priceTypeCode = this.getPriceType();
        let price = 0;
        
        // Check if unit has prices array
        if (unit.prices && Array.isArray(unit.prices) && unit.prices.length > 0) {
            // Find price for the selected price type
            // The server returns prices as objects with price_type and value properties
            const priceObj = unit.prices.find(p => p.price_type_code === priceTypeCode || (p.price_type && p.price_type.code === priceTypeCode));
            if (priceObj) {
                // Price may be in .value or .price depending on API response format
                if (priceObj.value !== undefined && priceObj.value !== null) {
                    price = parseFloat(priceObj.value) || 0;
                } else if (priceObj.price !== undefined && priceObj.price !== null) {
                    price = parseFloat(priceObj.price) || 0;
                }
            } else {
                // Fallback to first price in the list if it exists
                const firstPrice = unit.prices[0];
                if (firstPrice) {
                    if (firstPrice.value !== undefined && firstPrice.value !== null) {
                        price = parseFloat(firstPrice.value) || 0;
                    } else if (firstPrice.price !== undefined && firstPrice.price !== null) {
                        price = parseFloat(firstPrice.price) || 0;
                    }
                }
            }
        }
        
        // If we still have no price, check if there's a direct price property
        if (price === 0 && unit.price !== undefined && unit.price !== null) {
            price = parseFloat(unit.price) || 0;
        }
        
        return price;
    }

    /**
     * Get the current price type
     * @returns {string} - Price type code
     */
    getPriceType() {
        const priceTypeSelect = document.getElementById('price-type');
        return priceTypeSelect ? priceTypeSelect.value : 'retail';
    }

    /**
     * Save the invoice
     * @param {boolean} printAfterSave - Whether to print after saving
     */
    saveInvoice(printAfterSave = false) {
        // Validate invoice
        if (this.invoiceTable.querySelectorAll('tbody tr').length === 0) {
            showError('لا يمكن حفظ فاتورة فارغة');
            return;
        }
        
        const invoiceType = document.getElementById('invoice-type').value;
        const customerId = document.getElementById('customer-id').value;
        
        // Ensure credit invoices have a valid customer (not cash customer and not empty)
        if (invoiceType === 'credit') {
            if (!customerId || customerId === '1') {
                showError('يجب اختيار عميل للفواتير الآجلة');
                return;
            }
            
            // Check credit limit for credit invoices
            const selectedOption = document.querySelector(`#customer-id option[value="${customerId}"]`);
            const currentCredit = parseFloat(selectedOption.dataset.credit) || 0;
            const creditLimit = parseFloat(selectedOption.dataset.creditLimit) || 0;
            
            // Access the data attribute directly from the DOM element to avoid jQuery's data caching
            const isUnlimitedCredit = selectedOption && selectedOption.getAttribute('data-is-unlimited-credit') === '1';
            
            const invoiceTotal = parseFloat(this.totalElement.textContent) || 0;
            const paidAmount = parseFloat(this.paidAmountInput.value) || 0;
            const remainingAmount = invoiceTotal - paidAmount;
            
            // Calculate new balance after this invoice
            const newBalance = currentCredit + remainingAmount;
            
            // Check if the new balance exceeds credit limit (only if the customer doesn't have unlimited credit)
            if (!isUnlimitedCredit && newBalance > creditLimit) {
                // Show more detailed error message
                const availableCredit = creditLimit - currentCredit;
                const exceedAmount = newBalance - creditLimit;
                
                showError(`
                    <strong>تجاوز حد الائتمان المسموح به</strong><br>
                    الرصيد الحالي: ${currentCredit.toFixed(2)}<br>
                    حد الائتمان: ${creditLimit.toFixed(2)}<br>
                    الائتمان المتاح: ${availableCredit.toFixed(2)}<br>
                    المبلغ المتبقي: ${remainingAmount.toFixed(2)}<br>
                    المبلغ الزائد: ${exceedAmount.toFixed(2)}<br><br>
                    <u>الحلول الممكنة:</u><br>
                    1. زيادة المبلغ المدفوع<br>
                    2. تقليل إجمالي الفاتورة<br>
                    3. زيادة حد الائتمان للعميل
                `);
                return;
            }
        }
        
        const orderType = document.getElementById('order-type').value;
        if (orderType === 'delivery' && !document.getElementById('delivery-employee').value) {
            showError('يجب اختيار موظف التوصيل');
            return;
        }

        // إعداد بيانات الدفعات للفاتورة متعددة الدفعات
        let paymentsArray = [];
        if (invoiceType === 'mixed') {
            paymentsArray = window.collectMixedPayments();

            if (paymentsArray.length < 2) {
                showError('يجب إضافة دفعتين على الأقل');
                return;
            }

            // تحقق من تطابق المبالغ مع الإجمالي
            const sumPayments = paymentsArray.reduce((acc, cur) => acc + (parseFloat(cur.amount || cur.amount) || 0), 0);
            const invoiceTotalCurrent = parseFloat(this.totalElement.textContent) || 0;
            if (Math.abs(sumPayments - invoiceTotalCurrent) > 0.01) {
                showError('إجمالي مبالغ الدفعات لا يطابق إجمالي الفاتورة');
                return;
            }
        }

        // Prepare invoice data
        const invoiceData = {
            invoice_type: invoiceType,
            order_type: orderType,
            customer_id: customerId,
            discount_value: this.discountTypeSelect.value === 'percentage' ? 0 : parseFloat(this.discountInput.value) || 0,
            discount_percentage: this.discountTypeSelect.value === 'percentage' ? parseFloat(this.discountInput.value) || 0 : 0,
            paid_amount: parseFloat(this.paidAmountInput.value) || 0,
            price_type: this.getPriceType(),
            delivery_employee_id: orderType === 'delivery' ? document.getElementById('delivery-employee').value : null,
            items: [],
            payments: paymentsArray
        };
        
        // Add items to invoice
        this.invoiceTable.querySelectorAll('tbody tr').forEach(row => {
            const productId = row.dataset.productId;
            const unitId = row.dataset.productUnitId;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const discountValue = row.querySelector('.discount-type').value === 'percentage' ? 0 : parseFloat(row.querySelector('.discount-input').value) || 0;
            const discountPercentage = row.querySelector('.discount-type').value === 'percentage' ? parseFloat(row.querySelector('.discount-input').value) || 0 : 0;
            
            invoiceData.items.push({
                product_id: productId,
                unit_id: unitId,
                unit_price: price,
                quantity: quantity,
                discount_value: discountValue,
                discount_percentage: discountPercentage
            });
        });
        
        // Show loading indicator
        Swal.fire({
            title: 'جاري الحفظ...',
            text: 'برجاء الانتظار',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Save invoice
        fetch('/sales/invoices', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                invoice: invoiceData
            })
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            
            if (data.success) {
                if (printAfterSave) {
                    // Open print window
                    window.open(`/sales/invoices/${data.invoice.id}/print`, '_blank');
                }
                
                // If a suspended sale was resumed, delete it
                if (window.g_resumedSuspendedSaleId) {
                    const resumedId = window.g_resumedSuspendedSaleId;
                    window.g_resumedSuspendedSaleId = null; // Clear it immediately
                    
                    fetch(`/sales/suspended-sales/${resumedId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                }
                
                // Reset form
                this.resetInvoice();
                
                showSuccess('تم حفظ الفاتورة بنجاح');
                
                // تحديث رقم الفاتورة الحالية
                if (window.loadCurrentInvoiceNumber) {
                    window.loadCurrentInvoiceNumber();
                }
            } else {
                showError(data.message || 'حدث خطأ في حفظ الفاتورة');
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error saving invoice:', error);
            showError('حدث خطأ في حفظ الفاتورة');
        });
    }

    /**
     * Suspend the current invoice
     */
    suspendInvoice() {
        if (this.invoiceTable.querySelectorAll('tbody tr').length === 0) {
            showError('لا يمكن تعليق فاتورة فارغة');
            return;
        }
        
        const customerId = document.getElementById('customer-id').value;
        const totalAmount = parseFloat(document.getElementById('total').textContent) || 0;
        const orderType = document.getElementById('order-type').value;

        const invoiceData = {
            customer_id: customerId,
            invoice_type: document.getElementById('invoice-type').value,
            order_type: orderType,
            discount_value: this.discountTypeSelect.value === 'fixed' ? (parseFloat(this.discountInput.value) || 0) : 0,
            discount_percentage: this.discountTypeSelect.value === 'percentage' ? (parseFloat(this.discountInput.value) || 0) : 0,
            price_type_code: this.getPriceType(),
            total_amount: totalAmount,
            paid_amount: 0, 
            notes: '',
            delivery_employee_id: orderType === 'delivery' ? document.getElementById('delivery-employee').value : null,
            items: []
        };
        
        if (orderType === 'delivery' && !invoiceData.delivery_employee_id) {
            showError('يجب اختيار موظف التوصيل لطلبات الدليفري');
            return;
        }

        this.invoiceTable.querySelectorAll('tbody tr').forEach(row => {
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const discountInput = row.querySelector('.discount-input');
            const discountType = row.querySelector('.discount-type');
            const discountValue = parseFloat(discountInput.value) || 0;
            
            let itemDiscountAmount = 0;
            let discount_percentage = 0;
            let discount_value = 0;

            if (discountType.value === 'fixed') {
                itemDiscountAmount = discountValue;
                discount_value = discountValue;
            } else {
                itemDiscountAmount = (price * quantity) * (discountValue / 100);
                discount_percentage = discountValue;
            }
            
            const subTotal = (price * quantity) - itemDiscountAmount;
            
            invoiceData.items.push({
                product_id: row.dataset.productId,
                unit_id: row.dataset.unitId,
                unit_price: price,
                quantity: quantity,
                discount_value: discount_value,
                discount_percentage: discount_percentage,
                sub_total: subTotal
            });
        });
        
        showLoading();

        fetch('/api/suspended-sales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(invoiceData)
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                this.resetInvoice();
                showSuccess('تم تعليق الفاتورة بنجاح');
                if (window.suspendedSalesManager) {
                    window.suspendedSalesManager.loadSuspendedSales();
                }
            } else {
                let errorMessage = data.message || 'حدث خطأ في تعليق الفاتورة';
                if (data.errors) {
                    errorMessage += '<br><ul class="text-start">';
                    for (const key in data.errors) {
                        errorMessage += `<li>${data.errors[key].join(', ')}</li>`;
                    }
                    errorMessage += '</ul>';
                }
                showError(errorMessage);
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error suspending invoice:', error);
            showError('حدث خطأ في تعليق الفاتورة');
        });
    }

    /**
     * Reset the invoice
     */
    resetInvoice(options = {}) {
        // Clear invoice items
        this.invoiceTable.querySelector('tbody').innerHTML = '';
        
        // Reset totals
        this.subtotalElement.textContent = '0.00';
        this.itemsCountElement.textContent = '0';
        this.totalElement.textContent = '0.00';
        
        // Reset profit information
        this.totalProfitElement.textContent = '0.00';
        this.profitPercentageElement.textContent = '0%';
        this.totalProfitElement.classList.remove("text-danger").add("text-success");
        this.profitPercentageElement.classList.remove("text-danger").add("text-success");
        
        // Reset form fields
        this.discountInput.value = '0';
        this.paidAmountInput.value = '';
        this.remainingElement.textContent = '0.00';
        
        // Reset selects to defaults
        document.getElementById('invoice-type').value = 'cash';
        document.getElementById('order-type').value = 'takeaway';
        
        // Use settings for default price type if available, otherwise fallback
        const defaultPrice = window.settings.defaultPriceType || (document.querySelector('#price-type option:first') ? document.querySelector('#price-type option:first').value : 'retail');
        if (document.getElementById('price-type')) {
            document.getElementById('price-type').value = defaultPrice;
        }
        
        document.getElementById('customer-id').value = '1';
        // Trigger change events
        document.getElementById('customer-id').dispatchEvent(new Event('change'));
        
        if (document.getElementById('delivery-employee')) {
            document.getElementById('delivery-employee').value = '';
            document.getElementById('delivery-employee').dispatchEvent(new Event('change'));
        }
        
        // Reset UI states
        window.handleInvoiceTypeChange();
        window.handleOrderTypeChange();
        this.updateTotals();
        
        // Reset item counter
        this.itemCount = 0;
        
        // Reset resumed sale ID if not keeping it
        if (!options.keepResumedId) {
            window.g_resumedSuspendedSaleId = null;
        }
    }

    /**
     * Load a suspended sale
     * @param {number} suspendedSaleId - Suspended sale ID
     */
    loadSuspendedSale(suspendedSaleId) {
        // Show loading indicator
        Swal.fire({
            title: 'جاري تحميل الفاتورة المعلقة...',
            text: 'برجاء الانتظار',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Fetch suspended sale
        fetch(`/sales/suspended-sales/${suspendedSaleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset current invoice
                this.resetInvoice({ keepResumedId: true });
                
                // Set resumed ID
                window.g_resumedSuspendedSaleId = suspendedSaleId;
                
                // Set customer
                document.getElementById('customer-id').value = data.suspended_sale.customer_id;
                document.getElementById('customer-id').dispatchEvent(new Event('change'));
                
                // Set price type if it exists
                if (data.suspended_sale.price_type && document.getElementById('price-type')) {
                    document.getElementById('price-type').value = data.suspended_sale.price_type;
                }
                
                // Load items
                const promises = [];
                data.suspended_sale.items.forEach(item => {
                    // Fetch product details for each item
                    const promise = fetch(`/sales/product/${item.product_id}?price_type=${this.getPriceType()}`)
                        .then(response => response.json())
                        .then(productData => {
                            if (productData.success) {
                                // Find the correct unit
                                const unit = productData.product.units.find(u => u.id === item.unit_id);
                                if (unit) {
                                    // Add to invoice
                                    this.addProductToInvoice(productData.product, unit);
                                    
                                    // Update the newly added row with saved values
                                    const rows = this.invoiceTable.querySelectorAll('tbody tr');
                                    const row = rows[rows.length - 1]; // Get the last row added
                                    
                                    if (row) {
                                        const priceInput = row.querySelector('.price-input');
                                        const quantityInput = row.querySelector('.quantity-input');
                                        const discountInput = row.querySelector('.discount-input');
                                        const discountTypeSelect = row.querySelector('.discount-type');
                                        
                                        // Set values
                                        priceInput.value = item.unit_price;
                                        quantityInput.value = item.quantity;
                                        
                                        if (item.discount_percentage > 0) {
                                            discountInput.value = item.discount_percentage;
                                            discountTypeSelect.value = 'percentage';
                                        } else {
                                            discountInput.value = item.discount_value;
                                            discountTypeSelect.value = 'fixed';
                                        }
                                        
                                        // Recalculate totals
                                        this.calculateRowTotal(row);
                                    }
                                }
                            }
                        });
                    
                    promises.push(promise);
                });
                
                // Wait for all products to be added
                Promise.all(promises).then(() => {
                    // Close suspended sales modal and loading indicator
                    $('#suspended-sales-modal').modal('hide');
                    Swal.close();
                    
                    showSuccess('تم استرجاع الفاتورة المعلقة بنجاح');
                });
            } else {
                Swal.close();
                showError(data.message || 'حدث خطأ في تحميل الفاتورة المعلقة');
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error loading suspended sale:', error);
            showError('حدث خطأ في تحميل الفاتورة المعلقة');
        });
    }
}

// Initialize as a global object for event handlers
window.invoiceManager = new InvoiceManager(); 