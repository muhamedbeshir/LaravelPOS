<!-- Modal: الفواتير المعلقة -->
<div class="modal fade" id="suspended-sales-modal" tabindex="-1" aria-labelledby="suspendedSalesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="suspendedSalesModalLabel"><i class="fas fa-pause-circle me-2"></i>الفواتير المعلقة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" id="suspended-sales-search" placeholder="ابحث بالرقم المرجعي أو اسم العميل...">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-sm w-100" id="btn-refresh-suspended-sales"><i class="fas fa-sync-alt me-1"></i> تحديث القائمة</button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-hover table-sm">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>#</th>
                                <th>الرقم المرجعي</th>
                                <th>العميل</th>
                                <th>المستخدم</th>
                                <th>الإجمالي</th>
                                <th>تاريخ التعليق</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="suspended-sales-table-body">
                            <!-- سيتم ملء البيانات بواسطة الجافاسكربت -->
                        </tbody>
                    </table>
                </div>
                <div id="suspended-sales-pagination" class="mt-3 d-flex justify-content-center"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div> 