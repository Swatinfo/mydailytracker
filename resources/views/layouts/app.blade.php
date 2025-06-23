<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'CEO Routine Tracker')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #f1f5f9;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --border-radius: 0.75rem;
            --transition: all 0.2s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header Styles */
        .header {
            background: white;
            box-shadow: var(--shadow);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav {
            display: flex;
            gap: 0.5rem;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
            background-color: var(--secondary-color);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        /* Main Content */
        .main {
            margin-top: 90px;
            padding: 2rem 0;
            min-height: calc(100vh - 90px);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: var(--text-primary);
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .card-content {
            color: var(--text-secondary);
        }

        /* Grid Layout */
        .grid {
            display: grid;
            gap: 1.5rem;
        }

        .grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .grid-4 {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        /* Stat Cards */
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .stat-card.success {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }

        .stat-card.danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }

        .stat-card.info {
            background: linear-gradient(135deg, var(--info-color), #0891b2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            opacity: 0.95;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #d97706;
        }

        .btn-secondary {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Progress Bar */
        .progress-bar {
            background-color: var(--border-color);
            border-radius: 1rem;
            overflow: hidden;
            height: 8px;
            margin: 0.5rem 0;
        }

        .progress-fill {
            background: linear-gradient(90deg, var(--success-color), #34d399);
            height: 100%;
            border-radius: 1rem;
            transition: width 0.3s ease;
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: #f0fdf4;
            border-color: var(--success-color);
            color: #065f46;
        }

        .alert-warning {
            background-color: #fffbeb;
            border-color: var(--warning-color);
            color: #92400e;
        }

        .alert-danger {
            background-color: #fef2f2;
            border-color: var(--danger-color);
            color: #991b1b;
        }

        .alert-info {
            background-color: #f0f9ff;
            border-color: var(--info-color);
            color: #0c4a6e;
        }

        /* Utilities */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .text-lg {
            font-size: 1.125rem;
        }

        .text-xl {
            font-size: 1.25rem;
        }

        .font-semibold {
            font-weight: 600;
        }

        .font-bold {
            font-weight: 700;
        }

        .text-muted {
            color: var(--text-muted);
        }

        .text-primary {
            color: var(--primary-color);
        }

        .text-success {
            color: var(--success-color);
        }

        .text-warning {
            color: var(--warning-color);
        }

        .text-danger {
            color: var(--danger-color);
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .mb-6 {
            margin-bottom: 1.5rem;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .mt-6 {
            margin-top: 1.5rem;
        }

        .p-2 {
            padding: 0.5rem;
        }

        .p-4 {
            padding: 1rem;
        }

        .p-6 {
            padding: 1.5rem;
        }

        .flex {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        .items-center {
            align-items: center;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .gap-4 {
            gap: 1rem;
        }

        .hidden {
            display: none;
        }

        .block {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .nav {
                flex-wrap: wrap;
                gap: 0.25rem;
            }

            .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .grid-2,
            .grid-3,
            .grid-4 {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 1rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #0f172a;
                color: #e2e8f0;
            }

            .card {
                background-color: #1e293b;
            }

            .header {
                background-color: #1e293b;
            }

            .form-input,
            .form-select,
            .form-textarea {
                background-color: #334155;
                border-color: #475569;
                color: #e2e8f0;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="{{ route('dashboard') }}" class="logo">
                    <i class="fas fa-chart-line"></i>
                    CEO Routine Tracker
                </a>

                <nav class="nav">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="{{ route('routine.show') }}"
                        class="nav-link {{ request()->routeIs('routine*') ? 'active' : '' }}">
                        <i class="fas fa-tasks"></i> Routine
                    </a>
                    <a href="{{ route('books.index') }}"
                        class="nav-link {{ request()->routeIs('books*') ? 'active' : '' }}">
                        <i class="fas fa-book"></i> Reading
                    </a>
                    <a href="{{ route('analytics.index') }}"
                        class="nav-link {{ request()->routeIs('analytics*') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    {{ session('warning') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Scripts -->
    <script>
        // CSRF token for AJAX requests
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}'
        };

        // Set up CSRF token for all AJAX requests
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                if (!options.headers) {
                    options.headers = {};
                }
                options.headers['X-CSRF-TOKEN'] = window.Laravel.csrfToken;
                return originalFetch(url, options);
            };
        }

        // Utility functions
        window.CEOTracker = {
            // Show loading state
            showLoading: function(element) {
                if (element) {
                    element.innerHTML = '<span class="loading"></span>';
                }
            },

            // Show success message
            showSuccess: function(message) {
                this.showAlert(message, 'success');
            },

            // Show error message
            showError: function(message) {
                this.showAlert(message, 'danger');
            },

            // Show alert
            showAlert: function(message, type = 'info') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;

                const container = document.querySelector('.container');
                container.insertBefore(alert, container.firstChild);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            },

            // Format time (HH:MM)
            formatTime: function(time) {
                if (!time) return '';
                return time.substring(0, 5);
            },

            // Format duration (minutes to hours:minutes)
            formatDuration: function(minutes) {
                if (!minutes) return '0 min';
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                if (hours > 0) {
                    return `${hours}h ${mins}m`;
                }
                return `${mins} min`;
            },

            // Format percentage
            formatPercentage: function(value) {
                return Math.round(value) + '%';
            },

            // Refresh page data
            refreshData: function() {
                location.reload();
            }
        };

        // Initialize tooltips and other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
