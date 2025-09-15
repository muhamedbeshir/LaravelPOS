/**
 * POS System - Main JavaScript File
 * 
 * This is the entry point for the Sales/POS system JavaScript.
 * It imports and initializes all modules, and sets up global functions.
 */

// Import modules
import InvoiceManager from './invoice-manager.js';
import ProductManager from './product-manager.js';
import SuspendedSalesManager from './suspended-sales-manager.js';

// Global utility functions
window.showError = function(message) {
    Swal.fire({
        icon: 'error',
        title: 'خطأ',
        html: message,
        confirmButtonText: 'حسناً',
        timer: 3000,
        timerProgressBar: true
    });
};

window.showSuccess = function(message) {
    Swal.fire({
        icon: 'success',
        title: 'نجاح',
        text: message,
        confirmButtonText: 'حسناً',
        timer: 2000,
        timerProgressBar: true
    });
};

// دالة تحميل رقم الفاتورة الحالية
window.loadCurrentInvoiceNumber = function() {
    console.log('بدء استدعاء رقم الفاتورة...');
    
    // عرض قيم مؤقتة أثناء التحميل
    var invoiceElement = document.getElementById('current-invoice-number');
    var countElement = document.querySelector('.invoice-number-count');
    var referenceElement = document.getElementById('reference-invoice-number');
    
    if (invoiceElement) invoiceElement.textContent = '...';
    if (countElement) countElement.textContent = '...';
    if (referenceElement) referenceElement.textContent = '...';
    
    // استدعاء API للحصول على رقم الفاتورة الحالية
    fetch('/sales/current-invoice-number')
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP error, status = ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            console.log('تم استلام البيانات:', data);
            
            // عرض البيانات الواردة بغض النظر عن حالة النجاح
            // عرض رقم الفاتورة الجديد (في الوردية)
            if (data.invoice_number !== undefined && invoiceElement) {
                invoiceElement.textContent = data.invoice_number;
            } else if (invoiceElement) {
                invoiceElement.textContent = '1';
                console.warn('لم يتم استلام رقم الفاتورة في الوردية');
            }
            
            // عرض الرقم المرجعي القديم
            if (data.reference_number && referenceElement) {
                referenceElement.textContent = data.reference_number;
            } else if (referenceElement) {
                referenceElement.textContent = data.next_invoice_number || '-';
            }
            
            // عرض عدد الفواتير
            if (data.invoice_count !== undefined && countElement) {
                countElement.textContent = data.invoice_count;
            } else if (countElement) {
                countElement.textContent = '0';
                console.warn('لم يتم استلام عدد الفواتير');
            }
            
            // تخزين بيانات الاستجابة للرجوع إليها لاحقًا
            window.currentInvoiceData = data;
        })
        .catch(function(error) {
            console.error('خطأ في الاستدعاء:', error.message);
            
            // عرض قيم افتراضية في حالة الخطأ
            if (invoiceElement) invoiceElement.textContent = '1';
            if (countElement) countElement.textContent = '0';
            if (referenceElement) referenceElement.textContent = '-';
        });
};

// Handle invoice type change
window.handleInvoiceTypeChange = function() {
    const invoiceTypeSelect = document.getElementById('invoice-type');
    if (!invoiceTypeSelect) return;

    const invoiceType = invoiceTypeSelect.value;
    const iconContainer = document.getElementById('mixed-payments-icon-container');
    if (!iconContainer) return;

    const cashFields = document.querySelectorAll('.cash-field');
    const creditFields = document.querySelectorAll('.credit-field');
    const customerSelect = document.getElementById('customer-id');
    const paidAmountParent = document.getElementById('paid-amount')?.parentElement;

    // Default state: hide the icon. We only show it for 'mixed'.
    iconContainer.classList.add('d-none');

    if (invoiceType === 'mixed') {
        iconContainer.classList.remove('d-none');
        
        if(paidAmountParent) paidAmountParent.classList.add('d-none');

        cashFields.forEach(f => f.classList.add('d-none'));
        creditFields.forEach(f => f.classList.add('d-none'));

    } else {
        if(paidAmountParent) paidAmountParent.classList.remove('d-none');

        if (invoiceType === 'cash') {
            cashFields.forEach(f => f.classList.remove('d-none'));
            creditFields.forEach(f => f.classList.add('d-none'));
            if (customerSelect) {
                customerSelect.value = '1';
                customerSelect.dispatchEvent(new Event('change'));
            }
        } else { // credit, visa, transfer
            cashFields.forEach(f => f.classList.add('d-none'));
            creditFields.forEach(f => f.classList.remove('d-none'));
        }
    }
};

