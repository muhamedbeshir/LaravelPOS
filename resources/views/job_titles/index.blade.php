@extends('layouts.app')

@section('title', 'الوظائف')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة الوظائف</h2>
        <div>
            <a href="{{ route('job-titles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة وظيفة جديدة
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

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>الوظيفة</th>
                            <th>الوصف</th>
                            <th>عدد الموظفين</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobTitles as $jobTitle)
                        <tr>
                            <td>{{ $jobTitle->name }}</td>
                            <td>{{ $jobTitle->description ?? '-' }}</td>
                            <td>{{ $jobTitle->employees_count ?? $jobTitle->employees()->count() }}</td>
                            <td>
                                <form action="{{ route('job-titles.toggle-active', $jobTitle) }}" 
                                      method="POST" 
                                      class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="btn btn-link p-0 {{ $jobTitle->is_active ? 'text-success' : 'text-danger' }}">
                                        @if($jobTitle->is_active)
                                        <i class="fas fa-check-circle"></i> نشط
                                        @else
                                        <i class="fas fa-times-circle"></i> غير نشط
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="{{ route('job-titles.edit', $jobTitle) }}" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <form action="{{ route('job-titles.destroy', $jobTitle) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذه الوظيفة؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-sm btn-danger" 
                                            {{ $jobTitle->employees()->count() > 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">لا توجد وظائف مضافة</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 