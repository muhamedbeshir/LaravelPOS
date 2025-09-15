@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة المجموعات</h2>
        <div>
            <a href="{{ route('categories.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة مجموعة جديدة
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        @forelse($categories as $category)
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                @if($category->image)
                <div class="card-img-top position-relative" style="height: 200px; background-color: {{ $category->color ?? '#2563eb' }}">
                    <img src="/storage/categories/{{ $category->image }}" 
                         class="position-absolute top-50 start-50 translate-middle"
                         alt="{{ $category->name }}" 
                         style="max-width: 90%; max-height: 90%; object-fit: contain;">
                </div>
                @else
                <div class="card-img-top d-flex align-items-center justify-content-center" 
                     style="height: 200px; background-color: {{ $category->color ?? '#2563eb' }}">
                    <i class="fas fa-folder fa-4x text-white"></i>
                </div>
                @endif
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title mb-0">{{ $category->name }}</h5>
                        <form action="{{ route('categories.toggle-active', $category) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $category->is_active ? 'btn-success' : 'btn-danger' }}">
                                @if($category->is_active)
                                <i class="fas fa-check-circle"></i>
                                @else
                                <i class="fas fa-times-circle"></i>
                                @endif
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <div class="btn-group w-100" role="group">
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-info text-white">
                            <i class="fas fa-edit me-1"></i>
                            تعديل
                        </a>
                        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline w-50">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100" 
                                    onclick="return confirm('هل أنت متأكد من حذف هذه المجموعة؟')">
                                <i class="fas fa-trash me-1"></i>
                                حذف
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <p class="text-muted">لا توجد مجموعات مضافة</p>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection 