<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>نظام نقاط البيع | الصفحة الرئيسية</title>
        
        <!-- الخطوط والأيقونات -->
        <link rel="stylesheet" href="{{ asset('/assets/bootstrap.rtl.min.css') }}">
        <link rel="stylesheet" href="{{ asset('/assets/all.min.css') }}">
        
        <style>
            :root {
                --primary-color: #2563eb;
                --secondary-color: #4b5563;
                --accent-color: #fb923c;
                --success-color: #10b981;
                --warning-color: #f59e0b;
                --danger-color: #ef4444;
                --dark-color: #1f2937;
                --light-color: #f3f4f6;
            }
            
            body {
                font-family: 'Cairo', sans-serif;
                background-color: var(--light-color);
                color: var(--dark-color);
                margin: 0;
                padding: 0;
            }
            
            .hero-section {
                background: linear-gradient(rgba(31, 41, 55, 0.8), rgba(31, 41, 55, 0.8)), url('/public/images/hero-bg.jpg');
                background-size: cover;
                background-position: center;
                color: white;
                padding: 6rem 2rem;
                text-align: center;
            }
            
            .hero-title {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1rem;
            }
            
            .hero-subtitle {
                font-size: 1.25rem;
                margin-bottom: 2rem;
                opacity: 0.9;
            }
            
            .features-section {
                padding: 5rem 1rem;
                background-color: white;
            }
            
            .feature-card {
                height: 100%;
                border-radius: 0.5rem;
                border: none;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
                transition: transform 0.3s, box-shadow 0.3s;
                margin-bottom: 2rem;
            }
            
            .feature-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            }
            
            .feature-icon {
                background-color: var(--light-color);
                width: 60px;
                height: 60px;
                border-radius: 50%;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .feature-card .card-title {
                font-weight: 600;
                color: var(--dark-color);
                margin-bottom: 1rem;
            }
            
            .feature-card .card-text {
                color: var(--secondary-color);
                margin-bottom: 1.5rem;
            }
            
            .footer-section {
                background-color: var(--dark-color);
                color: white;
                padding: 2rem 1rem;
                text-align: center;
            }
            
            .btn-main {
                padding: 0.75rem 2rem;
                font-weight: 600;
                border-radius: 0.5rem;
                transition: transform 0.2s;
            }
            
            .btn-main:hover {
                transform: translateY(-3px);
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }
            
            .section-title {
                position: relative;
                margin-bottom: 3rem;
                font-weight: 700;
                color: var(--dark-color);
            }
            
            .section-title:after {
                content: '';
                position: absolute;
                bottom: -10px;
                left: 50%;
                transform: translateX(-50%);
                width: 50px;
                height: 3px;
                background-color: var(--primary-color);
            }
            </style>
    </head>
    <body>
        <!-- القسم الرئيسي (Hero Section) -->
        <section class="hero-section">
            <div class="container">
                <h1 class="hero-title">نظام نقاط البيع المتكامل</h1>
                <p class="hero-subtitle">حل متكامل لإدارة المبيعات والمخزون والعملاء</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-main">تسجيل الدخول</a>
                    <a href="{{ route('home') }}" class="btn btn-light btn-main">لوحة التحكم</a>
                </div>
                        </div>
        </section>
        
        <!-- قسم المميزات -->
        <section class="features-section">
            <div class="container">
                <h2 class="section-title text-center">مميزات النظام</h2>
                
                <div class="row">
                    <div class="col-md-4 col-sm-6">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-primary bg-opacity-10">
                                    <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                                </div>
                                <h5 class="card-title">إدارة المبيعات</h5>
                                <p class="card-text">إنشاء الفواتير وإدارة المبيعات بطريقة سهلة وسريعة مع دعم الباركود وعدة طرق للدفع.</p>
                                <a href="{{ route('sales.index') }}" class="btn btn-primary">فتح نقطة البيع</a>
                                        </div>
                                        </div>
                                    </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-success bg-opacity-10">
                                    <i class="fas fa-box fa-2x text-success"></i>
                                </div>
                                <h5 class="card-title">إدارة المنتجات</h5>
                                <p class="card-text">إدارة المنتجات والمخزون بسهولة مع دعم وحدات مختلفة للمنتج الواحد وتنبيهات المخزون.</p>
                                <a href="{{ route('products.index') }}" class="btn btn-success">إدارة المنتجات</a>
                            </div>
                        </div>
                                </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-warning bg-opacity-10">
                                    <i class="fas fa-chart-bar fa-2x text-warning"></i>
                                </div>
                                <h5 class="card-title">التقارير والإحصائيات</h5>
                                <p class="card-text">تقارير مفصلة للمبيعات والمشتريات والمخزون مع إمكانية تصدير البيانات بصيغ مختلفة.</p>
                                <a href="{{ route('home') }}" class="btn btn-warning text-white">عرض التقارير</a>
                            </div>
                        </div>
                                </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-info bg-opacity-10">
                                    <i class="fas fa-users fa-2x text-info"></i>
                                </div>
                                <h5 class="card-title">إدارة العملاء</h5>
                                <p class="card-text">إدارة بيانات العملاء ومتابعة المبيعات والدفعات وأرصدة العملاء بشكل مباشر.</p>
                                <a href="{{ route('customers.index') }}" class="btn btn-info text-white">إدارة العملاء</a>
                            </div>
                        </div>
                                </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-danger bg-opacity-10">
                                    <i class="fas fa-shopping-basket fa-2x text-danger"></i>
                                </div>
                                <h5 class="card-title">إدارة المشتريات</h5>
                                <p class="card-text">إدارة المشتريات وفواتير الموردين ومتابعة الدفعات والمصروفات بطريقة منظمة.</p>
                                <a href="{{ route('purchases.index') }}" class="btn btn-danger">إدارة المشتريات</a>
                            </div>
                        </div>
                                </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-secondary bg-opacity-10">
                                    <i class="fas fa-cog fa-2x text-secondary"></i>
                                </div>
                                <h5 class="card-title">إعدادات النظام</h5>
                                <p class="card-text">تخصيص الإعدادات حسب احتياجات عملك مع إدارة المستخدمين والصلاحيات المختلفة.</p>
                                <a href="{{ route('settings.index') }}" class="btn btn-secondary">إعدادات النظام</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- القسم السفلي (Footer) -->
        <footer class="footer-section">
            <div class="container">
                <p>{{ config('app.name') }} &copy; {{ date('Y') }} | مشغل بواسطة Laravel v{{ app()->version() }}</p>
        </div>
        </footer>
        
        <script src="{{ asset('/assets/bootstrap.bundle.min.js') }}"></script>
    </body>
</html>
