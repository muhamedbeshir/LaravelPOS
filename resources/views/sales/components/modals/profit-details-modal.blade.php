<!-- Modal: تفاصيل الربح -->
<div class="modal fade" id="profit-details-modal" tabindex="-1" aria-labelledby="profit-details-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profit-details-modal-label">
                    <i class="fas fa-chart-line me-2"></i>تفاصيل الربح
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الوحدة</th>
                                <th>الكمية</th>
                                <th>سعر البيع</th>
                                <th>سعر التكلفة</th>
                                <th>إجمالي المبيعات</th>
                                <th>إجمالي التكلفة</th>
                                <th>صافي الربح</th>
                                <th>نسبة الربح</th>
                            </tr>
                        </thead>
                        <tbody id="profit-details-table-body">
                            <!-- سيتم ملء البيانات بواسطة الجافاسكربت -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5">الإجمالي</th>
                                <th id="profit-total-sales">0</th>
                                <th id="profit-total-cost">0</th>
                                <th id="profit-total-profit">0</th>
                                <th id="profit-total-percentage">0%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="window.printProfitDetails()">
                    <i class="fas fa-print me-1"></i> طباعة التقرير
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div> 