// Handle order type change
window.handleOrderTypeChange = function() {
    const orderType = document.getElementById('order-type').value;
    const deliveryFields = document.querySelectorAll('.delivery-field');
    const deliveryButtons = document.getElementById('delivery-buttons-bar');
    const deliveryStatusBtn = document.getElementById('delivery-status-btn');
    
    if (orderType === 'delivery') {
        deliveryFields.forEach(field => field.classList.remove('d-none'));
        if (deliveryButtons) deliveryButtons.classList.remove('d-none');
        if (deliveryStatusBtn) deliveryStatusBtn.style.display = 'block';
    } else {
        deliveryFields.forEach(field => field.classList.add('d-none'));
        if (deliveryButtons) deliveryButtons.classList.add('d-none');
        if (deliveryStatusBtn) deliveryStatusBtn.style.display = 'none';
    }
};

// Initialize EventListeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modules on page load
    window.productManager = new ProductManager();
    window.invoiceManager = new InvoiceManager();
    window.suspendedSalesManager = new SuspendedSalesManager();
    
    // تحميل رقم الفاتورة الحالية عند بدء التطبيق
    if (window.loadCurrentInvoiceNumber) {
        window.loadCurrentInvoiceNumber();
    }
    
    // Set up invoice type change handler
    const invoiceTypeSelect = document.getElementById('invoice-type');
    if (invoiceTypeSelect) {
        invoiceTypeSelect.addEventListener('change', window.handleInvoiceTypeChange);
        window.handleInvoiceTypeChange(); // Initial call
    }
    
    // Set up order type change handler
    const orderTypeSelect = document.getElementById('order-type');
    if (orderTypeSelect) {
        orderTypeSelect.addEventListener('change', window.handleOrderTypeChange);
        window.handleOrderTypeChange(); // Initial call
    }
    
    // Set up customer change handler
    const customerSelect = document.getElementById('customer-id');
    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const creditBalanceElement = document.getElementById('customer-credit-balance');
            const creditLimitElement = document.getElementById('customer-credit-limit');
            const priceTypeSelect = document.getElementById('price-type');
            
            if (creditBalanceElement && selectedOption) {
                const creditBalance = selectedOption.dataset.credit || '0.00';
                creditBalanceElement.textContent = parseFloat(creditBalance).toFixed(2);
                
                // Change color based on balance
                if (parseFloat(creditBalance) > 0) {
                    creditBalanceElement.classList.remove('text-success');
                    creditBalanceElement.classList.add('text-danger');
                } else {
                    creditBalanceElement.classList.remove('text-danger');
                    creditBalanceElement.classList.add('text-success');
                }
            }
            
            if (creditLimitElement && selectedOption) {
                const creditLimit = selectedOption.dataset.creditLimit || '0.00';
                const isUnlimited = selectedOption.dataset.isUnlimitedCredit === '1';
                
                creditLimitElement.textContent = isUnlimited ? 'غير محدود' : parseFloat(creditLimit).toFixed(2);
            }
            
            // Handle customer's default price type
            if (priceTypeSelect && selectedOption) {
                // IMPORTANT: HTML data attributes are converted to camelCase in JavaScript
                // data-default-price-type-code becomes dataset.defaultPriceTypeCode
                const customerDefaultPriceTypeCode = selectedOption.getAttribute('data-default-price-type-code');
                
                console.log('Customer changed. Selected option:', selectedOption);
                console.log('Default Price Type Code (using getAttribute):', customerDefaultPriceTypeCode);
                console.log('Default Price Type Code (using dataset):', selectedOption.dataset.defaultPriceTypeCode);
                console.log('All data attributes:', Object.keys(selectedOption.dataset).map(key => `${key}: ${selectedOption.dataset[key]}`));
                console.log('Price type options:', Array.from(priceTypeSelect.options).map(opt => `${opt.value}: ${opt.text} (default: ${opt.dataset.isDefault})`));

                if (customerDefaultPriceTypeCode && customerDefaultPriceTypeCode !== '') {
                    // Customer has a specific default price type
                    let priceTypeFound = false;
                    for (let option of priceTypeSelect.options) {
                        console.log(`Comparing price type option ${option.value} with customer default ${customerDefaultPriceTypeCode}`);
                        if (option.value === customerDefaultPriceTypeCode) {
                            console.log(`Match found! Setting price type to ${option.text}`);
                            priceTypeSelect.value = customerDefaultPriceTypeCode;
                            priceTypeFound = true;
                            console.log(`تم تطبيق السعر الافتراضي للعميل: ${option.text}`);
                            
                            // Trigger change event to update prices if needed
                            priceTypeSelect.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                    
                    // If customer's price type not found, fall back to default
                    if (!priceTypeFound) {
                        console.warn(`لم يتم العثور على نوع السعر الافتراضي للعميل: ${customerDefaultPriceTypeCode}, سيتم تطبيق السعر الافتراضي العام.`);
                        setDefaultPriceType();
                    }
                } else {
                    // Customer doesn't have a specific price type, reset to default
                    console.log('العميل ليس لديه سعر افتراضي، سيتم تطبيق السعر الافتراضي العام.');
                    setDefaultPriceType();
                }
            }
            
            function setDefaultPriceType() {
                console.log('Attempting to set default price type...');
                // Find the default price type (marked as is_default in the backend)
                let defaultFound = false;
                for (let option of priceTypeSelect.options) {
                    if (option.dataset.isDefault === '1') {
                        priceTypeSelect.value = option.value;
                        defaultFound = true;
                        console.log(`تم تطبيق السعر الافتراضي العام: ${option.text}`);
                        
                        // Trigger change event to update prices if needed
                        priceTypeSelect.dispatchEvent(new Event('change'));
                        break;
                    }
                }
                
                // If no default found, use the first option
                if (!defaultFound && priceTypeSelect.options.length > 0) {
                    priceTypeSelect.selectedIndex = 0;
                    priceTypeSelect.dispatchEvent(new Event('change'));
                    console.log('لم يتم العثور على سعر افتراضي عام، تم تطبيق السعر الأول كافتراضي.');
                }
            }
        });
        
        // Trigger initial change event
        customerSelect.dispatchEvent(new Event('change'));
    }
    
    // Handle print invoice button
    document.getElementById('print-last-invoice-btn').addEventListener('click', function() {
        const lastInvoiceId = localStorage.getItem('last_invoice_id');
        if (lastInvoiceId) {
            window.open(`/sales/invoices/${lastInvoiceId}/print`, '_blank');
        } else {
            showError('لا توجد فاتورة سابقة للطباعة');
        }
    });
    
    // Handle print profit details
    document.getElementById('print-profit-btn').addEventListener('click', function() {
        window.printProfitDetails();
    });
    
    // Handle show delivery orders
    document.getElementById('show-delivery-orders-btn').addEventListener('click', function() {
        // Will be implemented in delivery module
        $('#delivery-orders-modal').modal('show');
        window.loadDeliveryOrders();
    });
    
    // Initialize any third-party plugins
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#app') // Ensures dropdowns are properly positioned in modals
        });
    }
});

