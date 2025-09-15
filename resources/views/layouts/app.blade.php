@php
    use Illuminate\Support\Facades\Route;
    // Force a fresh query to get the current shift status
    // This ensures we always have the latest shift status
    $currentShift = \App\Models\Shift::getCurrentOpenShift(true);
    
    // Removed debug code section
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'البلدوزر'))</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('bulldozer-favicon.svg') }}" type="image/svg+xml">
    
    <!-- Cairo Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="{{ asset('/assets/bootstrap.rtl.min.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('/assets/all.min.css') }}">
    <!-- Select2 CSS -->
    <link href="{{ asset('/assets/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('/assets/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="{{ asset('/assets/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <script src="{{ asset('/assets/sweetalert2@11') }}"></script>
    
    <!-- Moment.js CDN -->
    <script src="{{ asset('/assets/moment.min.js') }}"></script>
    <script src="{{ asset('/assets/ar-sa.min.js') }}"></script>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #4f46e5;
            --success-color: #10b981;
            --info-color: #38bdf8;
            --warning-color: #fbbf24;
            --danger-color: #ef4444;
            --purple-color: #8b5cf6;
            --teal-color: #14b8a6;
            --blue-color: #3b82f6;
            --indigo-color: #6366f1;
            --orange-color: #f97316;
            --pink-color: #ec4899;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            
            /* New shadow variables */
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            
            --transition-fast: all 0.2s ease;
            --transition-normal: all 0.3s ease;
            --transition-slow: all 0.5s ease;
            
            --glow-primary: 0 0 15px rgba(59,130,246,0.5);
            --glow-success: 0 0 15px rgba(16,185,129,0.5);
            --glow-warning: 0 0 15px rgba(251,191,36,0.5);
            --glow-danger: 0 0 15px rgba(239,68,68,0.5);
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            padding-top: 65px;
            min-height: 100vh;
            position: relative;
            background-image: 
                radial-gradient(at 100% 0%, rgba(59,130,246,0.03) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(139,92,246,0.03) 0px, transparent 50%);
            background-attachment: fixed;
        }

        /* Navbar Styling */
        .navbar {
            background: linear-gradient(135deg, #3b82f6, #4f46e5);
            padding: 0.5rem 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1030;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            color: #ffffff !important;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            margin-left: 0;
        }

        .navbar-brand:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            margin: 0 0.1rem;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.18);
            color: #ffffff !important;
        }
        
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.25);
        }
        
        .nav-link i {
            font-size: 1rem;
            vertical-align: middle;
        }
        
        /* Hide text on medium screens but show on small and large screens */
        @media (min-width: 768px) and (max-width: 1199px) {
            .nav-link span {
                display: none;
            }
            
            .nav-link i {
                font-size: 1.2rem;
                margin: 0;
            }
            
            .nav-link {
                padding: 0.5rem;
                text-align: center;
            }
        }

        .nav-item.dropdown .dropdown-menu {
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            padding: 0.5rem;
            background-color: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(209, 213, 219, 0.2);
            margin-top: 8px;
        }
        
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -5px;
            right: 20px;
            width: 10px;
            height: 10px;
            background-color: white;
            transform: rotate(45deg);
            z-index: -1;
            border-top: 1px solid rgba(209, 213, 219, 0.2);
            border-left: 1px solid rgba(209, 213, 219, 0.2);
        }

        .dropdown-item {
            padding: 0.5rem 0.8rem;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background-color: rgba(59, 130, 246, 0.08);
            transform: translateX(-3px);
        }

        .dropdown-item i {
            width: 1.2rem;
            text-align: center;
            margin-left: 0.6rem;
            transition: all 0.2s ease;
            color: #4f46e5;
        }

        /* Main Content Area */
        .main-content {
            padding: 1.5rem 0;
            position: relative;
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(209, 213, 219, 0.3);
        }

        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: rgba(255, 255, 255, 0.7);
            padding: 1rem 1.25rem;
            font-weight: 600;
            border-top-left-radius: 0.75rem !important;
            border-top-right-radius: 0.75rem !important;
        }

        .card-body {
            padding: 1.25rem;
        }

        /* Form Controls */
        .form-control, .form-select {
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
            background-color: #fff;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: var(--text-primary);
        }

        /* Button Styling */
        .btn {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn:hover {
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.3s ease;
            z-index: -1;
        }
        
        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #059669, var(--success-color));
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-info {
            background: linear-gradient(135deg, #0284c7, var(--info-color));
            color: white;
            box-shadow: 0 4px 10px rgba(56, 189, 248, 0.3);
        }
        
        .btn-info:hover {
            box-shadow: 0 6px 15px rgba(56, 189, 248, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #d97706, var(--warning-color));
            color: white;
            box-shadow: 0 4px 10px rgba(251, 191, 36, 0.3);
        }
        
        .btn-warning:hover {
            box-shadow: 0 6px 15px rgba(251, 191, 36, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc2626, var(--danger-color));
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4);
        }

        /* Table Styling */
        .table {
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            padding: 0.75rem 0.5rem;
            background-color: rgba(243, 244, 246, 0.7);
        }

        .table td {
            padding: 0.75rem 0.5rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            transition: all 0.2s ease;
        }

        .table tr {
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .table tr:hover {
            border-left: 3px solid var(--primary-color);
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        .table tr:hover td {
            /* Removed: transform: translateX(3px); */
        }

        .table-hover tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        /* Badge Styling */
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 50rem;
            box-shadow: var(--shadow-sm);
        }
        
        .badge:hover {
            /* Removed: transform: scale(1.05); */
        }
        
        .badge-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #059669, var(--success-color));
            color: white;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #0284c7, var(--info-color));
            color: white;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #d97706, var(--warning-color));
            color: white;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #dc2626, var(--danger-color));
            color: white;
        }

        /* Select2 Customization */
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 0.5rem;
            font-size: 0.95rem;
            min-height: calc(1.5em + 0.75rem + 2px);
            border: 1px solid var(--border-color);
            background-color: rgba(255, 255, 255, 0.8);
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
            background-color: #fff;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            padding: 0.5rem 0.75rem;
        }
        
        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            border: none;
            overflow: hidden;
        }
        
        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--primary-color) !important;
        }
        
        .select2-container--bootstrap-5 .select2-results__option--selected {
            background-color: var(--primary-color) !important;
            color: white !important;
        }

        /* Toastr Customization */
        .toast-success {
            background-color: var(--success-color);
            box-shadow: var(--shadow-lg), 0 0 15px rgba(16,185,129,0.5);
        }
        .toast-error {
            background-color: var(--danger-color);
            box-shadow: var(--shadow-lg), 0 0 15px rgba(239,68,68,0.5);
        }
        .toast-info {
            background-color: var(--info-color);
            box-shadow: var(--shadow-lg);
        }
        .toast-warning {
            background-color: var(--warning-color);
            box-shadow: var(--shadow-lg), 0 0 15px rgba(251,191,36,0.5);
            color: #000;
        }
        
        .toast-progress {
            background: linear-gradient(to right, rgba(255,255,255,0.7), rgba(255,255,255,0.3));
            height: 4px !important;
            opacity: 1 !important;
        }

        /* Gradient Background Classes */
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #059669, var(--success-color));
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #0284c7, var(--info-color));
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #d97706, var(--warning-color));
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #dc2626, var(--danger-color));
        }
        
        .bg-gradient-purple {
            background: linear-gradient(135deg, #7c3aed, var(--purple-color));
        }
        
        .bg-gradient-teal {
            background: linear-gradient(135deg, #0d9488, var(--teal-color));
        }
        
        .bg-gradient-blue {
            background: linear-gradient(135deg, #1d4ed8, var(--blue-color));
        }
        
        .bg-gradient-indigo {
            background: linear-gradient(135deg, #4338ca, var(--indigo-color));
        }
        
        .bg-gradient-orange {
            background: linear-gradient(135deg, #ea580c, var(--orange-color));
        }
        
        .bg-gradient-pink {
            background: linear-gradient(135deg, #db2777, var(--pink-color));
        }
        
        /* Glass Effect Class */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        .glass-dark {
            background: rgba(31, 41, 55, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(31, 41, 55, 0.3);
            box-shadow: var(--shadow-md);
            color: white;
        }

        /* User dropdown customization */
        #userDropdown {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        #userDropdown:hover, #userDropdown:focus {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        
        #userDropdown i {
            font-size: 1.2rem;
            color: white;
        }
        
        .user-name {
            font-weight: 600;
            color: white;
        }
        
        #userDropdown + .dropdown-menu {
            min-width: 240px;
            border-radius: 10px;
            padding: 0.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(209, 213, 219, 0.3);
            margin-top: 8px !important;
            position: absolute !important;
            left: auto !important;
            right: 0 !important;
            max-width: 320px;
            max-height: 90vh;
            overflow-y: auto;
            background-color: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(5px);
        }
        
        [dir="rtl"] #userDropdown + .dropdown-menu.dropdown-menu-end {
            left: 0 !important;
            right: auto !important;
        }
        
        #userDropdown + .dropdown-menu .dropdown-item {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            margin-bottom: 0.2rem;
        }
        
        #userDropdown + .dropdown-menu .dropdown-item i {
            width: 1.4rem;
            margin-left: 0.7rem;
            text-align: center;
            color: var(--primary-color);
            flex-shrink: 0;
        }
        
        #userDropdown + .dropdown-menu .dropdown-divider {
            margin: 0.5rem 0;
            opacity: 0.1;
        }
        
        /* Shift Status Dropdown Item Styling */
        .shift-status-item {
            font-style: italic;
            color: var(--text-secondary);
            padding: 0.4rem 1rem !important; 
            font-size: 0.85rem !important;
        }
        .shift-status-item i {
             color: var(--text-secondary) !important;
        }
        .shift-status-item.active-shift {
            color: var(--success-color);
            font-style: normal;
            font-weight: 600;
        }
        .shift-status-item.active-shift i {
            color: var(--success-color) !important;
        }
        .shift-status-item.no-shift a {
            color: var(--primary-color) !important;
        }

        /* Navbar button group */
        .navbar-buttons-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 991.98px) { 
            .navbar-buttons-group {
                width: 100%;
                margin-top: 0.5rem;
                flex-direction: column;
                align-items: stretch; 
            }
            .navbar-buttons-group .btn,
            .navbar-buttons-group .dropdown {
                width: 100%;
                text-align: right;
            }
            .navbar-buttons-group .dropdown-toggle {
                 justify-content: space-between;
            }
            #userDropdown {
                 background-color: rgba(59, 130, 246, 0.05);
                 padding: 0.7rem 1rem;
            }
             #userDropdown + .dropdown-menu {
                 position: static !important;
                 box-shadow: none;
                 border: none;
                 margin-top: 0.3rem !important;
                 background-color: transparent;
                 backdrop-filter: none;
             }
             #userDropdown + .dropdown-menu .dropdown-item {
                 padding: 0.7rem 1rem;
             }
        }

        /* Responsive Navbar Adjustments */
        @media (max-width: 992px) {
            .navbar-collapse {
                background-color: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border-radius: 12px;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
                padding: 1rem;
                margin-top: 0.8rem;
                border: 1px solid rgba(209, 213, 219, 0.2);
                max-height: 80vh;
                overflow-y: auto;
            }
            
            .navbar-collapse .nav-link {
                color: #1f2937 !important;
                margin: 0.3rem 0;
                padding: 0.7rem 1rem;
            }
            
            .navbar-collapse .nav-link:hover, .navbar-collapse .nav-link.active {
                background-color: rgba(59, 130, 246, 0.08);
                color: var(--primary-color) !important;
                transform: none;
                box-shadow: none;
            }
            
            .navbar-collapse .dropdown-menu {
                box-shadow: none;
                border: 1px solid rgba(209, 213, 219, 0.2);
                background-color: rgba(249, 250, 251, 0.5);
                margin-right: 1rem;
                margin-top: 0.3rem;
            }
            
            .navbar-collapse .dropdown-menu::before {
                display: none;
            }
            
            .nav-divider {
                width: 100%;
            }
            
            .nav-divider small {
                color: #4f46e5 !important;
                font-size: 0.8rem;
            }
            
            .navbar-toggler {
                border: none;
                padding: 0.5rem;
                border-radius: 8px;
                background-color: rgba(255, 255, 255, 0.2);
                transition: all 0.2s ease;
            }
            
            .navbar-toggler:focus {
                box-shadow: none;
                outline: none;
            }
            
            .navbar-toggler:hover {
                background-color: rgba(255, 255, 255, 0.3);
            }
            
            #userDropdown {
                background-color: transparent;
                justify-content: flex-start;
            }
            
            #userDropdown:hover, #userDropdown:focus {
                background-color: rgba(59, 130, 246, 0.08);
                transform: none;
            }
            
            #userDropdown i, .user-name {
                color: #1f2937;
            }
            
            .user-name {
                display: inline-block !important;
            }
        }
        
        /* Dropdown hover enhancements */
        @media (min-width: 992px) {
            .dropdown-menu {
                display: block;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                margin-top: 0;
            }
            
            .dropdown-menu.show {
                opacity: 1;
                visibility: visible;
            }
            
            .nav-item.dropdown:hover .dropdown-menu {
                opacity: 1;
                visibility: visible;
            }
        }
        
        /* Page Transitions */
        .page-enter {
            opacity: 0;
        }
        
        .page-enter-active {
            opacity: 1;
            transition: opacity 300ms;
        }
        
        .page-exit {
            opacity: 1;
        }
        
        .page-exit-active {
            opacity: 0;
            transition: opacity 300ms;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(243, 244, 246, 0.5);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.7);
        }
        
        /* شريط التنقل السفلي للشاشات الصغيرة */
        .mobile-bottom-nav {
            background: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 55px;
            z-index: 1000;
        }
        
        .mobile-bottom-nav__item {
            flex: 1;
            text-align: center;
        }
        
        .mobile-bottom-nav__item-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 5px 0;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .mobile-bottom-nav__item-content i {
            font-size: 1.3rem;
            margin-bottom: 2px;
        }
        
        .mobile-bottom-nav__item-content span {
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .mobile-bottom-nav__item-content.active {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            body {
                padding-bottom: 60px;
            }
        }

        /* Compact dropdown menus */
        .compact-menu {
            min-width: 200px;
        }
        
        .compact-menu .dropdown-item {
            padding: 0.4rem 0.6rem;
            margin-bottom: 0.1rem;
            font-size: 0.85rem;
        }

        /* Remove overflow:hidden from navbar and d-flex parent if present */
        .navbar, .d-flex.align-items-center {
            overflow: visible !important;
        }
    </style>
</head>
<body>
    
    <x-layouts.navbar :current-shift="$currentShift"/>

    <div class="container-fluid main-content">
        @yield('content')
    </div>

    {{-- Modal: Open Shift Required --}}
    <div class="modal fade" id="openShiftRequiredModal" tabindex="-1" aria-labelledby="openShiftRequiredModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="open-shift-modal-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="openShiftRequiredModalLabel">
                            <i class="fas fa-door-open text-success me-2"></i>{{ __('فتح وردية جديدة') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('إغلاق') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ __('يجب فتح وردية للوصول إلى نقطة البيع.') }}</p>
                        <hr>
                        <div class="mb-3">
                            <label for="modal-opening-balance" class="form-label">{{ __('المبلغ الابتدائي') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="modal-opening-balance" name="opening_balance" required>
                            <div class="invalid-feedback">{{ __('الرجاء إدخال رصيد افتتاحي صحيح.') }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="modal-shift-notes" class="form-label">{{ __('ملاحظات') }}</label>
                            <textarea class="form-control" id="modal-shift-notes" name="notes" rows="3"></textarea>
                        </div>
                        <div id="open-shift-modal-error" class="alert alert-danger d-none mt-3" role="alert">
                            {{-- Error messages will be shown here --}}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                        <button type="button" class="btn btn-success" onclick="submitOpenShiftModal()">{{ __('فتح الوردية') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- End Modal --}}

    @if($currentShift && !$currentShift->is_closed)
    <!-- Modal for closing shift (navbar) -->
    <div class="modal fade" id="closeShiftNavbarModal" tabindex="-1" aria-labelledby="closeShiftNavbarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="closeShiftNavbarModalLabel">إغلاق الوردية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form id="close-shift-modal-form">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            @php
                                $cashSales = $currentShift->invoices()->where('type', 'cash')->sum('total');
                                $deposits = $currentShift->current_deposits_total ?? 0;
                                $purchases = $currentShift->current_purchases_total ?? 0;
                                $expenses = $currentShift->current_expenses_total ?? 0;
                                $returns = $currentShift->returns_amount ?? $currentShift->current_returns_total ?? 0;
                                
                                // Calculate expected drawer amount
                                $expectedDrawer = $currentShift->opening_balance + $cashSales + $deposits - $purchases - $expenses - $returns;
                            @endphp
                            <p class="mb-1">سيتم إغلاق الوردية رقم: {{ $currentShift->shift_number }}</p>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">المبلغ الفعلي في الدرج <span class="text-danger">*</span></label>
                            <input type="number" id="actual-closing-balance" name="actual_closing_balance" step="0.01" min="0" class="form-control" required>
                            <div class="invalid-feedback">{{ __('الرجاء إدخال المبلغ الفعلي في الدرج.') }}</div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">ملاحظات الإغلاق</label>
                            <textarea id="closing-notes" name="closing_notes" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="print_inventory" id="print_inventory_navbar" value="1">
                            <label class="form-check-label" for="print_inventory_navbar">
                                طباعة تقرير جرد الأصناف المباعة
                            </label>
                        </div>
                        
                        <div id="close-shift-modal-error" class="alert alert-danger d-none mt-3" role="alert">
                            {{-- Error messages will be shown here --}}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-danger" onclick="submitCloseShiftModal()">إغلاق الوردية</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('/assets/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('/assets/select2.min.js') }}"></script>
    <script src="{{ asset('/assets/axios.min.js') }}"></script>
    <script src="{{ asset('/assets/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('/assets/sweetalert2@11') }}"></script>
    <script src="{{ asset('/assets/toastr.min.js') }}"></script>
    
    <script>
        // Function to submit the open shift modal form via AJAX
        function submitOpenShiftModal() {
            const openingBalance = $('#modal-opening-balance').val();
            const notes = $('#modal-shift-notes').val();
            
            if (!openingBalance || openingBalance <= 0) {
                $('#modal-opening-balance').addClass('is-invalid');
                return;
            }
            
            $('#modal-opening-balance').removeClass('is-invalid');
            
            $.ajax({
                url: '{{ route("shifts.store") }}',
                type: 'POST',
                data: {
                    opening_balance: openingBalance,
                    notes: notes,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        toastr.success('تم فتح الوردية بنجاح');
                        
                        // Close the modal
                        $('#openShiftRequiredModal').modal('hide');
                        
                        // Set flag to refresh page on next load
                        localStorage.setItem('shift_status_changed', 'true');
                        
                        // Redirect to sales page if that's where we were going
                        if (window.lastSalesAttempt) {
                            window.location.href = "{{ route('sales.index') }}";
                        } else {
                            // Otherwise reload the page to update the navbar
                            window.location.reload();
                        }
                    } else {
                        // Show error message
                        $('#open-shift-modal-error').removeClass('d-none').text(response.message || 'حدث خطأ أثناء فتح الوردية');
                    }
                },
                error: function(xhr) {
                    // Show error message
                    const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : 'حدث خطأ أثناء فتح الوردية';
                    
                    $('#open-shift-modal-error').removeClass('d-none').text(errorMsg);
                }
            });
        }
        
        // Function to submit the close shift modal form via AJAX
        @if($currentShift && !$currentShift->is_closed)
        function submitCloseShiftModal() {
            const actualClosingBalance = $('#actual-closing-balance').val();
            const closingNotes = $('#closing-notes').val();
            const printInventory = $('#print_inventory_navbar').is(':checked') ? 1 : 0;
            
            if (!actualClosingBalance || actualClosingBalance < 0) {
                $('#actual-closing-balance').addClass('is-invalid');
                return;
            }
            
            $('#actual-closing-balance').removeClass('is-invalid');
            
            $.ajax({
                url: '{{ route("shifts.close", $currentShift) }}',
                type: 'POST',
                data: {
                    actual_closing_balance: actualClosingBalance,
                    closing_notes: closingNotes,
                    print_inventory: printInventory,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Show success message
                    toastr.success('تم إغلاق الوردية بنجاح');
                    
                    // Close the modal
                    $('#closeShiftNavbarModal').modal('hide');
                    
                    // Set flag to refresh page on next load
                    localStorage.setItem('shift_status_changed', 'true');
                    
                    // Redirect to the shift details page instead of reloading
                    window.location.href = "{{ route('shifts.show', $currentShift) }}";
                },
                error: function(xhr) {
                    // Show error message
                    const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : 'حدث خطأ أثناء إغلاق الوردية';
                    
                    $('#close-shift-modal-error').removeClass('d-none').text(errorMsg);
                }
            });
        }
        @endif

        // Toastr Configuration
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000",
            "rtl": true
        };

        // CSRF Token Setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Function to check if there's an open shift before going to the sales page
        function checkShiftBeforeSales(event) {
            event.preventDefault();
            
            // Store that we were trying to go to sales
            window.lastSalesAttempt = true;
            
            // Check if there's an open shift
            $.ajax({
                url: '{{ route("shifts.check") }}',
                type: 'GET',
                success: function(response) {
                    console.log("Shift check response:", response);
                    if (response.hasOpenShift) {
                        // If there's an open shift, proceed to sales page
                        window.location.href = "{{ route('sales.index') }}";
                    } else {
                        // If no open shift, show the modal to open one
                        console.log("No open shift, showing modal");
                        $('#openShiftRequiredModal').modal('show');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error checking shift status:", error);
                    // In case of error, show the modal anyway
                    $('#openShiftRequiredModal').modal('show');
                }
            });
        }

        // Initialize Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                dir: 'rtl'
            });
            
            // Add active class to navbar based on current route
            const currentPath = window.location.pathname;
            $('.navbar-nav .nav-link').each(function() {
                const linkPath = $(this).attr('href');
                if (linkPath && currentPath.includes(linkPath) && linkPath !== '/') {
                    $(this).addClass('active');
                }
            });
            
            // Enable hover dropdown on all dropdown items
            if (window.innerWidth >= 992) {
                $('.dropdown').hover(
                    function() {
                        $(this).find('.dropdown-menu').addClass('show');
                    },
                    function() {
                        $(this).find('.dropdown-menu').removeClass('show');
                    }
                );
                
                // Prevent default click behavior for dropdown toggles
                $('.dropdown-toggle').click(function(e) {
                    const href = $(this).attr('href');
                    if (href && href !== '#') {
                        window.location.href = href;
                    }
                    return false;
                });
            } else {
                // For mobile, add click handler
                $('.dropdown-toggle').click(function(e) {
                    e.preventDefault();
                    $(this).next('.dropdown-menu').toggleClass('show');
                });
            }
            
            // Handle window resize
            $(window).resize(function() {
                if (window.innerWidth >= 992) {
                    // Remove mobile click handlers when switching to desktop
                    $('.dropdown-toggle').off('click');
                    
                    // Re-add hover handlers
                    $('.dropdown').hover(
                        function() {
                            $(this).find('.dropdown-menu').addClass('show');
                        },
                        function() {
                            $(this).find('.dropdown-menu').removeClass('show');
                        }
                    );
                    
                    // Prevent default click behavior for dropdown toggles
                    $('.dropdown-toggle').click(function(e) {
                        const href = $(this).attr('href');
                        if (href && href !== '#') {
                            window.location.href = href;
                        }
                        return false;
                    });
                } else {
                    // Remove hover handlers when switching to mobile
                    $('.dropdown').off('mouseenter mouseleave');
                    
                    // Add mobile click handlers
                    $('.dropdown-toggle').click(function(e) {
                        e.preventDefault();
                        $(this).next('.dropdown-menu').toggleClass('show');
                    });
                }
            });

            // Handle shift status changes
            // When closing a shift
            $(document).on('submit', 'form[action*="shifts/"]', function() {
                if ($(this).attr('action').includes('/close')) {
                    localStorage.setItem('shift_status_changed', 'true');
                }
            });

            // When opening a shift
            $(document).on('submit', '#open-shift-modal-form', function() {
                localStorage.setItem('shift_status_changed', 'true');
            });

            // Check for shift status changes on page load
            if (localStorage.getItem('shift_status_changed') === 'true') {
                localStorage.removeItem('shift_status_changed');
                // Refresh the page to update the navbar
                if (!window.location.pathname.includes('/shifts/')) {
                    window.location.reload();
                }
            }
        });
    </script>
    
    {{-- Custom Scripts --}}
    @stack('scripts')

    @if(session('settings_updated'))
    <script>
        localStorage.setItem('settings_updated', 'true');
    </script>
    @endif
    </body>
</html> 