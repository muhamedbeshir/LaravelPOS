<!-- Quick totals and actions panel -->
<div class="card shadow-sm sales-card small-card">
    <div class="card-body p-2">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <div>
                <span class="small fw-bold">الإجمالي:</span>
                <span id="totals-subtotal" class="badge bg-primary">0.00</span>
            </div>
            <div>
                <span class="small fw-bold">المنتجات:</span>
                <span id="totals-items" class="badge bg-secondary">0</span>
            </div>
            <div>
                <span class="small fw-bold">الخصم:</span>
                <span id="totals-discount" class="badge bg-danger">0.00</span>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold">المطلوب دفعه:</span>
            <span class="h5 mb-0 text-primary" id="totals-final">0.00</span>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <button class="btn btn-primary btn-sm flex-grow-1 me-1" id="quick-save">
                <i class="fas fa-save"></i> (F1) حفظ
            </button>
            <button class="btn btn-success btn-sm flex-grow-1" id="quick-print">
                <i class="fas fa-print"></i> (F9) طباعة
            </button>
        </div>
        <div class="d-flex mt-1">
            <button class="btn btn-warning btn-sm flex-grow-1 me-1" id="quick-suspend">
                <i class="fas fa-pause me-1"></i> تعليق الفاتورة
            </button>
            <button class="btn btn-info btn-sm flex-grow-1" id="delivery-status-btn-quick" style="display: none;">
                <i class="fas fa-truck me-1"></i> حالة الدليفري
            </button>
        </div>
    </div>
</div> 

<script>
document.addEventListener('keydown', function(event) {
    switch (event.key) {
        case 'F1':
            event.preventDefault();
            document.getElementById('quick-save').click();
            break;
        case 'F9':
            event.preventDefault();
            document.getElementById('quick-print').click();
            break;
        case 'F6':
            event.preventDefault();
            document.getElementById('quick-suspend').click();
            break;
    }
});

function ensurePaidAmountBeforeQuickAction() {
    const paidInput = document.getElementById('paid-amount');
    const totalEl = document.getElementById('total');
    const invoiceTypeEl = document.getElementById('invoice-type');
    if (paidInput && totalEl && invoiceTypeEl) {
        const invoiceType = invoiceTypeEl.value;
        if (invoiceType === 'cash') {
            let paidVal = parseFloat(paidInput.value);
            if (isNaN(paidVal) || paidVal === 0) {
                const totalVal = Math.round((parseFloat(totalEl.textContent) || 0) * 100) / 100;
                paidInput.value = totalVal.toFixed(2);
            }
        }
    }
}

// Attach to quick-save and quick-print
window.addEventListener('DOMContentLoaded', function() {
    const quickSave = document.getElementById('quick-save');
    const quickPrint = document.getElementById('quick-print');
    if (quickSave) quickSave.addEventListener('click', ensurePaidAmountBeforeQuickAction, true);
    if (quickPrint) quickPrint.addEventListener('click', ensurePaidAmountBeforeQuickAction, true);
});
</script> 