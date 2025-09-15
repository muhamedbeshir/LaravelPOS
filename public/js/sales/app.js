/**
 * POS System App - Bundled JS File
 * 
 * This file acts as a bundle for our ES6 modules since Laravel doesn't use a bundler by default.
 * It includes all the functionality from our modular components.
 */

// ProductManager functionality
class ProductManager {
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
        
        // Add event listener for price type change to update existing products
        const priceTypeSelect = document.getElementById('price-type');
        if (priceTypeSelect) {
            priceTypeSelect.addEventListener('change', () => this.updateInvoiceItemPrices());
        }
    }

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

    handleBarcodeInput() {
        const searchTerm = this.searchInput.value.trim();
        if (!searchTerm) return;
        
        // Hide search suggestions
        this.searchSuggestions.classList.add('d-none');
        
        // Show loading indicator
        this.searchButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        
        // Determine if this is a barcode or text search
        // Treat any input that is only numbers as a barcode
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
                    } else if (data.is_unit_barcode && data.unit) {
                        // Unit barcode found - add specific unit directly to cart
                        invoiceManager.addProductToInvoice(data.product, data.unit);
                        // Maintain focus on barcode input after adding via barcode scan
                        this.searchInput.focus();
                        this.searchInput.select();
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

    hideSearchSuggestions(event) {
        if (!event.target.closest('.search-group, #search-suggestions')) {
            this.searchSuggestions.classList.add('d-none');
        }
    }

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

    showCategoryProducts(categoryId, categoryName) {
        document.getElementById('category-name').textContent = categoryName || '';
        
        $('#categories-modal').modal('hide');
        $('#category-products-modal').modal('show');
        
        this.loadCategoryProducts(categoryId);
    }

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

    backToCategories() {
        $('#category-products-modal').modal('hide');
        $('#categories-modal').modal('show');
    }

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
                        // Maintain focus on barcode input after adding via barcode scan
                        this.searchInput.focus();
                        this.searchInput.select();
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

    showUnitSelectionModal(product) {
        const tbody = document.getElementById('units-table-body');
        tbody.innerHTML = '';
        
        // Store current product data globally for the event handler
        window.currentProductData = product;
        
        product.units.forEach(unit => {
            const price = this.getUnitPrice(unit);
            const isOutOfStock = unit.stock_quantity <= 0 && !window.settings.allowNegativeInventory;
            const disabledAttr = isOutOfStock ? 'disabled' : '';
            
            const row = document.createElement('tr');
            
            // Create the row HTML without inline onclick handlers
            row.innerHTML = `
                <td>${unit.name}</td>
                <td>
                    ${unit.barcodes && unit.barcodes.length > 0 ? unit.barcodes.map(b => `<span class="badge bg-secondary">${b}</span>`).join(' ') : '<span class="text-muted">لا يوجد</span>'}
                </td>
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
                // Enforce negative inventory restriction directly here
                const stockQty = parseFloat(unit.stock_quantity || unit.stock || 0);
                if (!window.settings.allowNegativeInventory && stockQty <= 0) {
                    if (typeof window.showError === 'function') {
                        showError('لا يمكن بيع هذا المنتج لأن الكمية في المخزون غير كافية.');
                    } else {
                        alert('لا يمكن بيع هذا المنتج لأن الكمية في المخزون غير كافية.');
                    }
                    return;
                }
                
                // Make sure unit has proper structure for invoice manager
                const unitForInvoice = {...unit};
                if (!unitForInvoice.unit) {
                    unitForInvoice.unit = { name: unitForInvoice.name };
                }
                
                // Add to invoice
                invoiceManager.addProductToInvoice(product, unitForInvoice);
            });
        });
        
        $('#product-units-modal').modal('show');
    }

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

    getPriceType() {
        const priceTypeSelect = document.getElementById('price-type');
        return priceTypeSelect ? priceTypeSelect.value : 'retail';
    }

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

    updateInvoiceItemPrices() {
        console.log('Updating invoice item prices based on new price type');
        const tbody = document.getElementById('invoice-items').querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        
        // If there are no rows, there's nothing to update
        if (rows.length === 0) {
            return;
        }
        
        // Get the selected price type
        const priceType = this.getPriceType();
        console.log('New price type:', priceType);
        
        // Show loading indicator
        Swal.fire({
            title: 'جاري تحديث الأسعار...',
            text: 'يرجى الانتظار',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Create an array to hold all fetch promises
        const promises = [];
        
        // For each row, fetch updated product information
        rows.forEach(row => {
            const productId = row.dataset.productId;
            const unitId = row.dataset.unitId;
            
            if (productId && unitId) {
                // Fetch updated pricing information
                const promise = fetch(`/sales/product/${productId}?price_type=${priceType}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Find the matching unit
                            const unit = data.product.units.find(u => u.id == unitId || u.unit_id == unitId);
                            
                            if (unit) {
                                // Get the new price
                                const newPrice = this.getUnitPrice(unit);
                                
                                // Update the price input in the row
                                const priceInput = row.querySelector('.price-input');
                                if (priceInput) {
                                    priceInput.value = newPrice.toFixed(2);
                                    
                                    // Trigger the input event to recalculate totals
                                    priceInput.dispatchEvent(new Event('input'));
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error(`Error updating price for product ${productId}:`, error);
                    });
                
                promises.push(promise);
            }
        });
        
        // Wait for all fetch operations to complete
        Promise.all(promises)
            .then(() => {
                // Hide loading indicator
                Swal.close();
                
                // Show success message
                showSuccess('تم تحديث الأسعار بنجاح');
            })
            .catch(error => {
                console.error('Error updating prices:', error);
                Swal.close();
                showError('حدث خطأ أثناء تحديث الأسعار');
            });
    }
}