// After initializing modules
$(document).ready(function() {
    console.log('POS system initialized');
    
    // تأكد من تحميل رقم الفاتورة عند بدء التطبيق
    setTimeout(function() {
        if (window.loadCurrentInvoiceNumber) {
            console.log('Loading initial invoice number...');
            window.loadCurrentInvoiceNumber();
        }
    }, 500); // تأخير قصير للتأكد من أن كل شيء جاهز
});

// كود إضافي لضمان استدعاء رقم الفاتورة بعد تحميل الصفحة
(function() {
    // استدعاء عند تحميل الصفحة
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            console.log('استدعاء رقم الفاتورة بعد تحميل DOM');
            if (window.loadCurrentInvoiceNumber) {
                window.loadCurrentInvoiceNumber();
            }
        }, 1000); // تأخير لمدة ثانية للتأكد من تحميل كل شيء
    });
    
    // استدعاء فوري
    if (window.loadCurrentInvoiceNumber) {
        console.log('استدعاء فوري لرقم الفاتورة');
        setTimeout(window.loadCurrentInvoiceNumber, 500);
    }
})(); 

// ===== Mixed Payments Helpers =====
function addPaymentRow(method = 'cash', amount = 0) {
    console.log('addPaymentRow called');
    const tbody = document.getElementById('mixed-payments-body');
    if (!tbody) return;

    const tr = document.createElement('tr');

    // Method select
    const tdMethod = document.createElement('td');
    const select = document.createElement('select');
    select.className = 'form-select form-select-sm payment-method-select';
    ['cash','visa','transfer'].forEach(function(opt){
        const o = document.createElement('option');
        o.value = opt;
        o.textContent = opt === 'cash' ? 'كاش' : (opt === 'visa' ? 'فيزا' : 'تحويل');
        if (opt === method) o.selected = true;
        select.appendChild(o);
    });
    tdMethod.appendChild(select);

    // Amount input
    const tdAmount = document.createElement('td');
    const input = document.createElement('input');
    input.type = 'number';
    input.step = '0.01';
    input.min = '0';
    input.className = 'form-control form-control-sm payment-amount-input';
    input.value = amount;
    tdAmount.appendChild(input);

    // Remove button
    const tdRemove = document.createElement('td');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-danger';
    btn.innerHTML = '<i class="fas fa-trash"></i>';
    btn.addEventListener('click', function(){
        tr.remove();
        calculateMixedTotals();
    });
    tdRemove.appendChild(btn);

    tr.appendChild(tdMethod);
    tr.appendChild(tdAmount);
    tr.appendChild(tdRemove);

    // Update totals on change
    input.addEventListener('input', calculateMixedTotals);
    select.addEventListener('change', calculateMixedTotals);

    tbody.appendChild(tr);
    calculateMixedTotals();
}

