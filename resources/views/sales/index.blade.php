@extends('layouts.app')

@section('title', 'نقطة البيع')

@php
// All data is now passed directly from the SalesController@index method.
// This block is kept clean for clarity.

// For reference, these are the variables now available from the controller:
// $settings (collection of all relevant settings)
// $categories (with products, units, barcodes, and prices)
// $customers
// $employees
// $priceTypes

// Example of accessing a setting:
// $allowNegativeInventory = $settings->get('allow_negative_inventory', false);
@endphp

@section('content')
<div class="container-fluid sales-container pb-1">
    <div class="row g-2">
        @include('sales.components.left-column')
        @include('sales.components.right-column')
                </div>
            </div>

@include('sales.components.modals.categories-modal')
@include('sales.components.modals.category-products-modal')
@include('sales.components.modals.products-modal')
@include('sales.components.modals.product-units-modal')
@include('sales.components.modals.add-customer-modal')
@include('sales.components.modals.profit-details-modal')
@include('sales.components.modals.suspended-sales-modal')
@include('sales.components.modals.delivery-status-modal')
@include('sales.components.modals.delivery-orders-modal')
@endsection

@push('styles')
<style>
/* General styling */
.sales-container {
    background-color: #f5f7fa;
}

.sales-card {
    border: none;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 1rem;
    transition: all 0.3s;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
}

.sales-card:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.sales-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 0.75rem 1rem;
    font-weight: 600;
}

.sales-card .card-body {
    background-color: #ffffff;
    padding: 1rem;
}

/* Compact styling */
.compact-controls {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 0.5rem;
}

.compact-controls .nav-tabs .nav-link {
    padding: 0.4rem 0.5rem;
    font-size: 0.8rem;
    transition: all 0.2s;
    border-radius: 0;
}

.bg-white-hover:hover {
    background-color: #ffffff !important;
}

.nav-tabs .nav-link {
    color: #6c757d;
}

.nav-tabs .nav-link.active {
    background-color: #ffffff;
    border-bottom-color: transparent;
    font-weight: bold;
    color: #212529;
}

.nav-tabs .nav-link:hover:not(.active) {
    background-color: rgba(255, 255, 255, 0.5);
    border-color: #dee2e6 #dee2e6 #fff;
    color: #495057;
}

.compact-controls .tab-content {
    max-height: calc(100vh - 400px);
    overflow-y: auto;
}

.small-card {
    font-size: 0.85rem;
}

/* Header styling */
.card-header {
    border-bottom: 0;
    padding: 0.5rem 1rem;
}

/* Search bar styling */
.search-group .form-control {
    border: 1px solid #dee2e6;
    border-left: 0;
}

.search-group .form-control:focus {
    box-shadow: none;
    border-color: var(--bs-primary);
}

.search-group .input-group-text {
    border: 1px solid var(--bs-primary);
}

/* Quick action buttons */
.quick-action-btn {
    border-width: 1px;
    min-width: 36px;
}

/* Form controls */
.form-control, .form-select {
    border: 1px solid #dee2e6;
}

.form-control:focus, .form-select:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.15rem rgba(var(--bs-primary-rgb), 0.25);
}

/* Summary boxes */
.summary-box {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 8px 10px;
    border-left: 3px solid var(--bs-primary);
    margin-bottom: 0.25rem;
    transition: all 0.2s;
}

.summary-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 5px rgba(0,0,0,0.1);
}

/* Clickable profit box */
.profit-box {
    border-left: 3px solid var(--bs-success);
    position: relative;
    transition: all 0.2s;
    cursor: pointer;
}

.profit-box:hover {
    background-color: rgba(var(--bs-success-rgb), 0.1);
}

.profit-box::after {
    content: "\f044"; /* fa-pen-to-square */
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 0.7rem;
    color: var(--bs-success);
    opacity: 0.7;
}

.profit-box:hover::after {
    opacity: 1;
}

/* Total amount box */
.total-amount-box {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 8px 10px;
    border-left: 4px solid var(--bs-primary);
}

/* Remaining amount box */
.remaining-box {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 8px 10px;
    border-left: 4px solid var(--bs-danger);
}

/* Actions buttons */
.action-btn {
    border-radius: 6px;
    padding: 8px;
    transition: all 0.2s;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0,0,0,0.12);
}

/* Paid amount input */
.paid-amount-input {
    font-weight: bold;
    text-align: center;
}

/* Card styling */
.category-card, .product-card {
    cursor: pointer;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    transition: all 0.2s;
}

.category-card:hover, .product-card:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
    border-color: var(--bs-primary);
    transform: translateY(-3px);
}

