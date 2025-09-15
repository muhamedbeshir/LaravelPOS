/**
 * Customer Helper Functions for POS System
 * Contains all functionality related to customer management
 */

// Customer Helper Module
(function(window) {
    // Keep track of all customers for local searching
    let allCustomers = [];
    
    /**
     * Initialize customer helper functionality
     */
    function initialize() {
        console.log('Initializing customer helper with search functionality');
        
        // Get all customers from the select element
        $('#customer-id option').each(function() {
            if ($(this).val() != 1) { // Skip the cash customer
                allCustomers.push({
                    id: $(this).val(),
                    name: $(this).text().trim(),
                    credit: $(this).data('credit'),
                    creditLimit: $(this).data('credit-limit'),
                    isUnlimitedCredit: $(this).data('is-unlimited-credit'),
                    address: $(this).data('address'),
                    defaultPriceTypeCode: $(this).data('default-price-type-code'),
                    // Use the text content for search, which includes the price type if present
                    searchText: $(this).text().toLowerCase().trim()
                });
            }
        });
        
        // Handle unlimited credit toggle
        $('#customer-unlimited-credit').on('change', function() {
            const isChecked = $(this).prop('checked');
            console.log('Unlimited credit checkbox changed:', isChecked);
            
            $('#customer-has-unlimited-credit').val(isChecked ? '1' : '0');
            console.log('Set hidden field value to:', $('#customer-has-unlimited-credit').val());
            
            if (isChecked) {
                // Store the current value before disabling
                $('#customer-credit-limit').data('previous-value', $('#customer-credit-limit').val());
                console.log('Stored previous credit limit value:', $('#customer-credit-limit').data('previous-value'));
                
                // Disable the input with a default value
                $('#customer-credit-limit').val('0').prop('disabled', true);
            } else {
                // Restore the previous value if available, otherwise set to 0
                const previousValue = $('#customer-credit-limit').data('previous-value') || '0';
                console.log('Restoring previous credit limit value:', previousValue);
                
                $('#customer-credit-limit').val(previousValue).prop('disabled', false);
            }
        });
        
        // Reset form when modal is hidden
        $('#add-customer-modal').on('hidden.bs.modal', function() {
            $('#add-customer-form').trigger('reset');
            $('#customer-credit-limit').prop('disabled', false);
            $('#customer-unlimited-credit').prop('checked', false);
            $('#customer-has-unlimited-credit').val('0');
        });
        
        // Setup search functionality
        $('#customer-search').on('keyup', function(e) {
            if (e.key === 'Enter') {
                searchCustomers();
            }
        });
        
        $('#search-customer-btn').on('click', searchCustomers);
        
        // Handle customer selection from modal
        $(document).on('click', '.select-customer-btn', function() {
            const customerId = $(this).data('id');
            const customerName = $(this).data('name');
            
            // Select this customer in the dropdown
            $('#customer-id').val(customerId).trigger('change');
            
            // Update credit info if available
            updateCustomerCreditInfo(
                $(this).data('credit'), 
                $(this).data('credit-limit'),
                $(this).data('is-unlimited-credit')
            );
            
            // Close the modal
            $('#select-customer-modal').modal('hide');
        });
        
        // Restore original customer select behavior
        $('#customer-id').on('change', function() {
            handleCustomerChange(this);
        });
    }
    
    /**
     * Handle customer change to update credit info display
     */
    function handleCustomerChange(selectElement) {
        const $selectedOption = $(selectElement).find('option:selected');
        const credit = $selectedOption.data('credit') || 0;
        const creditLimit = $selectedOption.data('credit-limit') || 0;
        const isUnlimitedCredit = $selectedOption.data('is-unlimited-credit') || 0;
        
        updateCustomerCreditInfo(credit, creditLimit, isUnlimitedCredit);
        
        // Update default price type if applicable
        const priceTypeCode = $selectedOption.data('default-price-type-code');
        if (priceTypeCode && typeof updatePriceType === 'function') {
            updatePriceType(priceTypeCode);
        }
    }
    
    /**
     * Update customer credit info display
     */
    function updateCustomerCreditInfo(credit, creditLimit, isUnlimitedCredit) {
        // Convert to numbers
        const creditValue = parseFloat(credit) || 0;
        const limitValue = parseFloat(creditLimit) || 0;
        
        // Calculate available credit
        let availableCredit = 0;
        if (parseInt(isUnlimitedCredit) === 1) {
            availableCredit = "غير محدود";
        } else {
            availableCredit = limitValue - creditValue;
        }
        
        // Update displayed values
        $('#customer-current-credit').text(creditValue.toFixed(2));
        $('#customer-credit-limit').text(parseInt(isUnlimitedCredit) === 1 ? "غير محدود" : limitValue.toFixed(2));
        $('#customer-available-credit').text(availableCredit === "غير محدود" ? availableCredit : availableCredit.toFixed(2));
        
        // Show or hide credit info based on customer selection
        if ($('#customer-id').val() != 1) {
            $('#customer-credit-info').removeClass('d-none');
        } else {
            $('#customer-credit-info').addClass('d-none');
        }
    }
    
    /**
     * Search customers based on input
     */
    function searchCustomers() {
        const searchQuery = $('#customer-search').val().toLowerCase().trim();
        
        if (!searchQuery) {
            // If empty, show all customers
            displayCustomers(allCustomers);
            return;
        }
        
        // Filter customers based on search query
        const filteredCustomers = allCustomers.filter(customer => 
            customer.searchText.includes(searchQuery)
        );
        
        displayCustomers(filteredCustomers);
    }
    
    /**
     * Display filtered customers in the table
     */
    function displayCustomers(customers) {
        const $tbody = $('#customers-table tbody');
        $tbody.empty();
        
        if (customers.length === 0) {
            $('#no-customers-found').show();
            return;
        }
        
        $('#no-customers-found').hide();
        
        customers.forEach(customer => {
            $tbody.append(`
                <tr data-id="${customer.id}">
                    <td>${customer.name}</td>
                    <td>-</td>
                    <td>${customer.address || '-'}</td>
                    <td>${customer.credit || '0.00'}</td>
                    <td>
                        <button class="btn btn-sm btn-success select-customer-btn"
                            data-id="${customer.id}"
                            data-name="${customer.name}"
                            data-credit="${customer.credit || '0.00'}"
                            data-credit-limit="${customer.creditLimit || '0.00'}"
                            data-is-unlimited-credit="${customer.isUnlimitedCredit || '0'}"
                            data-address="${customer.address || ''}"
                            data-default-price-type-code="${customer.defaultPriceTypeCode || ''}">
                            <i class="fas fa-check"></i> اختيار
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    /**
     * Save new customer from modal form
     */
    function saveNewCustomer() {
        // Get form data
        const customerName = $('#customer-name').val();
        const customerPhone = $('#customer-phone').val();
        const customerAddress = $('#customer-address').val();
        const customerNotes = $('#customer-notes').val();
        const creditLimit = $('#customer-credit-limit').val();
        const hasUnlimitedCredit = $('#customer-has-unlimited-credit').val();
        const defaultPriceTypeId = $('#customer-default-price-type').val();
        
        console.log('Saving customer with data:', {
            name: customerName,
            phone: customerPhone,
            address: customerAddress, 
            notes: customerNotes,
            credit_limit: creditLimit,
            has_unlimited_credit: hasUnlimitedCredit,
            default_price_type_id: defaultPriceTypeId
        });
        
        // Validate required fields
        if (!customerName || !customerPhone) {
            showError('يرجى ملء جميع الحقول المطلوبة');
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'جاري حفظ العميل...',
            text: 'يرجى الانتظار',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Send request to save customer
        $.ajax({
            url: '/customers',
            type: 'POST',
            data: {
                name: customerName,
                phone: customerPhone,
                address: customerAddress,
                notes: customerNotes,
                credit_limit: creditLimit,
                has_unlimited_credit: hasUnlimitedCredit,
                default_price_type_id: defaultPriceTypeId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    console.log('Customer created successfully:', response.customer);
                    // Close modal
                    $('#add-customer-modal').modal('hide');
                    
                    // Reset form
                    $('#add-customer-form').trigger('reset');
                    $('#customer-credit-limit').prop('disabled', false);
                    $('#customer-unlimited-credit').prop('checked', false);
                    $('#customer-has-unlimited-credit').val('0');
                    
                    // Add the new customer to the dropdown
                    const newCustomer = response.customer;
                    const newOption = new Option(
                        newCustomer.name, 
                        newCustomer.id, 
                        true, 
                        true
                    );
                    
                    // Set data attributes for credit info
                    $(newOption).data('credit', '0.00');
                    $(newOption).data('creditLimit', newCustomer.credit_limit || '0.00');
                    $(newOption).attr('data-credit', '0.00');
                    $(newOption).attr('data-credit-limit', newCustomer.credit_limit || '0.00');
                    $(newOption).attr('data-is-unlimited-credit', newCustomer.is_unlimited_credit ? '1' : '0');
                    $(newOption).attr('data-address', newCustomer.address || '');
                    $(newOption).attr('data-default-price-type-code', (newCustomer.default_price_type && newCustomer.default_price_type.code) ? newCustomer.default_price_type.code : '');
                    
                    // Add to select and trigger change
                    $('#customer-id').append(newOption).trigger('change');
                    
                    // Add to local customers array for search
                    allCustomers.push({
                        id: newCustomer.id,
                        name: newCustomer.name,
                        credit: '0.00',
                        creditLimit: newCustomer.credit_limit || '0.00',
                        isUnlimitedCredit: newCustomer.is_unlimited_credit ? '1' : '0',
                        address: newCustomer.address || '',
                        defaultPriceTypeCode: (newCustomer.default_price_type && newCustomer.default_price_type.code) ? newCustomer.default_price_type.code : '',
                        searchText: newCustomer.name.toLowerCase()
                    });
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'تم إضافة العميل بنجاح',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    showError(response.message || 'حدث خطأ أثناء إضافة العميل');
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء إضافة العميل';
                
                // Check for validation errors
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
                
                showError(errorMessage);
            }
        });
    }

    // Public API
    window.customerHelper = {
        initialize: initialize,
        saveNewCustomer: saveNewCustomer,
        handleCustomerChange: handleCustomerChange
    };
})(window); 