function calculateMixedTotals() {
    const inputs = document.querySelectorAll('.payment-amount-input');
    let sum = 0;
    inputs.forEach(i => { sum += parseFloat(i.value) || 0; });

    document.getElementById('paid-amount').value = sum.toFixed(2); // keep backend compatibility
    const totalField = parseFloat(document.getElementById('total').textContent) || 0;
    const remaining = totalField - sum;
    document.getElementById('remaining').textContent = remaining.toFixed(2);
}

function ensureMixedRows() {
    const rowsCount = document.querySelectorAll('#mixed-payments-body tr').length;
    if (rowsCount === 0) {
        addPaymentRow();
    }
}

// expose
window.ensureMixedRows = ensureMixedRows;

// Handle click on "Add Payment" button inside the mixed-payments modal
document.addEventListener('click', function (e) {
    // Support both legacy and updated IDs just in case
    const target = e.target.closest('#add-payment-row, #add-payment-row-btn');
    if (target) {
        e.preventDefault();
        addPaymentRow();
    }
});

// Export helper to gather payments before submit
window.collectMixedPayments = function() {
    const rows = document.querySelectorAll('#mixed-payments-body tr');
    const payments = [];
    rows.forEach(function(r){
        const method = r.querySelector('.payment-method-select').value;
        const amount = parseFloat(r.querySelector('.payment-amount-input').value) || 0;
        payments.push({method, amount});
    });
    return payments;
};
// ===== End Mixed Helpers ===== 
window.addPaymentRow = addPaymentRow;
window.calculateMixedTotals = calculateMixedTotals; 