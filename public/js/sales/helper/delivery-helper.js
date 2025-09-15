/**
 * Delivery Helper Functions for POS System
 * Contains all functionality related to delivery order management
 */

// Delivery Helper Module
(function(window) {
    // Store active delivery order timers
    var deliveryOrderTimers = [];

    /**
     * Helper function to show loading indicator
     */
    function showLoading() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'جاري التحميل...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    }

    /**
     * Helper function to hide loading indicator
     */
    function hideLoading() {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
    }

    /**
     * Helper function to show error message
     */
    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: message,
                confirmButtonText: 'حسناً'
            });
        } else {
            alert('خطأ: ' + message);
        }
    }

    /**
     * Helper function to show success message
     */
    function showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'تم بنجاح',
                text: message,
                confirmButtonText: 'حسناً',
                timer: 2000,
                timerProgressBar: true
            });
        } else {
            alert('تم بنجاح: ' + message);
        }
    }

    /**
     * Toggle delivery-related UI elements based on selected order type
     */
    function handleOrderTypeChange() {
        const orderType = $('#order-type').val();
        
        if (orderType === 'delivery') {
            $('.delivery-section').removeClass('d-none');
            $('#delivery-status-btn').show();
        } else {
            $('.delivery-section').addClass('d-none');
            $('#delivery-status-btn').hide();
            $('#delivery-employee').val('').trigger('change');
        }
    }

    /**
     * Load all delivery orders for the current shift
     */
    function loadDeliveryOrders() {
        // Clear any existing timers
        clearDeliveryOrderTimers();
        
        // Show loading
        showLoading();
        
        // Fetch delivery transactions for current shift
        $.ajax({
            url: '/delivery-transactions/current-shift',
            type: 'GET',
            success: function(response) {
                // Hide loading
                hideLoading();
                
                // Get the table body
                const tableBody = $('#delivery-orders-table-body');
                
                // Clear existing content
                tableBody.empty();
                
                console.log('Loading delivery orders with data:', response);
                
                if (response.transactions && response.transactions.length > 0) {
                    // Loop through transactions and add them to the table
                    response.transactions.forEach(function(transaction, index) {
                        console.log(`Processing transaction ${index + 1}:`, transaction);
                        
                        // Format the customer information
                        const customerInfo = transaction.customer ? 
                            `${transaction.customer.name}<br><small class="text-muted">${transaction.customer.phone || ''}</small>` : 
                            'غير محدد';
                        
                        // Get status code from status object if present
                        let statusCode = transaction.status;
                        if (typeof statusCode === 'object' && statusCode !== null) {
                            statusCode = statusCode.code || statusCode.value || 'unknown';
                        }
                        console.log(`Transaction ${index + 1} status:`, transaction.status, 'Extracted code:', statusCode);
                        
                        // Format status with appropriate color and icon
                        const statusInfo = formatDeliveryStatus(transaction.status);
                        
                        // ELAPSED TIME - Calculate time since dispatch for dispatched orders
                        // This should appear in the "الوقت المنقضي منذ الخروج" column (not in expected delivery time)
                        let elapsedTimeDisplay = '—';
                        if (transaction.dispatched_at) {
                            try {
                                const dispatchDate = new Date(transaction.dispatched_at);
                                if (!isNaN(dispatchDate.getTime())) {
                                    const elapsedTime = formatDuration(new Date() - dispatchDate);
                                    // This will be displayed in the "الوقت المنقضي منذ الخروج" column
                                    elapsedTimeDisplay = `<span class="elapsed-timer" data-dispatched-at="${transaction.dispatched_at}" data-transaction-id="${transaction.id}">${elapsedTime}</span>`;
                                }
                            } catch(e) {
                                console.error('Error calculating elapsed time:', e);
                            }
                        }
                        
                        // DELIVERY DURATION - For completed orders, time between dispatch and delivery
                        // This should appear in the "وقت التوصيل المتوقع/الفعلي" column
                        let deliveryTimeDisplay = '—';
                        
                        // For debugging
                        console.log(`Transaction ${index + 1} status check:`, 
                            'Status:', statusCode, 
                            'Is completed:', ['delivered_pending_payment', 'paid', 'returned'].includes(statusCode),
                            'Has dispatched_at:', !!transaction.dispatched_at,
                            'dispatched_at:', transaction.dispatched_at,
                            'Has delivered_at:', !!transaction.delivered_at,
                            'delivered_at:', transaction.delivered_at);
                        
                        // Look for delivered_at timestamp in various possible locations
                        const deliveredAt = transaction.delivered_at || 
                                         (transaction.delivery_data && transaction.delivery_data.delivered_at) ||
                                         (transaction.status_history && transaction.status_history.delivered) ||
                                         transaction.updated_at; // fallback to updated_at for completed orders
                        
                        // Check if we have a valid delivery status and should show duration
                        const isCompletedOrder = ['delivered_pending_payment', 'paid', 'returned'].includes(statusCode);
                        
                        if (isCompletedOrder && transaction.dispatched_at) {
                            try {
                                const dispatchDate = new Date(transaction.dispatched_at);
                                
                                // If we don't have an explicit delivered_at time, but order is completed,
                                // use updated_at as a fallback
                                let deliveryDate;
                                if (deliveredAt) {
                                    deliveryDate = new Date(deliveredAt);
                                } else if (transaction.updated_at) {
                                    deliveryDate = new Date(transaction.updated_at);
                                    console.log(`Using updated_at as fallback for delivery time:`, transaction.updated_at);
                                } else {
                                    // If no delivery time found, use current time as fallback
                                    deliveryDate = new Date();
                                    console.log(`No delivery timestamp found, using current time as fallback`);
                                }
                                
                                // Calculate total duration
                                if (!isNaN(dispatchDate.getTime()) && !isNaN(deliveryDate.getTime())) {
                                    const totalDuration = formatDuration(deliveryDate - dispatchDate);
                                    deliveryTimeDisplay = `<span class="badge bg-success">${totalDuration}</span>`;
                                    console.log(`Calculated delivery duration: ${totalDuration}`);
                                } else {
                                    console.warn(`Invalid dates for duration calculation:`, 
                                        'dispatchDate:', dispatchDate, 
                                        'deliveryDate:', deliveryDate);
                                }
                            } catch(e) {
                                console.error('Error calculating final delivery time:', e);
                            }
                        } else {
                            console.log(`Not calculating delivery duration: order status doesn't qualify or missing dispatch time`);
                        }
                        
                        // Add the row to the table - IMPORTANT: Swap the 8th and 9th columns
                        tableBody.append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${transaction.invoice_number || transaction.id}</td>
                                <td>${customerInfo}</td>
                                <td>${transaction.customer?.phone || '—'}</td>
                                <td>${transaction.employee?.name || 'غير محدد'}</td>
                                <td class="text-start">${formatCurrency(transaction.amount)}</td>
                                <td>${statusInfo}</td>
                                <td>${deliveryTimeDisplay}</td>
                                <td>${elapsedTimeDisplay}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="window.deliveryHelper.loadDeliveryDetails(${transaction.id})">
                                        <i class="fas fa-eye me-1"></i> عرض/تعديل
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                    
                    // Start timers for all orders that need live updates
                    startAllElapsedTimers();
                    
                } else {
                    // No transactions found
                    tableBody.append(`
                        <tr>
                            <td colspan="10" class="text-center py-3">
                                <i class="fas fa-info-circle me-2 text-info"></i>
                                لا توجد طلبات توصيل نشطة للوردية الحالية
                            </td>
                        </tr>
                    `);
                }
                
                // Show the modal
                $('#delivery-orders-modal').modal('show');
            },
            error: function(xhr) {
                hideLoading();
                showError('حدث خطأ أثناء تحميل طلبات الدليفري');
                console.error(xhr);
            }
        });
    }

    /**
     * Start all elapsed timers for any elements with the elapsed-timer class
     */
    function startAllElapsedTimers() {
        // Clear any existing timers first
        clearDeliveryOrderTimers();
        
        // Find all elapsed timer elements
        $('.elapsed-timer').each(function() {
            const dispatchedAt = $(this).data('dispatched-at');
            const transactionId = $(this).data('transaction-id');
            
            if (dispatchedAt) {
                try {
                    // Parse the dispatch date
        const dispatchDate = new Date(dispatchedAt);
                    if (isNaN(dispatchDate.getTime())) {
                        console.warn(`Invalid dispatch date: ${dispatchedAt}`);
                        return;
                    }
                    
                    // Store the element and dispatch date
                    const element = $(this);
        
                    // Update function
                    const updateTimer = function() {
                        const elapsedTime = formatDuration(new Date() - dispatchDate);
                        element.text(elapsedTime);
                    };
                    
                    // Update immediately
                    updateTimer();
        
                    // Set interval to update every minute
                    const timerId = setInterval(updateTimer, 60000);
        deliveryOrderTimers.push(timerId);
                    
                } catch(e) {
                    console.error(`Error starting timer for transaction ${transactionId}:`, e);
                }
            }
        });
    }

    /**
     * Format delivery time with appropriate formatting
     */
    function formatDeliveryTime(transaction) {
        let deliveryTime;
        let isOverdue = false;
        
        console.log('Formatting delivery time for transaction:', transaction);
        
        // Check status code from status object if present
        let statusCode = transaction.status;
        if (typeof statusCode === 'object' && statusCode !== null) {
            statusCode = statusCode.code || statusCode.value || 'unknown';
        }
        
        // For final statuses, show actual time
        if (['paid', 'returned'].includes(statusCode)) {
            deliveryTime = transaction.delivered_at || transaction.returned_at || 
                         (transaction.status_history && (transaction.status_history.paid || transaction.status_history.returned)) || 
                         '—';
        } else {
            // For in-progress orders, show expected time
            deliveryTime = transaction.expected_delivery_time || 
                         transaction.delivery_time ||
                         (transaction.invoice && transaction.invoice.delivery_time) ||
                         (transaction.delivery_data && transaction.delivery_data.expected_time) ||
                         '—';
        
            // If no expected time is set but we're in dispatched status, show the dispatch time
            if ((deliveryTime === '—' || !deliveryTime) && statusCode === 'dispatched' && transaction.dispatched_at) {
                // Calculate expected time as dispatch time + 30 minutes
                try {
                    const dispatchDate = new Date(transaction.dispatched_at);
                    const expectedDate = new Date(dispatchDate.getTime() + (30 * 60 * 1000)); // 30 minutes later
                    deliveryTime = expectedDate.toISOString();
                } catch(e) {
                    console.error('Error calculating expected time:', e);
                }
            }
            
            // Check if it's overdue
            if (deliveryTime && deliveryTime !== '—') {
                try {
                    const expectedTime = new Date(deliveryTime);
                    isOverdue = expectedTime < new Date() && !['paid', 'returned'].includes(statusCode);
                } catch(e) {
                    console.error('Error parsing delivery time:', e);
                }
            }
        }
        
        // Format as date if we have a value
        if (deliveryTime && deliveryTime !== '—') {
            try {
                deliveryTime = formatDateTime(deliveryTime);
            } catch(e) {
                console.error('Error formatting delivery time:', e);
            }
        }
        
        return isOverdue ? 
            `<span class="text-danger fw-bold">${deliveryTime} <i class="fas fa-exclamation-triangle"></i></span>` : 
            deliveryTime;
    }

    /**
     * Format time duration into human readable string
     */
    function formatDuration(ms) {
        // Calculate hours, minutes
        const hours = Math.floor(ms / (1000 * 60 * 60));
        const minutes = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60));
        
        if (hours > 0) {
            return `${hours} ساعة و ${minutes} دقيقة`;
        } else {
            return `${minutes} دقيقة`;
        }
    }

    /**
     * Format date time for display
     * @param {string} dateString - ISO date string
     * @returns {string} - Formatted date and time
     */
    function formatDateTime(dateString) {
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

    /**
     * Format currency value
     * @param {number} amount - Amount to format
     * @returns {string} - Formatted amount
     */
    function formatCurrency(amount) {
        // Handle undefined, null, or NaN amounts
        if (amount === undefined || amount === null || isNaN(amount)) {
            return '0.00';
        }
        
        // Ensure amount is a number and format it
        return parseFloat(amount).toFixed(2);
    }

    /**
     * Format delivery status with appropriate color and icon
     */
    function formatDeliveryStatus(status) {
        let statusText, badgeClass, icon;
        
        // If status is an object rather than a string, extract the value
        if (typeof status === 'object' && status !== null) {
            status = status.value || status.code || 'unknown';
        }
        
        switch (status) {
            case 'ready':
                statusText = 'جاهز للتوصيل';
                badgeClass = 'bg-info';
                icon = 'fa-box';
                break;
            case 'dispatched':
                statusText = 'خرج للتوصيل';
                badgeClass = 'bg-warning';
                icon = 'fa-motorcycle';
                break;
            case 'delivered_pending_payment':
                statusText = 'تم التوصيل (بانتظار الدفع)';
                badgeClass = 'bg-primary';
                icon = 'fa-hand-holding-box';
                break;
            case 'paid':
                statusText = 'تم الدفع';
                badgeClass = 'bg-success';
                icon = 'fa-check-circle';
                break;
            case 'returned':
                statusText = 'مرتجع';
                badgeClass = 'bg-danger';
                icon = 'fa-undo';
                break;
            default:
                statusText = status || 'غير معروف';
                badgeClass = 'bg-secondary';
                icon = 'fa-question-circle';
        }
        
        return `<span class="badge ${badgeClass}"><i class="fas ${icon} me-1"></i> ${statusText}</span>`;
    }

    /**
     * Clear all active dispatch timers
     */
    function clearDeliveryOrderTimers() {
        deliveryOrderTimers.forEach(timerId => clearInterval(timerId));
        deliveryOrderTimers.length = 0; // Clear the array
    }

    /**
     * Load details of a specific delivery transaction
     */
    function loadDeliveryDetails(transactionId) {
        showLoading();
        
        $.ajax({
            url: `/delivery-transactions/${transactionId}`,
            type: 'GET',
            success: function(response) {
                hideLoading();
                
                const transaction = response.transaction;
                console.log('Loaded transaction details:', transaction);
                
                // Get status code from status object if present
                let statusCode = transaction.status;
                if (typeof statusCode === 'object' && statusCode !== null) {
                    statusCode = statusCode.code || statusCode.value || 'unknown';
                }
                
                // Calculate elapsed time for dispatched orders
                let elapsedTimeDisplay = '—';
                if (statusCode === 'dispatched' && transaction.dispatched_at) {
                    try {
                        const dispatchDate = new Date(transaction.dispatched_at);
                        if (!isNaN(dispatchDate.getTime())) {
                            const elapsedTime = formatDuration(new Date() - dispatchDate);
                            elapsedTimeDisplay = `<span class="badge bg-info">${elapsedTime} منذ الخروج</span>`;
                        }
                    } catch(e) {
                        console.error('Error calculating elapsed time:', e);
                    }
                }
                
                // Delivery time display
                let deliveryTimeDisplay = '—';
                if (transaction.delivered_at) {
                    try {
                        deliveryTimeDisplay = formatDateTime(transaction.delivered_at);
                    } catch(e) {
                        console.error('Error formatting delivery time:', e);
                    }
                }
                
                // Populate transaction details
                $('#delivery-transaction-details').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>رقم الفاتورة:</strong> ${transaction.invoice_number || transaction.id}</p>
                            <p><strong>العميل:</strong> ${transaction.customer?.name || 'غير محدد'}</p>
                            <p><strong>موظف التوصيل:</strong> ${transaction.employee?.name || 'غير محدد'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>المبلغ الإجمالي:</strong> ${formatCurrency(transaction.amount)}</p>
                            <p><strong>المبلغ المحصل:</strong> ${formatCurrency(transaction.collected_amount || 0)}</p>
                            <p><strong>المبلغ المتبقي:</strong> ${formatCurrency((transaction.amount || 0) - (transaction.collected_amount || 0))}</p>
                            <p>
                                <strong>الحالة الحالية:</strong> 
                                ${formatDeliveryStatus(transaction.status)}
                            </p>
                            <p><strong>الوقت المنقضي:</strong> ${elapsedTimeDisplay}</p>
                            <p><strong>وقت التوصيل الفعلي:</strong> ${deliveryTimeDisplay}</p>
                        </div>
                    </div>
                `);
                
                // Set transaction ID in hidden field
                $('input[name="transaction_id"]').val(transaction.id);
                
                // Populate status dropdown based on current status
                populateStatusOptions(transaction.status);
                
                // Hide the delivery orders modal and show the status update modal
                $('#delivery-orders-modal').modal('hide');
                $('#delivery-status-modal').modal('show');
            },
            error: function(xhr) {
                hideLoading();
                showError('حدث خطأ أثناء تحميل بيانات الدليفري');
                console.error(xhr);
            }
        });
    }

    /**
     * Populate delivery status dropdown options based on current status
     */
    function populateStatusOptions(currentStatus) {
        const statusSelect = $('#delivery-status');
        statusSelect.empty();
        
        // Add debug info to see what status we're dealing with
        console.log('Current status received:', currentStatus);
        
        // Normalize the status - handle both code and object cases
        let normalizedStatus = currentStatus;
        
        // If status is an object, extract the code
        if (typeof currentStatus === 'object' && currentStatus !== null) {
            normalizedStatus = currentStatus.value || currentStatus.code || 'unknown';
            console.log('Extracted status from object:', normalizedStatus);
        }
        
        // Map Arabic status text back to codes if needed
        const statusTextToCode = {
            'جاهز للتوصيل': 'ready',
            'خرج للتوصيل': 'dispatched',
            'تم التوصيل (بانتظار الدفع)': 'delivered_pending_payment',
            'تم الدفع': 'paid',
            'مرتجع': 'returned'
        };
        
        // If the status looks like Arabic text, convert it to a code
        if (statusTextToCode[normalizedStatus]) {
            normalizedStatus = statusTextToCode[normalizedStatus];
            console.log('Converted Arabic status to code:', normalizedStatus);
        }
        
        // Define possible status transitions
        const nextStatuses = {
            'ready': [
                {value: 'dispatched', text: 'خرج للتوصيل', icon: 'fa-motorcycle'}
            ],
            'dispatched': [
                {value: 'delivered_pending_payment', text: 'تم التوصيل (بانتظار الدفع)', icon: 'fa-hand-holding-box'},
                {value: 'returned', text: 'مرتجع', icon: 'fa-undo'}
            ],
            'delivered_pending_payment': [
                {value: 'paid', text: 'تم الدفع', icon: 'fa-check-circle'},
                {value: 'returned', text: 'مرتجع', icon: 'fa-undo'}
            ],
            // Final states have no next states
            'paid': [],
            'returned': []
        };
        
        // Get available status options for the current status
        const availableStatuses = nextStatuses[normalizedStatus] || [];
        console.log('Available next statuses:', availableStatuses);
        
        // If no available statuses, disable the form
        if (availableStatuses.length === 0) {
            statusSelect.append(`<option value="">لا يمكن تغيير الحالة</option>`);
            $('#update-delivery-status-btn').prop('disabled', true);
            $('#delivery-status-form .form-control').prop('disabled', true);
        } else {
            // Add available statuses to dropdown
            availableStatuses.forEach(status => {
                statusSelect.append(`
                    <option value="${status.value}">
                        ${status.text}
                    </option>
                `);
            });
            $('#update-delivery-status-btn').prop('disabled', false);
            $('#delivery-status-form .form-control').prop('disabled', false);
        }
        
        // Trigger change event to handle form state
        statusSelect.trigger('change');
    }

    /**
     * Handle delivery status change
     */
    function handleDeliveryStatusChange() {
        const newStatus = $('#delivery-status').val();
        
        // Show/hide payment amount container based on status
        if (newStatus === 'paid') {
            $('#payment-amount-container').removeClass('d-none');
            $('#return-notes-container').addClass('d-none');
        }
        // Show/hide return notes container based on status
        else if (newStatus === 'returned') {
            $('#payment-amount-container').addClass('d-none');
            $('#return-notes-container').removeClass('d-none');
        }
        // Hide both for other statuses
        else {
            $('#payment-amount-container').addClass('d-none');
            $('#return-notes-container').addClass('d-none');
        }
    }

    /**
     * Update delivery status
     */
    function updateDeliveryStatus() {
        const transactionId = $('input[name="transaction_id"]').val();
        const newStatus = $('#delivery-status').val();
        
        // Validate
        if (!transactionId || !newStatus) {
            showError('بيانات غير مكتملة');
            return;
        }
        
        // Get payment amount if visible
        let amount = null;
        if (!$('#payment-amount-container').hasClass('d-none')) {
            amount = parseFloat($('#payment-amount').val()) || 0;
        }
        
        // Get return notes if visible
        let notes = null;
        if (!$('#return-notes-container').hasClass('d-none')) {
            notes = $('#return-notes').val();
        }
        
        // Show loading
        showLoading();
        
        // Send update request
        $.ajax({
            url: `/delivery-transactions/${transactionId}/update-status`,
            type: 'PUT',
            data: {
                status: newStatus,
                amount: amount,
                notes: notes,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoading();
                showSuccess('تم تحديث حالة الدليفري بنجاح');
                
                // Close the modal and refresh the delivery orders list
                $('#delivery-status-modal').modal('hide');
                loadDeliveryOrders();
            },
            error: function(xhr) {
                hideLoading();
                showError('حدث خطأ أثناء تحديث حالة الدليفري');
                console.error('Error updating delivery status:', xhr.responseText);
            }
        });
    }

    /**
     * Handle delivery time input change
     */
    function handleDeliveryTimeChange() {
        const transactionId = $(this).data('id');
        const newTime = $(this).val();
        if (!transactionId || !newTime) return;
        
        $(this).prop('disabled', true);
        
        $.ajax({
            url: `/delivery-transactions/${transactionId}/delivery-time`,
            type: 'PUT',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                delivery_time: newTime
            },
            success: (res) => {
                showSuccess('تم تحديث وقت التوصيل');
            },
            error: (xhr) => {
                showError(xhr.responseJSON?.message || 'حدث خطأ في تحديث وقت التوصيل');
            },
            complete: () => {
                $(this).prop('disabled', false);
            }
        });
    }

    /**
     * Handle delivery status select change
     */
    function handleDeliveryStatusSelectChange() {
        const transactionId = $(this).data('id');
        const newStatus = $(this).val();
        if (!transactionId || !newStatus) return;
        
        $(this).prop('disabled', true);
        
        $.ajax({
            url: `/delivery-transactions/${transactionId}/status`,
            type: 'PUT',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status: newStatus
            },
            success: (res) => {
                showSuccess('تم تحديث حالة الطلب');
                // Reload delivery orders to update the table
                $('#delivery-status-btn').trigger('click');
            },
            error: (xhr) => {
                showError(xhr.responseJSON?.message || 'حدث خطأ في تحديث الحالة');
            },
            complete: () => {
                $(this).prop('disabled', false);
            }
        });
    }

    /**
     * Initialize all delivery related event listeners
     */
    function initDeliveryEventListeners() {
        console.log('Initializing delivery event listeners...');
        
        // Toggle delivery fields when order type changes
        $('#order-type').on('change', handleOrderTypeChange);
        
        // Load delivery orders when button is clicked
        $('#delivery-status-btn').on('click', loadDeliveryOrders);
        $('#quick-delivery-status-btn').on('click', loadDeliveryOrders);
        
        // Status change in delivery status modal
        $('#delivery-status').on('change', handleDeliveryStatusChange);
        
        // Update delivery status form submission
        $('#update-delivery-status-btn').on('click', updateDeliveryStatus);
        
        // Clear timers when delivery orders modal is hidden
        $('#delivery-orders-modal').on('hidden.bs.modal', clearDeliveryOrderTimers);
        
        // Handle delivery time input change
        $(document).on('change', '.delivery-time-input', handleDeliveryTimeChange);
        
        // Handle delivery status select change
        $(document).on('change', '.delivery-status-select', handleDeliveryStatusSelectChange);
        
        // Call once to set initial state
        handleOrderTypeChange();
        
        console.log('Delivery event listeners initialized successfully');
    }

    // Export the public API
    window.deliveryHelper = {
        handleOrderTypeChange: handleOrderTypeChange,
        loadDeliveryOrders: loadDeliveryOrders,
        loadDeliveryDetails: loadDeliveryDetails,
        updateDeliveryStatus: updateDeliveryStatus,
        initDeliveryEventListeners: initDeliveryEventListeners,
        clearDeliveryOrderTimers: clearDeliveryOrderTimers,
        handleDeliveryTimeChange: handleDeliveryTimeChange,
        handleDeliveryStatusSelectChange: handleDeliveryStatusSelectChange,
        formatDateTime: formatDateTime,
        formatCurrency: formatCurrency,
        startAllElapsedTimers: startAllElapsedTimers
    };
    
})(window); 