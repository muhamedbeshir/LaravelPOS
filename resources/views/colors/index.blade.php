@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-palette me-2"></i>إدارة الألوان</h5>
                    <div>
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-light me-2">
                            <i class="fas fa-arrow-right me-1"></i> رجوع للمنتجات
                        </a>
                        <a href="{{ route('colors.create') }}" class="btn btn-sm btn-light">
                            <i class="fas fa-plus-circle me-1"></i> إضافة لون جديد
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="65%">اسم اللون</th>
                                    <th width="30%">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($colors as $index => $color)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $color->name }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('colors.edit', $color) }}" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </a>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteColorModal{{ $color->id }}">
                                                    <i class="fas fa-trash"></i> حذف
                                                </button>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteColorModal{{ $color->id }}" tabindex="-1" aria-labelledby="deleteColorModalLabel{{ $color->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title" id="deleteColorModalLabel{{ $color->id }}">تأكيد الحذف</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            هل أنت متأكد من حذف اللون: <strong>{{ $color->name }}</strong>؟
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                            <form action="{{ route('colors.destroy', $color) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">حذف</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">لا توجد ألوان مسجلة</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 