/* Table styling */
#invoice-items {
    border-collapse: separate;
    border-spacing: 0;
}

#invoice-items thead th {
    background-color: #f2f7ff;
    position: sticky;
    top: 0;
    z-index: 100;
    font-weight: 600;
    border-bottom: 2px solid rgba(var(--bs-primary-rgb), 0.3);
    padding: 0.7rem 0.5rem;
    font-size: 0.85rem;
    color: #495057;
    text-align: right;
}

#invoice-items tbody td {
    padding: 0.7rem 0.5rem;
    vertical-align: middle;
    font-size: 0.9rem;
    border-bottom: 1px solid #f0f0f0;
}

#invoice-items tbody tr:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

/* إضافة تأثير بصري على الصف المحدد */
#invoice-items tbody tr.selected {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

/* تحسين حقل الكمية والسعر في الجدول */
#invoice-items .quantity-input,
#invoice-items .price-input {
    background-color: #f8f9fa;
    border-radius: 6px;
    text-align: center;
    font-weight: 600;
    color: #495057;
    transition: all 0.2s;
}

#invoice-items .quantity-input:focus,
#invoice-items .price-input:focus {
    background-color: #fff;
    border-color: rgba(var(--bs-primary-rgb), 0.5);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

/* تحسين عرض الأرقام في الجدول */
#invoice-items .subtotal,
#invoice-items .total,
#invoice-items .profit-column {
    font-weight: 600;
    text-align: left;
}

/* Input styling for quantities */
.quantity-input::-webkit-inner-spin-button, 
.quantity-input::-webkit-outer-spin-button,
.price-input::-webkit-inner-spin-button,
.price-input::-webkit-outer-spin-button,
.discount-input::-webkit-inner-spin-button,
.discount-input::-webkit-outer-spin-button { 
    -webkit-appearance: none;
    margin: 0;
}

.quantity-input, .price-input, .discount-input {
    text-align: center;
    font-weight: 500;
}

/* تحسين مظهر الحقول غير القابلة للتعديل */
.price-input[readonly] {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    opacity: 0.8;
    cursor: not-allowed;
}

/* تحسين مظهر القوائم المنسدلة في اللغة العربية */
.form-select {
    text-align: right;
    background-position: left 0.75rem center !important;
    padding-right: 0.75rem !important;
    padding-left: 2rem !important;
}

/* تحسين مظهر مربعات الاختيار */
.select2-container--bootstrap-5 .select2-selection {
    text-align: right;
    direction: rtl;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    left: 0.75rem !important;
    right: auto !important;
}

/* تحسين مظهر القوائم المنسدلة المفتوحة */
.select2-container--bootstrap-5 .select2-dropdown .select2-results__options .select2-results__option {
    text-align: right;
    direction: rtl;
}

