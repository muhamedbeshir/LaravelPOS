<!-- Modal: المنتجات -->
<div class="modal fade" id="products-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">جميع المنتجات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="modal-search-input" 
                                   placeholder="ابحث عن منتج...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="category-filter">
                            <option value="">كل المجموعات</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="all-products-table">
                        <thead class="table-light">
                            <tr>
                                <th width="80">الصورة</th>
                                <th>الباركود</th>
                                <th>اسم المنتج</th>
                                <th>المجموعة</th>
                                <th>المخزون</th>
                                <th width="100">السعر</th> <!-- Added Price Column -->
                                <th width="100">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                
                <!-- Pagination Controls -->
                <div class="mt-4" id="products-pagination">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted" id="pagination-info">
                            <!-- Will be populated by JS -->
                        </div>
                        <div>
                            <select class="form-select form-select-sm d-inline-block w-auto" id="pagination-size">
                                <option value="25">25</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="ms-2">منتج في الصفحة</span>
                        </div>
                    </div>
                    
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center" id="pagination-controls">
                            <!-- Will be populated by JS -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div> 