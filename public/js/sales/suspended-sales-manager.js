/**
 * Suspended Sales Manager - Handles operations related to suspended sales
 * 
 * This module handles:
 * - Loading suspended sales list
 * - Searching suspended sales
 * - Resuming suspended sales
 * - Deleting suspended sales
 */

export default class SuspendedSalesManager {
    constructor() {
        // DOM Elements
        this.suspendedSalesTable = document.getElementById('suspended-sales-table');
        this.searchInput = document.getElementById('suspended-sales-search');
        this.loadingElement = document.getElementById('suspended-sales-loading');
        this.noResultsElement = document.getElementById('suspended-sales-no-results');
        
        // Bind events
        this.bindEvents();
    }

    /**
     * Initialize event listeners
     */
    bindEvents() {
        // Search event
        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => {
                clearTimeout(this._searchTimeout);
                this._searchTimeout = setTimeout(() => {
                    this.loadSuspendedSales();
                }, 300);
            });
        }
        
        // Show suspended sales modal event
        document.getElementById('btn-show-suspended-sales').addEventListener('click', () => {
            this.loadSuspendedSales();
        });
    }

    /**
     * Load suspended sales
     */
    loadSuspendedSales() {
        // Show loading indicator
        this.showLoading();
        
        // Get search query if any
        const query = this.searchInput ? this.searchInput.value.trim() : '';
        
        // Fetch suspended sales
        fetch(`/sales/suspended-sales?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderSuspendedSales(data.suspended_sales);
                } else {
                    this.showNoResults('حدث خطأ أثناء تحميل الفواتير المعلقة');
                }
            })
            .catch(error => {
                console.error('Error loading suspended sales:', error);
                this.showNoResults('حدث خطأ أثناء تحميل الفواتير المعلقة');
            });
    }

    /**
     * Render suspended sales in the table
     * @param {Array} suspendedSales - List of suspended sales
     */
    renderSuspendedSales(suspendedSales) {
        // Hide loading indicator
        this.hideLoading();
        
        // Get table body
        const tbody = this.suspendedSalesTable.querySelector('tbody');
        tbody.innerHTML = '';
        
        // Check if there are any suspended sales
        if (!suspendedSales || suspendedSales.length === 0) {
            this.showNoResults();
            return;
        }
        
        // Populate table
        suspendedSales.forEach(sale => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>${sale.id}</td>
                <td>${sale.customer ? sale.customer.name : 'عميل نقدي'}</td>
                <td>
                    <span class="badge bg-info">${sale.items_count} منتج</span>
                </td>
                <td>${this.formatAmount(sale.total_amount)}</td>
                <td>${this.formatDate(sale.created_at)}</td>
                <td>${sale.employee ? sale.employee.name : '-'}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary btn-resume-sale" data-id="${sale.id}" title="استرجاع">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-danger btn-delete-sale" data-id="${sale.id}" title="حذف">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            
            // Add to table
            tbody.appendChild(row);
            
            // Add event listeners to buttons
            row.querySelector('.btn-resume-sale').addEventListener('click', () => this.resumeSuspendedSale(sale.id));
            row.querySelector('.btn-delete-sale').addEventListener('click', () => this.confirmDeleteSuspendedSale(sale.id));
        });
    }

    /**
     * Resume a suspended sale
     * @param {number} id - Suspended sale ID
     */
    resumeSuspendedSale(id) {
        window.invoiceManager.loadSuspendedSale(id);
    }

    /**
     * Confirm deletion of a suspended sale
     * @param {number} id - Suspended sale ID
     */
    confirmDeleteSuspendedSale(id) {
        // Show confirmation dialog
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'سيتم حذف الفاتورة المعلقة نهائيًا!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، احذفها!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                this.deleteSuspendedSale(id);
            }
        });
    }

    /**
     * Delete a suspended sale
     * @param {number} id - Suspended sale ID
     */
    deleteSuspendedSale(id) {
        // Show loading indicator
        this.showLoading();
        
        // Delete suspended sale
        fetch(`/sales/suspended-sales/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload suspended sales
                    this.loadSuspendedSales();
                    
                    // Show success message
                    showSuccess('تم حذف الفاتورة المعلقة بنجاح');
                } else {
                    // Show error message
                    showError(data.message || 'حدث خطأ أثناء حذف الفاتورة المعلقة');
                    
                    // Hide loading indicator
                    this.hideLoading();
                }
            })
            .catch(error => {
                console.error('Error deleting suspended sale:', error);
                showError('حدث خطأ أثناء حذف الفاتورة المعلقة');
                
                // Hide loading indicator
                this.hideLoading();
            });
    }

    /**
     * Show loading indicator
     */
    showLoading() {
        if (this.suspendedSalesTable) {
            this.suspendedSalesTable.classList.add('d-none');
        }
        
        if (this.noResultsElement) {
            this.noResultsElement.classList.add('d-none');
        }
        
        if (this.loadingElement) {
            this.loadingElement.classList.remove('d-none');
        }
    }

    /**
     * Hide loading indicator
     */
    hideLoading() {
        if (this.loadingElement) {
            this.loadingElement.classList.add('d-none');
        }
        
        if (this.suspendedSalesTable) {
            this.suspendedSalesTable.classList.remove('d-none');
        }
    }

    /**
     * Show no results message
     * @param {string} message - Optional custom message
     */
    showNoResults(message = null) {
        // Hide loading indicator and table
        this.hideLoading();
        
        if (this.suspendedSalesTable) {
            this.suspendedSalesTable.classList.add('d-none');
        }
        
        if (this.noResultsElement) {
            this.noResultsElement.classList.remove('d-none');
            
            if (message) {
                this.noResultsElement.querySelector('p').textContent = message;
            } else {
                this.noResultsElement.querySelector('p').textContent = 'لا توجد فواتير معلقة';
            }
        }
    }

    /**
     * Format an amount with currency symbol
     * @param {number} amount - Amount to format
     * @returns {string} - Formatted amount
     */
    formatAmount(amount) {
        return parseFloat(amount).toFixed(2);
    }

    /**
     * Format a date
     * @param {string} dateString - ISO date string
     * @returns {string} - Formatted date
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        
        // Options for formatting
        const options = { 
            year: 'numeric', 
            month: 'numeric', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        return date.toLocaleDateString('ar-SA', options);
    }
}

// Initialize as a global object for event handlers
window.suspendedSalesManager = new SuspendedSalesManager(); 