/* تحسين مظهر الأزرار */
.action-btn {
    transition: all 0.3s ease;
    font-weight: 500;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 38px;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.action-btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* تحسين مظهر حقول الإدخال */
.form-control, .form-select {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
}

.form-control:focus, .form-select:focus {
    border-color: rgba(var(--bs-primary-rgb), 0.5);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

/* تحسين مظهر الصناديق في الملخص */
.summary-box {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
    border-left: 4px solid var(--bs-primary);
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.summary-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

/* تحسين مظهر بطاقات المنتجات */
.product-card, .category-card {
    transition: all 0.3s ease;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.product-card:hover, .category-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Highlight for newly added items */
.highlight-animation {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

/* Modal styling */
.modal-content {
    border: none;
    border-radius: 8px;
}

.modal-header {
    background-color: var(--bs-primary);
    color: white;
}

.modal-title {
    font-weight: bold;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .action-btn {
        font-size: 0.85rem;
        padding: 0.4rem 0.6rem;
    }
    
    .paid-amount-input {
        font-size: 1rem;
    }
    
    .nav-tabs .nav-link {
        padding: 0.3rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .compact-controls .nav-tabs .nav-link i {
        margin-right: 0;
    }
    
    .compact-controls .nav-tabs .nav-link span {
        display: none;
    }
}

/* Search suggestions */
.search-suggestions {
    position: absolute;
    width: 100%;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
}

.suggestions-container {
    padding: 5px 0;
}

.suggestion-item {
    padding: 6px 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

.suggestion-img {
    width: 32px;
    height: 32px;
    margin-left: 8px;
    object-fit: contain;
    border-radius: 4px;
    background-color: #f8f9fa;
    display: flex;
    justify-content: center;
    align-items: center;
}

.suggestion-info {
    flex: 1;
}

.suggestion-name {
    font-weight: bold;
    margin-bottom: 2px;
    font-size: 0.85rem;
}

.suggestion-barcode {
    font-size: 0.75rem;
    color: #6c757d;
}

.all-results-button {
    background-color: #f8f9fa;
    font-weight: bold;
    padding: 8px;
    font-size: 0.85rem;
}

.all-results-button:hover {
    background-color: #e2e6ea;
}

/* Styling for out-of-stock products */
.btn-secondary.stock-empty {
    background-color: #f8d7da;
    border-color: #f5c2c7;
    color: #842029;
}

.product-card.product-disabled {
    border: 1px solid #f5c2c7;
    background-color: #fff8f8;
    opacity: 0.6;
}

.product-disabled .card-body {
    position: relative;
}

.product-disabled .card-body::after {
    content: "نفذت الكمية";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 248, 248, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #842029;
    font-weight: bold;
    font-size: 0.8rem;
    border-radius: 6px;
}

/* تحسين مظهر مفاتيح التبديل */
.form-check-input.switch-lg {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
}
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.settings-label {
    font-size: 0.9rem;
    font-weight: 500;
}

/* عناصر الربح للإخفاء والإظهار */
.profit-elements {
    transition: opacity 0.3s ease;
}

/* تحسين مظهر الخصم في الجدول */
.discount-input {
    border-top-right-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
    border-left: none;
}

.discount-type {
    border-top-left-radius: 4px !important;
    border-bottom-left-radius: 4px !important;
    border-right: none;
    padding-right: 0.25rem;
    padding-left: 0.25rem;
    text-align: center;
}

/* تحسين مظهر الحقول في الإعدادات */
#discount, #discount-type {
    font-weight: 500;
    text-align: center;
}

/* تحسين مظهر التبويبات في شاشة البيع */
.nav-tabs {
    border-bottom: none;
}

.nav-tabs .nav-link {
    border: none;
    border-radius: 8px 8px 0 0 !important;
    font-weight: 500;
    padding: 0.5rem 1rem;
    color: #6c757d;
    background-color: #f0f2f5;
    transition: all 0.3s;
    margin-left: 2px;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #ffffff;
    border-bottom: 3px solid var(--bs-primary);
    font-weight: 600;
}

.nav-tabs .nav-link:hover:not(.active) {
    background-color: #e9ecef;
    color: #495057;
}

/* تحسين حقل البحث */
.search-box {
    position: relative;
}

.search-box .form-control {
    padding-right: 40px;
    border-radius: 8px;
    height: 38px;
    font-size: 0.95rem;
}

.search-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 0.9rem;
}

/* تحسين مظهر أزرار الفئات */
.category-selector {
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: thin;
    scrollbar-color: #dee2e6 #f8f9fa;
    padding: 0.5rem 0;
}

.category-selector::-webkit-scrollbar {
    height: 5px;
}

.category-selector::-webkit-scrollbar-track {
    background: #f8f9fa;
}

.category-selector::-webkit-scrollbar-thumb {
    background-color: #dee2e6;
    border-radius: 10px;
}

.category-btn {
    border-radius: 20px;
    padding: 0.4rem 0.8rem;
    margin-left: 0.3rem;
    font-size: 0.85rem;
    white-space: nowrap;
    transition: all 0.2s;
    background-color: #f0f2f5;
    border: 1px solid #dee2e6;
    color: #495057;
}

.category-btn:hover, .category-btn.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

/* تحسين مظهر بطاقات المنتجات */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.product-card {
    border: none;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.product-card .card-body {
    padding: 0.75rem;
    text-align: center;
}

.product-card .product-img {
    height: 80px;
    object-fit: contain;
    margin-bottom: 0.5rem;
}

.product-card .product-name {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    height: 40px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card .product-price {
    font-size: 0.8rem;
    color: var(--bs-primary);
    font-weight: 700;
}

.product-card .product-stock {
    font-size: 0.7rem;
    color: #6c757d;
}

/* تحسين مظهر صف اختيار العميل */
#customer-id.select2 {
    min-width: 200px;
    max-width: 70%;
}

.d-flex.align-items-center.gap-2 > .select2-container {
    flex-grow: 1;
    min-width: 200px;
    max-width: 70%;
}

.d-flex.align-items-center.gap-2 > .btn {
    min-width: 38px;
    margin-right: 0.5rem;
    margin-left: 0;
}

@media (max-width: 600px) {
    .d-flex.align-items-center.gap-2 > .select2-container {
        min-width: 120px;
        max-width: 100%;
    }
    #customer-id.select2 {
        min-width: 120px;
        max-width: 100%;
    }
}

/* عكس اتجاه سهم الدروب داون في RTL بشكل إجباري */
.form-select, select[dir="rtl"] {
    direction: rtl !important;
    text-align: right !important;
    background-position-x: left !important;
    background-position: left 0.75rem center !important;
    padding-right: 0.75rem !important;
    padding-left: 2rem !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
}

/* سهم مخصص للدروب داون في RTL */
.form-select[dir="rtl"], select[dir="rtl"] {
    background-image: url('data:image/svg+xml;utf8,<svg fill="%236c757d" height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M12 6l-4 4-4-4" stroke="%236c757d" stroke-width="2" fill="none" fill-rule="evenodd"/></svg>');
}

/* دعم خاص لـ select2 */
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    left: 0.75rem !important;
    right: auto !important;
}

/* === تحسينات واجهة جدول الفاتورة === */
#invoice-items thead.sticky-top th {
    background-color: #e9ecef; /* Light grey background for header */
    color: #343a40; /* Darker text for header */
    padding-top: 0.8rem;
    padding-bottom: 0.8rem;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #dee2e6;
}

#invoice-items tbody td {
    padding: 0.6rem 0.5rem; /* Increased padding for cells */
    vertical-align: middle;
    font-size: 0.9rem;
    border-bottom: 1px solid #f1f3f5; /* Lighter border for rows */
}

#invoice-items tbody tr:hover {
    background-color: #f8f9fa; /* Subtle hover for rows */
}

#invoice-items .product-name .fw-bold {
    font-size: 0.95rem;
    color: var(--bs-primary);
}

