<!-- Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addSupplierModalLabel">إضافة مورد جديد</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="add-supplier-form">
            @csrf
            <div id="supplier-error-messages" class="alert alert-danger" style="display: none;"></div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="supplier-name" class="form-label">اسم المورد <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplier-name" name="name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="supplier-company-name" class="form-label">اسم الشركة</label>
                        <input type="text" class="form-control" id="supplier-company-name" name="company_name">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="supplier-phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplier-phone" name="phone" required>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="supplier-notes" class="form-label">ملاحظات</label>
                <textarea class="form-control" id="supplier-notes" name="notes" rows="3"></textarea>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        <button type="button" class="btn btn-primary" id="save-supplier-btn">حفظ المورد</button>
      </div>
    </div>
  </div>
</div> 