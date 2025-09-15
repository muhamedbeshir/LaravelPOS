<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام البلدوزر للكاشير</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link href="{{ asset('/assets/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('/assets/all.min.css') }}" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1a56db;
            --secondary-color: #0e3fa9;
            --accent-color: #3b82f6;
            --light-color: #e0f2fe;
            --dark-color: #0f172a;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            font-family: 'Cairo', sans-serif;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            position: relative;
        }
        
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.03)" width="50" height="50" x="0" y="0"/><rect fill="rgba(255,255,255,0.03)" width="50" height="50" x="50" y="50"/></svg>');
            opacity: 0.3;
            z-index: 0;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            width: 420px;
            border: 0;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card .card-header {
            padding: 2.5rem 1rem 1.5rem;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            text-align: center;
            border-bottom: 0;
            position: relative;
            overflow: hidden;
        }
        
        .login-card .card-header::after {
            content: "";
            position: absolute;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 60px;
            background: white;
            border-radius: 50% 50% 0 0;
        }
        
        .login-card .card-body {
            padding: 2.5rem;
        }
        
        .bulldozer-icon {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .app-name {
            font-weight: 900;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .app-tagline {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            padding: 0.8rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            background-color: white;
        }
        
        /* Dropdown Select Styling */
        .form-select {
            padding: 0.8rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 1rem center;
            background-size: 16px 12px;
        }
        
        .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            background-color: white;
        }
        
        .input-group-text {
            border-radius: 0.75rem 0 0 0.75rem;
            padding: 0 1rem;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
            border: none;
        }
        
        .login-btn {
            padding: 0.8rem;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 0.75rem;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.25);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(59, 130, 246, 0.35);
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .alert-danger {
            border-radius: 0.75rem;
            background-color: #fee2e2;
            border: none;
            color: #b91c1c;
        }
        
        .support-btn {
            padding: 0.6rem 1rem;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 0.75rem;
            background-color: #25D366;
            color: white;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(37, 211, 102, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: default;
        }
        
        .support-btn:hover {
            color: white;
        }
        
        .support-btn i {
            font-size: 1.2rem;
            margin-left: 0.5rem;
        }
        
        .footer-links {
            position: absolute;
            bottom: 1rem;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            z-index: 10;
        }
        
        .footer-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .footer-link:hover {
            background-color: rgba(255, 255, 255, 0.25);
            color: white;
        }
        
        .footer-link i {
            margin-left: 0.5rem;
        }
        
        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border-bottom: none;
            padding: 1.5rem;
            text-align: center;
            display: block;
        }
        
        .modal-title {
            font-weight: 700;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            border-top: none;
            padding: 1rem 2rem 1.5rem;
        }
        
        .close-modal-btn {
            background-color: #e5e7eb;
            color: #1f2937;
            border: none;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .close-modal-btn:hover {
            background-color: #d1d5db;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .feature-icon i {
            font-size: 1.5rem;
        }
        
        .feature-card {
            margin-bottom: 1.5rem;
        }
        
        .developer-info {
            background-color: #f3f4f6;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
            margin-top: 2rem;
        }
        
        .developer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="card-header">
                <i class="fas fa-truck-monster bulldozer-icon"></i>
                <h1 class="app-name">البلدوزر</h1>
                <p class="app-tagline">نظام الكاشير المتميز الذي لا مثيل له</p>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        @foreach ($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="username" class="form-label">اسم المستخدم</label>
                        <div class="input-group">
                            <select id="username" class="form-select form-control" name="username" required autofocus>
                                <option value="">اختر المستخدم</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->username }}" {{ old('username') == $user->username ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <span class="input-group-text text-white">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <div class="input-group">
                            <input id="password" type="password" class="form-control" name="password" required placeholder="أدخل كلمة المرور">
                            <span class="input-group-text text-white">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary w-100 login-btn mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i> تسجيل الدخول
                        </button>
                        
                        <div class="support-btn w-100">
                            <i class="fab fa-whatsapp"></i> م. محمد بشير - 01555556932
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="footer-links">
            <a class="footer-link" data-bs-toggle="modal" data-bs-target="#aboutModal">
                <i class="fas fa-info-circle"></i> عن النظام
            </a>
        </div>
    </div>
    
    <!-- About Modal -->
    <div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex justify-content-center w-100 align-items-center">
                        <img src="{{ asset('bulldozer-favicon.svg') }}" alt="البلدوزر" width="40" height="40" class="me-3">
                        <h5 class="modal-title fs-4" id="aboutModalLabel">عن نظام البلدوزر</h5>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="fw-bold text-primary mb-3">نظام الكاشير المتميز الذي لا مثيل له</h5>
                            <p>
                                نظام البلدوزر هو نظام نقاط بيع متكامل مصمم خصيصًا لتلبية احتياجات الأعمال التجارية بمختلف أحجامها. يتميز النظام بواجهة سهلة الاستخدام وقوة في الأداء تجعله الخيار الأمثل لإدارة المبيعات والمخزون بكفاءة عالية.
                            </p>
                            <p>
                                تم تطوير النظام باستخدام أحدث التقنيات لضمان سرعة الأداء والاستقرار والأمان، مع التركيز على تجربة المستخدم لتوفير بيئة عمل مريحة وفعالة.
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="fw-bold text-primary mb-3">المميزات الرئيسية</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card d-flex">
                                <div class="feature-icon me-3">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">نقاط البيع</h6>
                                    <p class="mb-0 text-muted">واجهة بيع سهلة وسريعة تسهل عمليات البيع اليومية</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card d-flex">
                                <div class="feature-icon me-3">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">إدارة المخزون</h6>
                                    <p class="mb-0 text-muted">تتبع دقيق للمنتجات والمخزون بسهولة</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card d-flex">
                                <div class="feature-icon me-3">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">تقارير متقدمة</h6>
                                    <p class="mb-0 text-muted">تحليلات وإحصائيات شاملة لاتخاذ القرارات</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card d-flex">
                                <div class="feature-icon me-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">إدارة العملاء</h6>
                                    <p class="mb-0 text-muted">متابعة بيانات ومعاملات العملاء بدقة</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="developer-info">
                        <img src="https://ui-avatars.com/api/?name=احمد+تهامي&background=1a56db&color=fff&size=128" alt="م. احمد تهامي" class="developer-avatar">
                        <h5 class="fw-bold">م. احمد تهامي</h5>
                        <p class="text-muted mb-2">مطور النظام والدعم الفني</p>
                        <p class="mb-0">
                            <i class="fas fa-phone-alt me-1"></i> 01500001487
                        </p>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="close-modal-btn" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="{{ asset('/assets/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usernameSelect = document.getElementById('username');
            if (usernameSelect) {
                usernameSelect.addEventListener('change', function() {
                    if (this.value === '') {
                        this.style.color = '#6c757d'; // Change text color to gray
                    } else {
                        this.style.color = '#343a40'; // Change text color to dark
                    }
                });
            }
        });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usernameSelect = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            // Set initial color
            if (usernameSelect.value === '') {
                usernameSelect.style.color = '#6c757d';
            } else {
                usernameSelect.style.color = '#343a40';
            }
            
            // Handle select change
            usernameSelect.addEventListener('change', function() {
                if (this.value === '') {
                    this.style.color = '#6c757d';
                } else {
                    this.style.color = '#343a40';
                    // Focus on password field after selecting a user
                    passwordInput.focus();
                }
            });
            
            // Add autofocus on page load
            setTimeout(function() {
                usernameSelect.focus();
            }, 300);
        });
    </script>
</body>
</html> 