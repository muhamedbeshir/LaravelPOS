<!-- Modal: منتجات المجموعة -->
<div class="modal fade" id="category-products-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">منتجات المجموعة: <span id="category-name"></span></h5>
                <div>
                    <button class="btn btn-secondary btn-sm me-2" onclick="productManager.backToCategories()">
                        <i class="fas fa-arrow-right"></i>
                        رجوع
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="category-products-search" 
                           placeholder="ابحث في منتجات المجموعة...">
                </div>
                <div class="row g-2" id="category-products-container"></div>
            </div>
        </div>
    </div>
</div> 