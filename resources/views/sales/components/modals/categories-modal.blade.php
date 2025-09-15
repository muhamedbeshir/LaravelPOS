<!-- Modal: المجموعات -->
<div class="modal fade" id="categories-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title"><i class="fas fa-th-large me-2"></i>المجموعات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    @foreach($categories as $category)
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="card category-card h-100" data-id="{{ $category->id }}" data-name="{{ $category->name }}" onclick="productManager.showCategoryProducts({{ $category->id }}, '{{ $category->name }}')">
                            <div class="card-body p-2 text-center">
                                @if($category->image)
                                <img src="{{ $category->image_url }}" class="img-fluid mb-2" style="height: 70px; object-fit: contain;">
                                @else
                                <i class="fas fa-folder fa-2x mb-2 text-primary"></i>
                                @endif
                                <h6 class="mb-0 small">{{ $category->name }}</h6>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div> 