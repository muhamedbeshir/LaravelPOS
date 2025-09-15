<!-- إضافة مودال للتحكم في حالة الدليفري -->
<div class="modal fade" id="delivery-status-modal" tabindex="-1" aria-labelledby="deliveryStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliveryStatusModalLabel">تحديث حالة الدليفري</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div id="delivery-transaction-details">
                    <div class="mb-3">
                        <label class="form-label">رقم الفاتورة:</label>
                        <span id="delivery-invoice-number" class="fw-bold"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">العميل:</label>
                        <span id="delivery-customer-name" class="fw-bold"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">موظف التوصيل:</label>
                        <span id="delivery-employee-name" class="fw-bold"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ الإجمالي:</label>
                        <span id="delivery-amount" class="fw-bold"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ المحصل:</label>
                        <span id="delivery-collected-amount" class="fw-bold"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ المتبقي:</label>
                        <span id="delivery-remaining-amount" class="fw-bold"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الحالة الحالية:</label>
                        <span id="delivery-current-status" class="fw-bold badge"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وقت التوصيل:</label>
                        <span id="delivery-time" class="fw-bold"></span>
                    </div>
                </div>
                
                <hr>
                
                <form id="delivery-status-form">
                    <input type="hidden" id="delivery-transaction-id" name="transaction_id">
                    
                    <div class="mb-3">
                        <label for="delivery-status" class="form-label">تحديث الحالة إلى:</label>
                        <select class="form-select" id="delivery-status" name="status">
                            <option value="">-- اختر الحالة --</option>
                            <option value="ready">الطلبية جاهزة في انتظار الخروج</option>
                            <option value="delivered_pending_payment">تم التوصيل بانتظار الدفع</option>
                            <option value="paid">تم الدفع</option>
                            <option value="returned">مرتجع</option>
                        </select>
                    </div>
                    
                    <div id="payment-amount-container" class="mb-3 d-none">
                        <label for="payment-amount" class="form-label">المبلغ المحصل:</label>
                        <input type="number" class="form-control" id="payment-amount" name="amount" step="0.01" min="0">
                    </div>
                    
                    <div id="return-notes-container" class="mb-3 d-none">
                        <label for="return-notes" class="form-label">ملاحظات الإرجاع:</label>
                        <textarea class="form-control" id="return-notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="update-delivery-status-btn">تحديث الحالة</button>
            </div>
        </div>
    </div>
</div> 