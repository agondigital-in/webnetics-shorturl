<?php
// super_admin/includes/header.php - Common header for all super_admin pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Super Admin'; ?> - webnetics-shorturl Ads Platform</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($include_chartjs) && $include_chartjs): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            min-height: 100vh;
        }
        
        /* Navbar */
        .main-navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
            padding: 0.75rem 1.5rem;
        }
        
        .main-navbar .navbar-brand {
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        .main-navbar .nav-link {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .main-navbar .nav-link:hover {
            color: white;
        }
        
        .main-navbar .btn-logout {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .main-navbar .btn-logout:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }
        
        /* Sidebar */
        .sidebar {
            background: white;
            min-height: calc(100vh - 60px);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }
        
        .sidebar .nav-link {
            color: var(--gray);
            padding: 0.75rem 1.25rem;
            margin: 0.15rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .sidebar .nav-link:hover {
            background: #f1f5f9;
            color: var(--primary);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        /* Main Content */
        .main-content {
            padding: 1.5rem;
            min-height: calc(100vh - 60px);
        }
        
        /* Cards */
        .card, .modern-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        /* Stat Cards */
        .stat-card {
            border-radius: 12px;
            padding: 1.25rem;
            color: white;
        }
        
        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .stat-card .stat-label {
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        /* Buttons */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
        }
        
        /* Tables */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background: #f8fafc;
            font-weight: 600;
            border-bottom-width: 1px;
            padding: 0.875rem 1rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray);
        }
        
        .table td {
            padding: 0.875rem 1rem;
            vertical-align: middle;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-header h2 {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .page-header p {
            color: var(--gray);
            margin-bottom: 0;
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
        }
        
        /* Forms */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 0.625rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        /* Badge */
        .badge {
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-btn {
            display: none;
        }
        
        @media (max-width: 991px) {
            .sidebar {
                display: none;
            }
            .mobile-menu-btn {
                display: block;
            }
        }
    </style>
    <?php if (isset($extra_css)): ?>
    <style><?php echo $extra_css; ?></style>
    <?php endif; ?>
</head>
<body>