#invoice-items .product-name .text-muted {
    font-size: 0.75rem;
}

#invoice-items .quantity-input,
#invoice-items .price-input,
#invoice-items .discount-input,
#invoice-items .discount-type {
    border-radius: 0.3rem;
    border: 1px solid #ced4da;
    background-color: #fff;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    font-size: 0.9rem;
    height: 35px; /* Consistent height */
}

#invoice-items .quantity-input:focus,
#invoice-items .price-input:focus,
#invoice-items .discount-input:focus,
#invoice-items .discount-type:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

#invoice-items .price-input[readonly] {
    background-color: #e9ecef;
    opacity: 0.7;
}

#invoice-items .discount-input {
    min-width: 55px;
    text-align: center;
}

#invoice-items .discount-type {
    min-width: 65px;
    padding-left: 0.25rem;
    padding-right: 0.25rem;
    text-align: center;
}

#invoice-items .subtotal,
#invoice-items .total,
#invoice-items .profit-column {
    font-weight: 600;
    font-size: 0.95rem;
}

#invoice-items .btn-danger {
    background-color: transparent;
    border: 1px solid var(--bs-danger);
    color: var(--bs-danger);
    padding: 0.25rem 0.5rem;
    border-radius: 50%;
}

#invoice-items .btn-danger:hover {
    background-color: var(--bs-danger);
    color: white;
}


</style>
@endpush

@push('scripts')
<!-- Debugging script to catch syntax errors -->
<script>
window.onerror = function(message, source, lineno, colno, error) {
    console.log('JavaScript error:', {
        message: message,
        source: source,
        lineno: lineno,
        colno: colno,
        error: error
    });
    return false;
};
</script>

<!-- Global utility functions -->
<script>
// Define global utility functions first
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

// تعريف دالة تحميل رقم الفاتورة الحالية مباشرة في الصفحة
window.loadCurrentInvoiceNumber = function() {
    console.log('بدء استدعاء رقم الفاتورة من index.blade.php...');
    
    // عرض قيم مؤقتة أثناء التحميل
    var invoiceElement = document.getElementById('current-invoice-number');
    var countElement = document.querySelector('.invoice-number-count');
    
    if (invoiceElement) invoiceElement.textContent = '...';
    if (countElement) countElement.textContent = '...';
    
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
            
            // عرض رقم الفاتورة في الوردية (الرقم الجديد)
            if (data.invoice_number !== undefined && invoiceElement) {
                invoiceElement.textContent = data.invoice_number;
            } else if (data.next_shift_invoice_number !== undefined && invoiceElement) {
                invoiceElement.textContent = data.next_shift_invoice_number;
            } else if (invoiceElement) {
                invoiceElement.textContent = '1';
                console.warn('لم يتم استلام رقم الفاتورة في الوردية');
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
            
            console.log('تم عرض رقم الفاتورة في الوردية بنجاح:', data.invoice_number || data.next_shift_invoice_number || 1);
        })
        .catch(function(error) {
            console.error('خطأ في الاستدعاء:', error.message);
            
            // عرض قيم افتراضية في حالة الخطأ
            if (invoiceElement) invoiceElement.textContent = '1';
            if (countElement) countElement.textContent = '0';
        });
};
</script>

