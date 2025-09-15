@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">تفعيل النظام</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <strong>معرف الجهاز:</strong>
                        <span dir="ltr" style="user-select:all">{{ $deviceId }}</span><br>
                  </div>
                    @if($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('activation.submit') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="device_id" class="form-label">معرف الجهاز</label>
                            <input type="text" class="form-control" id="device_id" name="device_id" value="{{ $deviceId }}" readonly required>
                        </div>
                        <div class="mb-3">
                            <label for="activation_code" class="form-label">رمز التفعيل</label>
                            <input type="text" class="form-control" id="activation_code" name="activation_code" required placeholder=" {{ $deviceId }}">
                        </div>
                        <button type="submit" class="btn btn-success">تفعيل</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 