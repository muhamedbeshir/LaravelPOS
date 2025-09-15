@extends('layouts.app')

@section('title', 'عن نظام البلدوزر')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-lg border-0 rounded-4 mb-5">
                <div class="card-header bg-primary text-white text-center py-4 border-0 rounded-top-4">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <img src="{{ asset('bulldozer-favicon.svg') }}" alt="البلدوزر" width="60" height="60" class="me-3">
                        <h1 class="display-5 fw-bold m-0">نظام البلدوزر</h1>
                    </div>
                    <p class="lead mb-0">نظام الكاشير المتميز الذي لا مثيل له</p>
                </div>
                <div class="card-body p-5">
                    <div class="row mb-5">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <h2 class="h3 fw-bold text-primary mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                عن النظام
                            </h2>
                            <p class="lead">
                                نظام البلدوزر هو نظام نقاط بيع متكامل مصمم خصيصًا لتلبية احتياجات الأعمال التجارية بمختلف أحجامها. يتميز النظام بواجهة سهلة الاستخدام وقوة في الأداء تجعله الخيار الأمثل لإدارة المبيعات والمخزون بكفاءة عالية.
                            </p>
                            <p>
                                تم تطوير النظام باستخدام أحدث التقنيات لضمان سرعة الأداء والاستقرار والأمان، مع التركيز على تجربة المستخدم لتوفير بيئة عمل مريحة وفعالة.
                            </p>
                        </div>
                        <div class="col-lg-6">
                            <h2 class="h3 fw-bold text-primary mb-4">
                                <i class="fas fa-star me-2"></i>
                                مميزات النظام
                            </h2>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-cash-register text-primary fs-4"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="fw-bold">نقاط البيع</h5>
                                            <p class="mb-0">واجهة بيع سهلة وسريعة</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-boxes text-primary fs-4"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="fw-bold">إدارة المخزون</h5>
                                            <p class="mb-0">تتبع المنتجات والمخزون</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-chart-bar text-primary fs-4"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="fw-bold">تقارير متقدمة</h5>
                                            <p class="mb-0">تحليلات وإحصائيات شاملة</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-users text-primary fs-4"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="fw-bold">إدارة العملاء</h5>
                                            <p class="mb-0">بيانات ومعاملات العملاء</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5">

                    <div class="row mb-5">
                        <div class="col-md-12 text-center mb-4">
                            <h2 class="h3 fw-bold text-primary">
                                <i class="fas fa-cogs me-2"></i>
                                المميزات التقنية
                            </h2>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body text-center p-4">
                                    <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3 mx-auto" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-tachometer-alt fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold">أداء سريع</h5>
                                    <p class="text-muted mb-0">مبني على أحدث التقنيات لضمان سرعة الاستجابة</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body text-center p-4">
                                    <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3 mx-auto" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-mobile-alt fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold">تصميم متجاوب</h5>
                                    <p class="text-muted mb-0">يعمل على جميع الأجهزة بكفاءة عالية</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body text-center p-4">
                                    <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3 mx-auto" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-shield-alt fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold">أمان متكامل</h5>
                                    <p class="text-muted mb-0">حماية البيانات وصلاحيات المستخدمين</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5">

                    <div class="row">
                        <div class="col-md-12 text-center mb-4">
                            <h2 class="h3 fw-bold text-primary">
                                <i class="fas fa-headset me-2"></i>
                                الدعم الفني
                            </h2>
                            <p class="lead mb-4">نحن هنا لمساعدتك في أي وقت</p>
                        </div>
                        <div class="col-md-6 offset-md-3">
                            <div class="card border-0 shadow-sm rounded-4 bg-light">
                                <div class="card-body p-4">
                                    <div class="text-center mb-3">
                                        <img src="https://ui-avatars.com/api/?name=احمد+تهامي&background=1a56db&color=fff&size=128&rounded=true" alt="م. احمد تهامي" class="rounded-circle img-thumbnail mb-3" style="width: 100px; height: 100px;">
                                        <h4 class="fw-bold">م. احمد تهامي</h4>
                                        <p class="text-muted mb-3">مطور النظام والدعم الفني</p>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="https://wa.me/201500001487" target="_blank" class="btn btn-success btn-lg">
                                            <i class="fab fa-whatsapp me-2"></i>
                                            تواصل عبر واتساب
                                        </a>
                                        <p class="text-center mt-2 mb-0 text-muted">
                                            <i class="fas fa-phone-alt me-1"></i> 01500001487
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-4 border-0 rounded-bottom-4">
                    <p class="mb-0">© {{ date('Y') }} نظام البلدوزر - جميع الحقوق محفوظة</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 