// InvoiceManager functionality
class InvoiceManager {
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

    addProductToInvoice(product, unit) {
        // Close product units modal if open
        $('#product-units-modal').modal('hide');
        
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
        
        // Create new row for this product
        const row = document.createElement('tr');
        this.itemCount++;
        
        // Get price based on price type
        const price = productManager.getUnitPrice(unit);
        
        // Determine if price is editable
        const priceEditableAttr = window.settings.allowPriceEditDuringSale ? '' : 'readonly';
        
        // Add data attributes
        row.dataset.productId = product.id;
        row.dataset.unitId = unit.id;
        row.dataset.cost = unit.cost || 0;
        // Create row HTML (abbreviated)
        row.innerHTML = `
            <td class="align-middle text-center">
                ${product.image_url ? 
                    `<img src="${product.image_url}" alt="${product.name}" width="40" height="40" class="img-thumbnail">` :
                    '<i class="fas fa-box text-primary fa-2x"></i>'
                }
            </td>
            <td class="product-name">
                <div class="fw-bold">${product.name || 'منتج بدون اسم'}</div>
                <small class="text-muted">
                    ${unit.unit && unit.unit.name ? unit.unit.name : (unit.name || '')}
                    ${product.barcode ? ' | ' + product.barcode : ''}
                </small>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm price-input" value="${price.toFixed(2)}" min="0" step="0.01" ${priceEditableAttr}>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm quantity-input" value="1" min="0.01" step="0.01">
            </td>
            <td class="subtotal">${price.toFixed(2)}</td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control form-control-sm discount-input" value="0" min="0">
                    <select class="form-select form-select-sm discount-type">
                        <option value="percentage">%</option>
                        <option value="fixed">جنيه</option>
                    </select>
                </div>
            </td>
            <td class="total">${price.toFixed(2)}</td>
            <td class="profit-column" style="${!window.settings.showProfitInSalesTable ? 'display:none;' : ''}">0.00</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="invoiceManager.removeInvoiceRow(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        
        // Add the row to the invoice table
        this.invoiceTable.querySelector('tbody').appendChild(row);
        
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

    findExistingProductRow(productId, unitId) {
        const rows = this.invoiceTable.querySelectorAll('tbody tr');
        for (const row of rows) {
            if (row.dataset.productId == productId && row.dataset.unitId == unitId) {
                return row;
            }
        }
        return null;
    }

    removeInvoiceRow(button) {
        const row = button.closest('tr');
        row.classList.add('fadeOut');
        
        // Use animation to remove row smoothly
        setTimeout(() => {
            row.remove();
            this.updateTotals();
        }, 300);
    }

    calculateRowTotal(row) {
        // Similar implementation as InvoiceManager.calculateRowTotal
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
        const discountType = row.querySelector('.discount-type').value;
        
        // Calculate subtotal (before discount)
        const subtotal = quantity * price;
        
        // Calculate discount amount
        let discountAmount = 0;
        if (discountType === 'percentage') {
            discountAmount = subtotal * (discount / 100);
        } else {
            discountAmount = discount;
        }
        
        // Calculate total (after discount)
        const total = Math.max(subtotal - discountAmount, 0);
        
        // Update row cells
        row.querySelector('.subtotal').textContent = subtotal.toFixed(2);
        row.querySelector('.total').textContent = total.toFixed(2);
        
        // Calculate and update profit if shown
        if (window.settings.showProfitInSalesTable) {
            this.calculateRowProfit(row);
        }
        
        // Update invoice totals
        this.updateTotals();
    }

    calculateRowProfit(row) {
        // Get values
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
        const discountType = row.querySelector('.discount-type').value;
        const cost = parseFloat(row.dataset.cost) || 0;

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

        let profit;

        // If cost is not available (zero or less), profit is 100% (i.e., total revenue)
        if (cost <= 0) {
            profit = total;
        } else {
            // Calculate total cost
            const totalCost = quantity * cost;
            // Calculate profit
            profit = total - totalCost;
        }

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
        if (this.totalsSubtotalElement) this.totalsSubtotalElement.textContent = subtotal.toFixed(2);
        if (this.totalsFinalElement) this.totalsFinalElement.textContent = total.toFixed(2);
        if (this.totalsItemsElement) this.totalsItemsElement.textContent = itemsCount;
        if (this.totalsDiscountElement) this.totalsDiscountElement.textContent = invoiceDiscountAmount.toFixed(2);
        
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
        // Update preview remaining in settings tab if available
        if (window.updatePreviewRemaining) {
            window.updatePreviewRemaining();
        }
    }
    
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
        
        // ===== Mixed Payments validation & data =====
        let paymentsArray = [];
        if (invoiceType === 'mixed') {
            paymentsArray = window.collectMixedPayments ? window.collectMixedPayments() : [];

            // Ensure at least two payments
            if (!Array.isArray(paymentsArray) || paymentsArray.length < 2) {
                showError('يجب إضافة دفعتين على الأقل');
                return;
            }

            // Verify sum equals invoice total (after discounts)
            const sumPayments = paymentsArray.reduce((acc, cur) => acc + (parseFloat(cur.amount) || 0), 0);
            const invoiceTotalCurrent = parseFloat(this.totalElement.textContent) || 0;
            if (Math.abs(sumPayments - invoiceTotalCurrent) > 0.01) {
                showError('إجمالي مبالغ الدفعات لا يطابق إجمالي الفاتورة');
                return;
            }
            // Override paid amount to accurate sum
            this.paidAmountInput.value = sumPayments.toFixed(2);
        }

        // Prepare invoice data
        const invoiceData = {
            invoice_type: invoiceType,
            order_type: orderType,
            customer_id: customerId,
            discount_value: this.discountTypeSelect.value === 'percentage' ? 0 : parseFloat(this.discountInput.value) || 0,
            discount_percentage: this.discountTypeSelect.value === 'percentage' ? parseFloat(this.discountInput.value) || 0 : 0,
            paid_amount: parseFloat(this.paidAmountInput.value) || 0,
            price_type_code: this.getPriceType(),
            delivery_employee_id: orderType === 'delivery' ? document.getElementById('delivery-employee').value : null,
            total_amount: 0,
            items: [],
            payments: paymentsArray
        };
        
        // Calculate total amount before sending
        const subtotal = Array.from(this.invoiceTable.querySelectorAll('tbody tr')).reduce((acc, row) => {
            return acc + (parseFloat(row.querySelector('.total').textContent) || 0);
        }, 0);
        
        const discountInputVal = parseFloat(this.discountInput.value) || 0;
        let totalDiscount = 0;
        if (this.discountTypeSelect.value === 'fixed') {
            totalDiscount = discountInputVal;
        } else {
            totalDiscount = (subtotal * discountInputVal) / 100;
        }
        const totalAmount = subtotal - totalDiscount;
        
        // Add items to invoice
        this.invoiceTable.querySelectorAll('tbody tr').forEach(row => {
            const productId = row.dataset.productId;
            const unitId = row.dataset.unitId;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const discountValue = row.querySelector('.discount-type').value === 'percentage' ? 0 : parseFloat(row.querySelector('.discount-input').value) || 0;
            const discountPercentage = row.querySelector('.discount-type').value === 'percentage' ? parseFloat(row.querySelector('.discount-input').value) || 0 : 0;
            const subTotal = (price * quantity) - (row.querySelector('.discount-type').value === 'fixed' ? discountValue : (price * quantity * discountPercentage / 100));
            
            invoiceData.items.push({
                product_id: productId,
                unit_id: unitId,
                unit_price: price,
                quantity: quantity,
                discount_value: discountValue,
                discount_percentage: discountPercentage,
                sub_total: subTotal
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
                    
                    fetch(`/api/suspended-sales/${resumedId}`, {
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
                
                // تحديث رقم الفاتورة الحالية بعد الحفظ
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
        if (this.totalProfitElement.classList) {
            this.totalProfitElement.classList.remove("text-danger");
            this.totalProfitElement.classList.add("text-success");
        }
        if (this.profitPercentageElement.classList) {
            this.profitPercentageElement.classList.remove("text-danger");
            this.profitPercentageElement.classList.add("text-success");
        }
        
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

    getPriceType() {
        const priceTypeSelect = document.getElementById('price-type');
        return priceTypeSelect ? priceTypeSelect.value : 'retail';
    }

    loadSuspendedSale(id) {
        // Show loading state
        showSuccess('جاري تحميل الفاتورة المعلقة...');
        
        fetch(`/api/suspended-sales/${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.suspended_sale) {
                    const sale = data.suspended_sale;
                    
                    // Reset the invoice first
                    this.resetInvoice({ keepResumedId: true });
                    
                    // Set the resumed sale ID
                    window.g_resumedSuspendedSaleId = sale.id;
                    
                    // Populate form fields
                    if (sale.customer_id) {
                        document.getElementById('customer-id').value = sale.customer_id;
                        document.getElementById('customer-id').dispatchEvent(new Event('change'));
                    }
                    
                    if (sale.invoice_type) {
                        document.getElementById('invoice-type').value = sale.invoice_type;
                        window.handleInvoiceTypeChange();
                    }
                    
                    if (sale.order_type) {
                        document.getElementById('order-type').value = sale.order_type;
                        window.handleOrderTypeChange();
                    }
                    
                    if (sale.price_type_code) {
                        const priceTypeSelect = document.getElementById('price-type');
                        if (priceTypeSelect) {
                            priceTypeSelect.value = sale.price_type_code;
                        }
                    }
                    
                    if (sale.delivery_employee_id) {
                        const deliveryEmployeeSelect = document.getElementById('delivery-employee');
                        if (deliveryEmployeeSelect) {
                            deliveryEmployeeSelect.value = sale.delivery_employee_id;
                            deliveryEmployeeSelect.dispatchEvent(new Event('change'));
                        }
                    }
                    
                    // Set discount
                    if (sale.discount_value > 0) {
                        this.discountInput.value = sale.discount_value;
                    }
                    
                    // Set paid amount
                    if (sale.paid_amount > 0) {
                        this.paidAmountInput.value = sale.paid_amount;
                    }
                    
                    // Add items to invoice
                    if (sale.items && sale.items.length > 0) {
                        sale.items.forEach(item => {
                            // Find the product unit that matches the item's unit
                            const productUnit = item.product.units.find(unit => unit.id == item.unit_id);
                            
                            if (productUnit) {
                                // Create a product object with the necessary data
                                const product = {
                                    id: item.product.id,
                                    name: item.product.name,
                                    image_url: item.product.image
                                };
                                
                                // Create a unit object with the necessary data
                                const unit = {
                                    id: productUnit.id,
                                    unit_id: productUnit.unit_id,
                                    cost: item.cost_price || 0,
                                    unit: {
                                        name: item.unit.name
                                    }
                                };
                                
                                // Add the product to invoice
                                this.addProductToInvoice(product, unit);
                                
                                // Set the specific values for this item
                                const row = this.findExistingProductRow(product.id, unit.id);
                                if (row) {
                                    const quantityInput = row.querySelector('.quantity-input');
                                    const priceInput = row.querySelector('.price-input');
                                    const discountInput = row.querySelector('.discount-input');
                                    const discountTypeSelect = row.querySelector('.discount-type');
                                    
                                    quantityInput.value = item.quantity;
                                    priceInput.value = item.unit_price;
                                    discountInput.value = item.discount_value || 0;
                                    
                                    // Set discount type (assuming percentage if discount_percentage > 0)
                                    if (item.discount_percentage > 0) {
                                        discountTypeSelect.value = 'percentage';
                                    } else {
                                        discountTypeSelect.value = 'fixed';
                                    }
                                    
                                    // Trigger calculations
                                    this.calculateRowTotal(row);
                                }
                            }
                        });
                    }
                    
                    // Update totals
                    this.updateTotals();
                    this.calculateRemaining();
                    
                    // Close the suspended sales modal
                    $('#suspended-sales-modal').modal('hide');
                    
                    showSuccess('تم تحميل الفاتورة المعلقة بنجاح.');
                } else {
                    showError('حدث خطأ أثناء تحميل الفاتورة المعلقة.');
                }
            })
            .catch(error => {
                console.error('Error loading suspended sale:', error);
                showError('حدث خطأ أثناء تحميل الفاتورة المعلقة.');
            });
    }
}

