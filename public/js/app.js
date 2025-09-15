/**
 * Main Application JavaScript
 * 
 * This file contains common functionality and performance optimizations
 * for the POS application.
 */

// Use strict mode for better error catching and performance
'use strict';

// Create app namespace to avoid polluting the global scope
window.app = (function() {
    // Cache DOM elements to avoid repeated querying
    const domCache = {};
    
    // Initialize the application
    function init() {
        document.addEventListener('DOMContentLoaded', function() {
            setupLazyLoading();
            setupEventDelegation();
            initializeTooltips();
        });
    }
    
    /**
     * Lazy loading implementation for images
     */
    function setupLazyLoading() {
        const lazyImages = document.querySelectorAll('.lazy-load');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy-load');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(image => imageObserver.observe(image));
        } else {
            // Fallback for browsers that don't support IntersectionObserver
            const lazyLoad = throttle(() => {
                const scrollTop = window.pageYOffset;
                lazyImages.forEach(img => {
                    if (img.offsetTop < (window.innerHeight + scrollTop)) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy-load');
                    }
                });
                
                if (lazyImages.length === 0) { 
                    document.removeEventListener('scroll', lazyLoad);
                    window.removeEventListener('resize', lazyLoad);
                    window.removeEventListener('orientationchange', lazyLoad);
                }
            }, 20);
            
            document.addEventListener('scroll', lazyLoad);
            window.addEventListener('resize', lazyLoad);
            window.addEventListener('orientationchange', lazyLoad);
        }
    }
    
    /**
     * Event delegation for improved performance
     */
    function setupEventDelegation() {
        // Delegate document clicks
        document.addEventListener('click', e => {
            // Handle delete confirmation buttons
            if (e.target.closest('.btn-delete')) {
                e.preventDefault();
                const deleteBtn = e.target.closest('.btn-delete');
                const deleteUrl = deleteBtn.dataset.url;
                if (deleteUrl) {
                    confirmDelete(deleteUrl);
                }
            }
            
            // Handle data-toggle elements
            if (e.target.closest('[data-toggle]')) {
                const toggleBtn = e.target.closest('[data-toggle]');
                const targetId = toggleBtn.dataset.target;
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.classList.toggle('d-none');
                }
            }
        });
    }
    
    /**
     * Initialize Bootstrap tooltips
     */
    function initializeTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length > 0) {
            [...tooltipTriggerList].map(tooltipTriggerEl => 
                new bootstrap.Tooltip(tooltipTriggerEl, {
                    boundary: document.body
                })
            );
        }
    }
    
    /**
     * Get element by selector with caching
     */
    function getElement(selector) {
        if (!domCache[selector]) {
            domCache[selector] = document.querySelector(selector);
        }
        return domCache[selector];
    }
    
    /**
     * Get element by ID with caching
     */
    function getElementById(id) {
        const selector = `#${id}`;
        if (!domCache[selector]) {
            domCache[selector] = document.getElementById(id);
        }
        return domCache[selector];
    }
    
    /**
     * Get elements by selector with caching
     */
    function getElements(selector) {
        if (!domCache[`__multiple_${selector}`]) {
            domCache[`__multiple_${selector}`] = document.querySelectorAll(selector);
        }
        return domCache[`__multiple_${selector}`];
    }
    
    /**
     * Debounce function for handling rapid events
     */
    function debounce(func, wait = 300) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    /**
     * Throttle function for rate-limiting events
     */
    function throttle(func, limit = 100) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    /**
     * Confirm delete with SweetAlert2
     */
    function confirmDelete(url) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'لن تتمكن من استرجاع هذا العنصر!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'نعم، قم بالحذف!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed && url) {
                window.location.href = url;
            }
        });
    }
    
    /**
     * Show success alert with SweetAlert2
     */
    function showSuccessAlert(message, timer = 3000) {
        Swal.fire({
            icon: 'success',
            title: 'نجاح',
            text: message,
            timer: timer,
            showConfirmButton: false
        });
    }
    
    /**
     * Show error alert with SweetAlert2
     */
    function showErrorAlert(message) {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: message
        });
    }
    
    /**
     * Show warning alert with SweetAlert2
     */
    function showWarningAlert(message) {
        Swal.fire({
            icon: 'warning',
            title: 'تنبيه',
            text: message
        });
    }
    
    /**
     * Format currency
     */
    function formatCurrency(amount, decimals = 2) {
        return parseFloat(amount).toFixed(decimals);
    }
    
    /**
     * Make API request with proper headers
     */
    function apiRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        };
        
        const requestOptions = {...defaultOptions, ...options};
        
        // If method is POST/PUT/PATCH and body is an object, stringify it
        if (requestOptions.body && typeof requestOptions.body === 'object' && 
            !requestOptions.headers['Content-Type'].includes('multipart/form-data')) {
            requestOptions.body = JSON.stringify(requestOptions.body);
        }
        
        return fetch(url, requestOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    }
    
    // Initialize the application
    init();
    
    // Return public API
    return {
        debounce,
        throttle,
        getElement,
        getElementById,
        getElements,
        confirmDelete,
        showSuccessAlert,
        showErrorAlert,
        showWarningAlert,
        formatCurrency,
        apiRequest,
        setupLazyLoading
    };
})(); 