<!-- Initialize settings object first -->
<script>
// Initialize settings object
window.settings = {
    allowNegativeInventory: {{ $settings->get('allow_negative_inventory', false) ? 'true' : 'false' }},
    subtractInventoryOnZero: {{ $settings->get('subtract_inventory_on_zero', false) ? 'true' : 'false' }},
    showProfitInSummary: {{ $settings->get('show_profit_in_summary', false) ? 'true' : 'false' }},
    showProfitInSalesTable: {{ $settings->get('show_profit_in_sales_table', false) ? 'true' : 'false' }},
    showExpiryDates: {{ $settings->get('show_expiry_dates', false) ? 'true' : 'false' }},
    allowSellingAtDifferentPrices: {{ $settings->get('allow_selling_at_different_prices', true) ? 'true' : 'false' }},
    allowPriceEditDuringSale: {{ $settings->get('allow_price_edit_during_sale', true) ? 'true' : 'false' }},
    defaultPriceType: '{{ $settings->get('default_price_type', 'retail') }}'
};
// DEBUG: Log settings to console
console.log('System Settings Loaded:', window.settings);

// Global variable to store resumed sale ID
window.g_resumedSuspendedSaleId = null;
</script>

<!-- Include helper scripts -->
<script nonce="{{ csrf_token() }}" src="{{ asset('js/sales/helper/delivery-helper.js') }}"></script>
<script nonce="{{ csrf_token() }}" src="{{ asset('js/sales/helper/customer-helper.js') }}"></script>

<!-- الملف الرئيسي للتطبيق فقط - تم إلغاء تحميل main.js لمنع التضارب -->
<script nonce="{{ csrf_token() }}" src="{{ asset('js/sales/app.js') }}" defer></script>

<!-- Initialize delivery helpers -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تعريف وظائف عرض وإخفاء مؤشر التحميل
    window.showLoading = function() {
        Swal.fire({
            title: 'جاري التحميل...',
            text: 'يرجى الانتظار',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    };

    window.hideLoading = function() {
    Swal.close();
    };

    // تهيئة مساعد العملاء
    if (window.customerHelper && typeof window.customerHelper.initialize === 'function') {
        window.customerHelper.initialize();
        console.log('Customer helper initialized');
            } else {
        console.error('Customer helper not loaded correctly');
    }

    if (window.deliveryHelper && typeof window.deliveryHelper.initDeliveryEventListeners === 'function') {
        window.deliveryHelper.initDeliveryEventListeners();
        console.log('Delivery event listeners initialized');
        
        // إضافة معالج أحداث للزر الجديد
        const quickDeliveryStatusBtn = document.getElementById('delivery-status-btn-quick');
        if (quickDeliveryStatusBtn) {
            quickDeliveryStatusBtn.addEventListener('click', function() {
                if (window.deliveryHelper.loadDeliveryOrders) {
                    window.deliveryHelper.loadDeliveryOrders();
        }
    });
}
            } else {
        console.error('Delivery helper not loaded correctly');
    }
});
</script>

<!-- سكريبت إضافي لضمان تحميل رقم الفاتورة -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحميل رقم الفاتورة مرة واحدة عند فتح الصفحة
    console.log('تحميل رقم الفاتورة عند فتح الصفحة');
    
    // استدعاء الدالة مباشرة
    try {
        window.loadCurrentInvoiceNumber();
        console.log('تم استدعاء دالة تحميل رقم الفاتورة بنجاح');
    } catch (e) {
        console.error('خطأ أثناء استدعاء دالة تحميل رقم الفاتورة:', e);
    }
    
    // لا حاجة للتحديث الدوري بعد ذلك
});

// Listener for settings updates from other tabs
window.addEventListener('storage', function(e) {
    if (e.key === 'settings_updated' && e.newValue === 'true') {
        // Clear the flag
        localStorage.removeItem('settings_updated');

        // Ask user to reload
        Swal.fire({
            title: 'تم تحديث الإعدادات',
            text: 'تم تحديث إعدادات النظام في تبويب آخر. هل ترغب في إعادة تحميل الصفحة لتطبيق الإعدادات الجديدة؟',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'نعم، إعادة التحميل',
            cancelButtonText: 'لاحقاً',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    }
});
</script>
@endpush