// SuspendedSalesManager functionality
class SuspendedSalesManager {
    constructor(invoiceManager) {
        this.invoiceManager = invoiceManager;
        this.modal = document.getElementById('suspended-sales-modal');
        this.tableBody = document.getElementById('suspended-sales-table-body');
        this.searchInput = document.getElementById('suspended-sales-search');
        this.paginationContainer = document.getElementById('suspended-sales-pagination');
        this.refreshBtn = document.getElementById('btn-refresh-suspended-sales');

        this.currentPage = 1;
        this.bindEvents();
    }

    bindEvents() {
        this.refreshBtn.addEventListener('click', () => this.loadSuspendedSales(1));
        this.searchInput.addEventListener('input', () => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.loadSuspendedSales(1), 300);
        });

        // Load sales when modal is shown
        $(this.modal).on('shown.bs.modal', () => {
            this.loadSuspendedSales(1);
        });
    }

    loadSuspendedSales(page = 1) {
        this.currentPage = page;
        const query = this.searchInput.value.trim();
        const url = `/api/suspended-sales?page=${page}&search=${encodeURIComponent(query)}`;

        this.tableBody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                this.renderTable(data.data);
                this.renderPagination(data);
            })
            .catch(error => {
                console.error('Error loading suspended sales:', error);
                this.tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">حدث خطأ أثناء تحميل الفواتير.</td></tr>';
            });
    }

    renderTable(sales) {
        this.tableBody.innerHTML = '';
        if (!sales || sales.length === 0) {
            this.tableBody.innerHTML = '<tr><td colspan="7" class="text-center">لا توجد فواتير معلقة.</td></tr>';
            return;
        }

        sales.forEach((sale, index) => {
            const row = this.tableBody.insertRow();
            const createdDate = new Date(sale.created_at).toLocaleString('ar-EG');
            row.innerHTML = `
                <td>${(this.currentPage - 1) * 15 + index + 1}</td>
                <td>${sale.reference_no}</td>
                <td>${sale.customer ? sale.customer.name : 'عميل نقدي'}</td>
                <td>${sale.user ? sale.user.name : '-'}</td>
                <td>${parseFloat(sale.total_amount).toFixed(2)}</td>
                <td>${createdDate}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="suspendedSalesManager.resumeSale(${sale.id})"><i class="fas fa-play"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="suspendedSalesManager.deleteSale(${sale.id})"><i class="fas fa-trash"></i></button>
                </td>
            `;
        });
    }
    
    renderPagination(data) {
        this.paginationContainer.innerHTML = '';
        if (!data.links || data.links.length <= 3) return;

        const nav = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination';

        data.links.forEach(link => {
            const li = document.createElement('li');
            li.className = `page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.innerHTML = link.label;
            if (link.url) {
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.loadSuspendedSales(new URL(link.url).searchParams.get('page'));
                });
            }
            li.appendChild(a);
            ul.appendChild(li);
        });

        nav.appendChild(ul);
        this.paginationContainer.appendChild(nav);
    }
    
    resumeSale(id) {
       this.invoiceManager.loadSuspendedSale(id);
    }

    deleteSale(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن تتمكن من التراجع عن هذا!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، احذفه!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/api/suspended-sales/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('تم حذف الفاتورة المعلقة بنجاح.');
                        this.loadSuspendedSales(this.currentPage);
                    } else {
                        showError(data.message || 'حدث خطأ أثناء الحذف.');
                    }
                })
                .catch(error => {
                    console.error('Error deleting sale:', error);
                    showError('حدث خطأ أثناء الحذف.');
                });
            }
        });
    }
}

// Main initialization function (from main.js)
document.addEventListener('DOMContentLoaded', function() {
    // Initialize global utility functions
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
    
    // Initialize modules
    window.productManager = new ProductManager();
    window.invoiceManager = new InvoiceManager();
    window.suspendedSalesManager = new SuspendedSalesManager(invoiceManager);
    
    // Handle invoice type change - ROBUST VERSION
    window.handleInvoiceTypeChange = function() {
        const invoiceTypeSelect = document.getElementById('invoice-type');
        if (!invoiceTypeSelect) {
            return;
        }
        const invoiceType = invoiceTypeSelect.value;

        const iconContainer = document.getElementById('mixed-payments-icon-container');
        if (!iconContainer) {
            return;
        }

        // Logic starts here
        const cashFields = document.querySelectorAll('.cash-field');
        const creditFields = document.querySelectorAll('.credit-field');
        const customerSelect = document.getElementById('customer-id');
        const paidAmountParent = document.getElementById('paid-amount')?.parentElement;

        // Default state: hide the icon and mixed container. We only show it for 'mixed'.
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
    
    // Handle order type changes
    window.handleOrderTypeChange = function() {
        const orderType = document.getElementById('order-type').value;
        const deliveryFields = document.querySelectorAll('.delivery-field');
        const deliveryButtons = document.getElementById('delivery-buttons-bar');
        const deliveryStatusBtn = document.getElementById('delivery-status-btn');
        const quickDeliveryStatusBtn = document.getElementById('delivery-status-btn-quick');
        
        if (orderType === 'delivery') {
            deliveryFields.forEach(field => field.classList.remove('d-none'));
            if (deliveryButtons) deliveryButtons.classList.remove('d-none');
            
            // إظهار زر حالة الدليفري
            if (deliveryStatusBtn) deliveryStatusBtn.style.display = 'block';
            if (quickDeliveryStatusBtn) quickDeliveryStatusBtn.style.display = 'block';
        } else {
            deliveryFields.forEach(field => field.classList.add('d-none'));
            if (deliveryButtons) deliveryButtons.classList.add('d-none');
            
            // إخفاء زر حالة الدليفري
            if (deliveryStatusBtn) deliveryStatusBtn.style.display = 'none';
            if (quickDeliveryStatusBtn) quickDeliveryStatusBtn.style.display = 'none';
        }
    };
    
    // Set up invoice type change event
    document.getElementById('invoice-type').addEventListener('change', window.handleInvoiceTypeChange);
    window.handleInvoiceTypeChange();
    
    // Set up order type change event
    document.getElementById('order-type').addEventListener('change', window.handleOrderTypeChange);
    window.handleOrderTypeChange();
    
    // Set up customer change event
    const customerSelect = document.getElementById('customer-id');
    customerSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const creditBalanceElement = document.getElementById('customer-credit-balance');
        const creditLimitElement = document.getElementById('customer-credit-limit');
        
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
            creditLimitElement.textContent = parseFloat(creditLimit).toFixed(2);
        }
    });
    
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    // ===== Mixed Payments Helpers =====
    // Ensure helpers are defined only once
    if (!window.addPaymentRow) {
        function addPaymentRow(method = 'cash', amount = 0) {
            console.log('addPaymentRow called');
            const tbody = document.getElementById('mixed-payments-body');
            if (!tbody) return;

            const tr = document.createElement('tr');

            // Method select
            const tdMethod = document.createElement('td');
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm payment-method-select';
            ['cash','credit','visa','transfer'].forEach(function(opt){
                const o = document.createElement('option');
                o.value = opt;
                o.textContent = opt === 'cash' ? 'كاش' : (opt === 'credit' ? 'آجل' : (opt === 'visa' ? 'فيزا' : 'تحويل'));
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

            const paidInput = document.getElementById('paid-amount');
            if (paidInput) paidInput.value = sum.toFixed(2);
            const totalField = parseFloat(document.getElementById('total').textContent) || 0;
            const remaining = totalField - sum;
            const remainingElement = document.getElementById('remaining');
            if (remainingElement) remainingElement.textContent = remaining.toFixed(2);

            // Update badge on button (if exists)
            const badge = document.getElementById('mixed-payments-count');
            if (badge) badge.textContent = inputs.length;
        }

        function ensureMixedRows() {
            const rowsCount = document.querySelectorAll('#mixed-payments-body tr').length;
            if (rowsCount === 0) {
                addPaymentRow();
            }
        }

        // Export helper
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

        // Expose globally
        window.addPaymentRow = addPaymentRow;
        window.calculateMixedTotals = calculateMixedTotals;
        window.ensureMixedRows = ensureMixedRows;

        // Delegated click listener for dynamically injected button(s)
        document.addEventListener('click', function(e){
            const target = e.target.closest('#add-payment-row, #add-payment-row-btn');
            if(target){
                e.preventDefault();
                addPaymentRow();
            }
        });

        // Ensure at least one row when modal is shown
        const mixedModal = document.getElementById('mixed-payments-modal');
        if (mixedModal) {
            mixedModal.addEventListener('shown.bs.modal', function () {
                ensureMixedRows();
            });
        }
    }
    // ===== End Mixed Payments Helpers =====
}); 