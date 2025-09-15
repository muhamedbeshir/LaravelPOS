/**
 * Product Manager - Handles all product-related operations in the POS system
 * 
 * This module handles:
 * - Product search
 * - Product selection
 * - Category filtering
 * - Product display
 */

export default class ProductManager {
    constructor() {
        // DOM Elements
        this.searchInput = document.getElementById('search-input');
        this.searchButton = document.getElementById('search-btn');
        this.searchSuggestions = document.getElementById('search-suggestions');
        this.suggestionsContainer = this.searchSuggestions.querySelector('.suggestions-container');
        this.modalSearchInput = document.getElementById('modal-search-input');
        this.categoryFilter = document.getElementById('category-filter');
        this.allProductsTable = document.getElementById('all-products-table');
        this.categoryProductsSearch = document.getElementById('category-products-search');
        this.categoryProductsContainer = document.getElementById('category-products-container');
        
        // Pagination state
        this.currentPage = 1;
        this.perPage = 50;
        
        // Bind events
        this.bindEvents();
    }

    /**
     * Initialize event listeners
     */
    bindEvents() {
        // Search events
        this.searchInput.addEventListener('input', () => this.handleSearchInput());
        this.searchButton.addEventListener('click', () => this.handleBarcodeInput());
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') this.handleBarcodeInput();
        });
        
        // Hide search suggestions on click outside
        document.addEventListener('click', (e) => this.hideSearchSuggestions(e));
        
        // Modal search events
        if (this.modalSearchInput) {
            this.modalSearchInput.addEventListener('input', () => this.loadAllProducts());
        }
        
        // Category filter
        if (this.categoryFilter) {
            this.categoryFilter.addEventListener('change', () => this.loadAllProducts());
        }
        
        // Category products search
        if (this.categoryProductsSearch) {
            this.categoryProductsSearch.addEventListener('input', (e) => this.filterCategoryProducts(e.target.value));
        }
        
        // Pagination size change
        const paginationSize = document.getElementById('pagination-size');
        if (paginationSize) {
            paginationSize.addEventListener('change', () => {
                const perPage = parseInt(paginationSize.value);
                this.loadAllProducts(1, perPage);
            });
        }
        
        // Load products when the modal is shown
        $('#products-modal').on('shown.bs.modal', () => {
            // Only load if the table is empty
            const tbody = this.allProductsTable.querySelector('tbody');
            if (!tbody.innerHTML || tbody.querySelector('tr td[colspan]')) {
                this.loadAllProducts(1, this.perPage);
            }
        });
    }

    /**
     * Handle search input events (as user types)
     */
    handleSearchInput() {
        const query = this.searchInput.value.trim();
        
        if (query.length < 2) {
            this.searchSuggestions.classList.add('d-none');
            return;
        }
        
        clearTimeout(this._searchTimeout);
        
        this._searchTimeout = setTimeout(() => {
            this.searchProducts(query);
        }, 300);
    }

    /**
     * Search for products via API
     * @param {string} query - The search query
     */
    searchProducts(query) {
        this.searchSuggestions.classList.remove('d-none');
        this.suggestionsContainer.innerHTML = `
            <div class="text-center py-3 text-muted">
                <i class="fas fa-search me-2"></i>جاري البحث...
            </div>
        `;
        
        fetch(`/sales/products/search?q=${encodeURIComponent(query)}&price_type=${this.getPriceType()}`)
            .then(response => response.json())
            .then(data => this.handleSearchResults(data, query))
            .catch(error => {
                this.suggestionsContainer.innerHTML = `
                    <div class="text-center py-3 text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>حدث خطأ أثناء البحث
                    </div>
                `;
                console.error('Search error:', error);
            });
    }

    /**
     * Handle search results from API
     * @param {Object} response - API response
     * @param {string} query - The search query
     */
    handleSearchResults(response, query) {
        if (!response.success || !response.products || response.products.length === 0) {
            this.suggestionsContainer.innerHTML = `
                <div class="text-center py-3 text-muted">
                    <i class="fas fa-search me-2"></i>لا توجد منتجات مطابقة
                </div>
            `;
            return;
        }
        
        this.suggestionsContainer.innerHTML = '';
        
        // Add the first 10 products
        response.products.slice(0, 10).forEach(product => {
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'suggestion-item';
            suggestionItem.onclick = () => this.selectProduct(product.id);
            
            suggestionItem.innerHTML = `
                <div class="suggestion-img">
                    ${product.image_url ? 
                        `<img src="${product.image_url}" alt="${product.name}" width="40" height="40">` :
                        '<i class="fas fa-box text-primary"></i>'
                    }
                </div>
                <div class="suggestion-info">
                    <div class="suggestion-name">${product.name || 'منتج بدون اسم'}</div>
                    <div class="suggestion-barcode">
                        <i class="fas fa-barcode me-1"></i>${product.barcode || 'بدون باركود'}
                    </div>
                    ${product.category ? `
                    <div class="mt-1">
                        <span class="badge" style="background-color: ${product.category.color || '#6c757d'}">
                            ${product.category.name || 'بدون تصنيف'}
                        </span>
                    </div>
                    ` : ''}
                </div>
            `;
            
            this.suggestionsContainer.appendChild(suggestionItem);
        });
        
        // If there are more results, add a "view all" button
        if (response.products.length > 10) {
            const allResultsButton = document.createElement('div');
            allResultsButton.className = 'suggestion-item text-center all-results-button';
            allResultsButton.onclick = () => this.showAllProductsModal(query);
            
            allResultsButton.innerHTML = `
                <div class="text-primary w-100">
                    <i class="fas fa-search me-2"></i>عرض كل النتائج (${response.products.length})
                </div>
            `;
            
            this.suggestionsContainer.appendChild(allResultsButton);
        }
    }

    /**
     * Handle barcode input (Enter key or search button)
     */
    handleBarcodeInput() {
        const searchTerm = this.searchInput.value.trim();
        if (!searchTerm) return;
        
        // Hide search suggestions
        this.searchSuggestions.classList.add('d-none');
        
        // Show loading indicator
        this.searchButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        
        // Determine if this is a barcode or text search
        const isBarcode = /^\d+$/.test(searchTerm);
        
        // Search parameters
        const params = isBarcode ? 
            `barcode=${encodeURIComponent(searchTerm)}&price_type=${this.getPriceType()}` : 
            `q=${encodeURIComponent(searchTerm)}&price_type=${this.getPriceType()}`;
        
        // Execute search
        fetch(`/sales/products/search?${params}`)
            .then(response => response.json())
            .then(data => {
                // Reset the search input
                this.searchInput.value = '';
                this.searchButton.innerHTML = '<i class="fas fa-search"></i>';
                
                if (data.success) {
                    if (data.multiple) {
                        // Multiple products found - show products in modal
                        this.populateProductsTable(data.products);
                        $('#products-modal').modal('show');
                    } else {
                        // Single product found
                        this.selectProduct(data.product.id);
                    }
                } else {
                    showError(data.message || 'لم يتم العثور على منتج مطابق');
                }
            })
            .catch(error => {
                this.searchInput.value = '';
                this.searchButton.innerHTML = '<i class="fas fa-search"></i>';
                
                console.error('Search error:', error);
                showError('حدث خطأ أثناء البحث عن المنتج');
            });
    }

    /**
     * Hide search suggestions when clicking outside
     * @param {Event} event - Click event
     */
    hideSearchSuggestions(event) {
        if (!event.target.closest('.search-group, #search-suggestions')) {
            this.searchSuggestions.classList.add('d-none');
        }
    }

    /**
     * Show products modal with search results
     * @param {string} query - The search query
     */
    showAllProductsModal(query) {
        this.searchSuggestions.classList.add('d-none');
        this.modalSearchInput.value = query;
        $('#products-modal').modal('show');
        
        // Load first page with 50 products by default
        this.loadAllProducts(1, 50);
    }

    /**
     * Load products in the all products modal
     * @param {number} page - Page number to load (default: 1)
     * @param {number} perPage - Items per page (default: 50)
     */
    loadAllProducts(page = 1, perPage = 50) {
        const tbody = this.allProductsTable.querySelector('tbody');
        const query = this.modalSearchInput.value.trim();
        const categoryId = this.categoryFilter.value;
        const params = new URLSearchParams();
        
        // Add search params
        if (query) params.append('q', query);
        if (categoryId) params.append('category_id', categoryId);
        params.append('price_type', this.getPriceType());
        
        // Add pagination params
        params.append('page', page);
        params.append('per_page', perPage);
        
        // Store current pagination state
        this.currentPage = page;
        this.perPage = perPage;
        
        // Show loading indicator
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <div class="mt-2">جاري تحميل المنتجات...</div>
                </td>
            </tr>
        `;
        
        // Fetch products with pagination
        fetch(`/sales/products?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate table with products
                    this.populateProductsTable(data.products);
                    
                    // Update pagination
                    this.updatePagination(data.pagination);
                } else {
                    throw new Error(data.message || 'Failed to load products');
                }
            })
            .catch(error => {
                console.error('Error loading products:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                            <div>حدث خطأ أثناء تحميل المنتجات</div>
                        </td>
                    </tr>
                `;
            });
    }

    /**
     * Populate the products table with search results
     * @param {Array} products - List of products
     */
    populateProductsTable(products) {
        const tbody = this.allProductsTable.querySelector('tbody');
        tbody.innerHTML = '';
        
        if (!products || products.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-search fa-2x mb-2 text-muted"></i>
                        <div class="text-muted">لا توجد منتجات مطابقة</div>
                    </td>
                </tr>
            `;
            return;
        }
        
        products.forEach(product => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td class="text-center">
                    ${product.image_url ? 
                        `<img src="${product.image_url}" height="50" style="object-fit: contain;">` :
                        '<i class="fas fa-box fa-2x text-muted"></i>'
                    }
                </td>
                <td>${product.barcode || '-'}</td>
                <td>
                    <div class="fw-bold">${product.name || 'منتج بدون اسم'}</div>
                </td>
                <td>
                    <span class="badge" style="background-color: ${product.category && product.category.color ? product.category.color : '#6c757d'}">
                        ${product.category && product.category.name ? product.category.name : 'بدون تصنيف'}
                    </span>
                </td>
                <td>
                    <span class="badge ${Number(product.stock_quantity) > 0 ? 'bg-success' : 'bg-danger'}">
                        ${product.stock_quantity !== undefined && product.stock_quantity !== null ? product.stock_quantity : '0'}
                    </span>
                </td>
                <td>${this.formatCurrency(product.price)}</td>
                <td>
                    <button class="btn btn-primary btn-sm w-100" onclick="productManager.selectProduct(${product.id})">
                        <i class="fas fa-plus"></i>
                        إضافة
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }

    /**
     * Show products for a specific category
     * @param {number} categoryId - Category ID
     * @param {string} categoryName - Category name
     */
    showCategoryProducts(categoryId, categoryName) {
        document.getElementById('category-name').textContent = categoryName || '';
        
        $('#categories-modal').modal('hide');
        $('#category-products-modal').modal('show');
        
        this.loadCategoryProducts(categoryId);
    }

    /**
     * Load products for a specific category
     * @param {number} categoryId - Category ID
     */
    loadCategoryProducts(categoryId) {
        this.categoryProductsContainer.innerHTML = `
            <div class="col-12 text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <div class="mt-2">جاري تحميل المنتجات...</div>
            </div>
        `;
        
        // Use the new dedicated endpoint
        const priceType = this.getPriceType();
        fetch(`/sales/products/category/${categoryId}?price_type=${priceType}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.products && data.products.length > 0) {
                    this.renderCategoryProducts(data.products);
                } else {
                    this.categoryProductsContainer.innerHTML = `
                        <div class="col-12 text-center py-4 text-muted">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <div>لا توجد منتجات في هذه المجموعة</div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading category products:', error);
                this.categoryProductsContainer.innerHTML = `
                    <div class="col-12 text-center py-4 text-danger">
                        <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                        <div>حدث خطأ أثناء تحميل المنتجات</div>
                    </div>
                `;
            });
    }

    /**
     * Render products for a category
     * @param {Array} products - List of products
     */
    renderCategoryProducts(products) {
        this.categoryProductsContainer.innerHTML = '';
        
        products.forEach(product => {
            const isOutOfStock = product.stock_quantity <= 0 && !window.settings.allowNegativeInventory;
            const disabledClass = isOutOfStock ? 'product-disabled' : '';
            
            const productCard = document.createElement('div');
            productCard.className = `col-lg-3 col-md-4 col-6`;
            
            productCard.innerHTML = `
                <div class="card product-card h-100 ${disabledClass}" data-product-id="${product.id}">
                    <div class="card-body p-2 text-center" onclick="productManager.selectProduct(${product.id})">
                        <div class="position-relative">
                            ${product.image_url ? 
                                `<img src="${product.image_url}" class="product-img mb-2" alt="${product.name}">` :
                                `<div class="d-flex align-items-center justify-content-center" style="height: 80px">
                                    <i class="fas fa-box fa-2x text-primary"></i>
                                </div>`
                            }
                            <span class="badge ${isOutOfStock ? 'bg-danger' : 'bg-success'} position-absolute" 
                                  style="top: 0; right: 0;">
                                ${product.stock_quantity || 0}
                            </span>
                        </div>
                        <h6 class="product-name">${product.name || 'منتج بدون اسم'}</h6>
                        <div class="product-price">${this.formatCurrency(product.price)}</div>
                    </div>
                </div>
            `;
            
            this.categoryProductsContainer.appendChild(productCard);
        });
    }

    /**
     * Filter products in a category by name
     * @param {string} searchTerm - Search term
     */
    filterCategoryProducts(searchTerm) {
        if (!searchTerm) {
            // Show all products
            const products = this.categoryProductsContainer.querySelectorAll('.col-lg-3');
            products.forEach(product => product.style.display = '');
            return;
        }
        
        const searchTermLower = searchTerm.toLowerCase();
        
        const products = this.categoryProductsContainer.querySelectorAll('.col-lg-3');
        products.forEach(productCol => {
            const productName = productCol.querySelector('.product-name').textContent.toLowerCase();
            
            if (productName.includes(searchTermLower)) {
                productCol.style.display = '';
            } else {
                productCol.style.display = 'none';
            }
        });
    }

    /**
     * Go back to categories from category products
     */
    backToCategories() {
        $('#category-products-modal').modal('hide');
        $('#categories-modal').modal('show');
    }

    /**
     * Select a product and add it to the invoice
     * @param {number} productId - Product ID
     */
    selectProduct(productId) {
        // Close any open modals
        $('#products-modal').modal('hide');
        $('#category-products-modal').modal('hide');
        $('#search-suggestions').addClass('d-none');
        
        // Clear search input
        this.searchInput.value = '';
        
        // Show loading indicator in search button
        this.searchButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        
        // Get selected price type
        const priceType = this.getPriceType();
        
        // Fetch product details
        fetch(`/sales/product/${productId}?price_type=${priceType}`)
            .then(response => response.json())
            .then(data => {
                // Reset search button
                this.searchButton.innerHTML = '<i class="fas fa-search"></i>';
                
                if (data.success) {
                    if (data.product.units.length === 0) {
                        // No units available
                        showError('هذا المنتج ليس له وحدات متاحة للبيع');
                    } else if (data.product.units.length === 1) {
                        // Only one unit, add it directly
                        const unit = data.product.units[0];
                        invoiceManager.addProductToInvoice(data.product, unit);
                    } else {
                        // Multiple units, show unit selection
                        this.showUnitSelectionModal(data.product);
                    }
                } else {
                    showError(data.message || 'لم يتم العثور على المنتج');
                }
            })
            .catch(error => {
                // Reset search button
                this.searchButton.innerHTML = '<i class="fas fa-search"></i>';
                
                console.error('Error selecting product:', error);
                showError('حدث خطأ أثناء محاولة إضافة المنتج');
            });
    }

    /**
     * Show unit selection modal
     * @param {Object} product - Product data
     */
    showUnitSelectionModal(product) {
        const tbody = document.getElementById('units-table-body');
        tbody.innerHTML = '';
        
        // Debug log to see structure (will help diagnose issues)
        console.log('Product units data:', product.units);
        
        // Store current product data globally for the event handler
        window.currentProductData = product;
        
        product.units.forEach(unit => {
            const price = this.getUnitPrice(unit);
            console.log(`Unit ${unit.name} price:`, price, 'Unit data:', unit);
            
            const isOutOfStock = unit.stock_quantity <= 0 && !window.settings.allowNegativeInventory;
            const disabledAttr = isOutOfStock ? 'disabled' : '';
            
            const row = document.createElement('tr');
            
            // Create the row HTML without inline onclick handlers
            row.innerHTML = `
                <td>${unit.name}</td>
                <td>
                    <span class="badge ${isOutOfStock ? 'bg-danger' : 'bg-success'}">
                        ${unit.stock || unit.stock_quantity || 0}
                    </span>
                </td>
                <td>${this.formatCurrency(price)}</td>
                <td>
                    <button class="btn btn-sm btn-primary add-unit-btn" ${disabledAttr}>
                        <i class="fas fa-plus"></i>
                    </button>
                </td>
            `;
            
            // Add the row to the DOM
            tbody.appendChild(row);
            
            // Get the button we just added
            const addButton = row.querySelector('.add-unit-btn');
            
            // Add event listener to the button
            addButton.addEventListener('click', () => {
                console.log("Add button clicked for unit", unit);
                
                // Make sure unit has proper structure for invoice manager
                const unitForInvoice = {...unit};
                if (!unitForInvoice.unit) {
                    unitForInvoice.unit = { name: unitForInvoice.name };
                }
                
                // Add to invoice
                invoiceManager.addProductToInvoice(product, unitForInvoice);
            });
        });
        
        // Add a method to the ProductManager to handle adding products
        // This is a fallback for compatibility with any code that might still use the global handler
        if (!window.productManager.handleAddProductToInvoice) {
            window.productManager.handleAddProductToInvoice = function(productId, unitId) {
                console.log("Legacy handler called:", productId, unitId);
                
                // Handle case when unitId is a string
                if (typeof unitId === 'string') {
                    if (unitId.match(/^[0-9]+$/)) {
                        unitId = parseInt(unitId);
                    }
                }
                
                // Find matching unit
                const unit = window.currentProductData?.units?.find(u => 
                    u.id == unitId || u.unit_id == unitId
                );
                
                if (unit) {
                    console.log("Found unit:", unit);
                    
                    // Make sure unit has proper structure for invoice manager
                    if (!unit.unit) {
                        unit.unit = { name: unit.name };
                    }
                    
                    invoiceManager.addProductToInvoice(window.currentProductData, unit);
                } else {
                    console.error("Unit not found:", unitId);
                }
            };
        }
        
        $('#product-units-modal').modal('show');
    }

    /**
     * Get the correct price for a unit based on price type
     * @param {Object} unit - Product unit data
     * @returns {number} - Price
     */
    getUnitPrice(unit) {
        const priceType = this.getPriceType();
        let price = 0;
        
        // Check if unit has prices array
        if (unit.prices && Array.isArray(unit.prices) && unit.prices.length > 0) {
            // Find price for the selected price type
            // The server returns prices as objects with price_type and value properties
            const priceObj = unit.prices.find(p => p.price_type_code === priceType || (p.price_type && p.price_type.code === priceType));
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
     * Format currency value
     * @param {number} amount - Amount to format
     * @returns {string} - Formatted amount
     */
    formatCurrency(amount) {
        // Handle undefined, null, or NaN amounts
        if (amount === undefined || amount === null || isNaN(amount)) {
            return '0.00';
        }
        
        // Ensure amount is a number and format it
        return parseFloat(amount).toFixed(2);
    }

    /**
     * Update pagination controls based on API response
     * @param {Object} pagination - Pagination data from API
     */
    updatePagination(pagination) {
        // Update pagination info text
        const paginationInfo = document.getElementById('pagination-info');
        const firstItem = pagination.total > 0 ? (pagination.current_page - 1) * pagination.per_page + 1 : 0;
        const lastItem = Math.min(pagination.current_page * pagination.per_page, pagination.total);
        
        paginationInfo.textContent = `عرض ${firstItem} إلى ${lastItem} من أصل ${pagination.total} منتج`;
        
        // Update pagination controls
        const paginationControls = document.getElementById('pagination-controls');
        paginationControls.innerHTML = '';
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${pagination.current_page === 1 ? 'disabled' : ''}`;
        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.setAttribute('aria-label', 'السابق');
        prevLink.innerHTML = '&raquo;';
        if (pagination.current_page > 1) {
            prevLink.onclick = (e) => {
                e.preventDefault();
                this.loadAllProducts(pagination.current_page - 1, pagination.per_page);
            };
        }
        prevLi.appendChild(prevLink);
        paginationControls.appendChild(prevLi);
        
        // Calculate page range
        const start = Math.max(1, pagination.current_page - 2);
        const end = Math.min(start + 4, pagination.last_page);
        const adjustedStart = Math.max(1, end - 4);
        
        // First page
        if (adjustedStart > 1) {
            const firstPageLi = document.createElement('li');
            firstPageLi.className = 'page-item';
            const firstPageLink = document.createElement('a');
            firstPageLink.className = 'page-link';
            firstPageLink.href = '#';
            firstPageLink.textContent = '1';
            firstPageLink.onclick = (e) => {
                e.preventDefault();
                this.loadAllProducts(1, pagination.per_page);
            };
            firstPageLi.appendChild(firstPageLink);
            paginationControls.appendChild(firstPageLi);
            
            if (adjustedStart > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                const ellipsisSpan = document.createElement('span');
                ellipsisSpan.className = 'page-link';
                ellipsisSpan.textContent = '...';
                ellipsisLi.appendChild(ellipsisSpan);
                paginationControls.appendChild(ellipsisLi);
            }
        }
        
        // Page numbers
        for (let i = adjustedStart; i <= end; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${pagination.current_page === i ? 'active' : ''}`;
            const pageLink = document.createElement('a');
            pageLink.className = 'page-link';
            pageLink.href = '#';
            pageLink.textContent = i;
            if (pagination.current_page !== i) {
                pageLink.onclick = (e) => {
                    e.preventDefault();
                    this.loadAllProducts(i, pagination.per_page);
                };
            }
            pageLi.appendChild(pageLink);
            paginationControls.appendChild(pageLi);
        }
        
        // Last page ellipsis and link
        if (end < pagination.last_page) {
            if (end < pagination.last_page - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                const ellipsisSpan = document.createElement('span');
                ellipsisSpan.className = 'page-link';
                ellipsisSpan.textContent = '...';
                ellipsisLi.appendChild(ellipsisSpan);
                paginationControls.appendChild(ellipsisLi);
            }
            
            const lastPageLi = document.createElement('li');
            lastPageLi.className = 'page-item';
            const lastPageLink = document.createElement('a');
            lastPageLink.className = 'page-link';
            lastPageLink.href = '#';
            lastPageLink.textContent = pagination.last_page;
            lastPageLink.onclick = (e) => {
                e.preventDefault();
                this.loadAllProducts(pagination.last_page, pagination.per_page);
            };
            lastPageLi.appendChild(lastPageLink);
            paginationControls.appendChild(lastPageLi);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}`;
        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.setAttribute('aria-label', 'التالي');
        nextLink.innerHTML = '&laquo;';
        if (pagination.current_page < pagination.last_page) {
            nextLink.onclick = (e) => {
                e.preventDefault();
                this.loadAllProducts(pagination.current_page + 1, pagination.per_page);
            };
        }
        nextLi.appendChild(nextLink);
        paginationControls.appendChild(nextLi);
        
        // Update perPage select value
        const paginationSize = document.getElementById('pagination-size');
        paginationSize.value = pagination.per_page.toString();
        
        // Make pagination controls visible
        document.getElementById('products-pagination').style.display = (pagination.total > 0) ? 'block' : 'none';
    }
}

// Initialize as a global object for event handlers
window.productManager = new ProductManager(); 