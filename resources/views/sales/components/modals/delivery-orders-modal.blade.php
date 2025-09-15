<!-- مودال عرض كل طلبات الدليفري غير المكتملة -->
<div class="modal fade" id="delivery-orders-modal" tabindex="-1" aria-labelledby="deliveryOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliveryOrdersModalLabel">
                    <i class="fas fa-truck me-2"></i>طلبات الدليفري للوردية الحالية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm" id="delivery-orders-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الفاتورة</th>
                                <th>العميل</th>
                                <th>الهاتف</th>
                                <th>موظف التوصيل</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th>وقت التوصيل المتوقع/الفعلي</th>
                                <th>الوقت المنقضي منذ الخروج</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="delivery-orders-table-body">
                            <!-- سيتم ملء البيانات بواسطة الجافاسكريبت -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div> 