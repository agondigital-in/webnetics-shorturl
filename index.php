<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>webnetics-tracking - URL Shortener & Advertising Platform</title>
    <meta name="description" content="Professional URL shortening and advertising platform with advanced analytics, campaign management, and monetization tools.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Webneticads Color Scheme - Gradient Blue/Purple/Cyan */
            --primary: #00D4FF;
            --primary-dark: #0099CC;
            --secondary: #8B5CF6;
            --accent: #F97316;
            --gradient-start: #667eea;
            --gradient-mid: #764ba2;
            --gradient-end: #00D4FF;
            --success: #10b981;
            --warning: #f59e0b;
            --dark: #0a0a1a;
            --dark-light: #12122a;
            --dark-card: #1a1a3a;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            color: var(--white);
            overflow-x: hidden;
            line-height: 1.7;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--dark);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 4px;
        }
        
        /* Animated Background - Webneticads Style */
        .bg-gradient-animated {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0a0a1a 0%, #12122a 50%, #1a1a3a 100%);
            z-index: -2;
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.3;
            animation: float 25s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 700px;
            height: 700px;
            background: linear-gradient(135deg, var(--secondary), #a855f7);
            top: -300px;
            right: -200px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            bottom: -200px;
            left: -200px;
            animation-delay: -8s;
        }
        
        .shape-3 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-mid));
            top: 40%;
            left: 40%;
            animation-delay: -15s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg) scale(1); }
            25% { transform: translateY(-50px) rotate(10deg) scale(1.05); }
            50% { transform: translateY(0) rotate(-10deg) scale(1); }
            75% { transform: translateY(50px) rotate(5deg) scale(0.95); }
        }
        
        /* Header - Matching Webneticads */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.4s ease;
        }
        
        .main-header.scrolled {
            background: rgba(10, 10, 26, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 5px 30px rgba(0, 212, 255, 0.1);
            padding: 0.5rem 0;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            text-decoration: none;
        }
        
        .brand-logo {
            height: 45px;
            width: auto;
            filter: drop-shadow(0 0 10px rgba(0, 212, 255, 0.3));
        }
        
        .brand-text {
            font-weight: 800;
            font-size: 1.6rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-link {
            color: var(--gray-300) !important;
            font-weight: 500;
            padding: 0.6rem 1.2rem !important;
            margin: 0 0.1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .nav-link:hover {
            color: var(--primary) !important;
            background: rgba(0, 212, 255, 0.1);
        }
        
        .nav-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white) !important;
            padding: 0.7rem 1.8rem !important;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 5px 25px rgba(0, 212, 255, 0.4);
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        
        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 35px rgba(0, 212, 255, 0.5);
        }
        
        /* Hero Section - Webneticads Style */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 8rem 0 5rem;
            position: relative;
        }
        
        .hero-content {
            text-align: center;
            max-width: 950px;
            margin: 0 auto;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(0, 212, 255, 0.3);
            padding: 0.7rem 1.8rem;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            color: var(--primary);
            animation: fadeInUp 0.8s ease;
        }
        
        .hero-badge i {
            color: var(--accent);
        }
        
        .hero-tagline {
            font-size: 1.2rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: 600;
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease 0.1s both;
        }
        
        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .hero-title .gradient-text {
            background: linear-gradient(135deg, var(--primary), var(--secondary), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--gray-400);
            max-width: 700px;
            margin: 0 auto 3rem;
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.6s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Buttons - Webneticads Style */
        .btn-glow {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--dark);
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            transition: all 0.4s ease;
            box-shadow: 0 10px 40px rgba(0, 212, 255, 0.35);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }
        
        .btn-glow:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 212, 255, 0.5);
            color: var(--dark);
        }
        
        .btn-glow:hover::before {
            left: 100%;
        }
        
        .btn-glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 212, 255, 0.4);
            color: var(--primary);
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-glass:hover {
            background: rgba(0, 212, 255, 0.15);
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.2);
            color: var(--primary);
        }
        
        /* Stats Bar - Webneticads Style */
        .stats-bar {
            padding: 3rem 0;
            margin-top: 4rem;
            animation: fadeInUp 0.8s ease 0.8s both;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 2rem 1.5rem;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.05), rgba(139, 92, 246, 0.05));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 20px;
            transition: all 0.4s ease;
        }
        
        .stat-item:hover {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(139, 92, 246, 0.1));
            transform: translateY(-8px);
            border-color: rgba(0, 212, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0, 212, 255, 0.15);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: var(--gray-400);
            font-size: 1rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        
        /* Innovation Section - Webneticads Style */
        .innovation-section {
            padding: 8rem 0;
            position: relative;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-tag {
            display: inline-block;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.2), rgba(139, 92, 246, 0.2));
            border: 1px solid rgba(0, 212, 255, 0.3);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .section-title .highlight {
            color: var(--primary);
        }
        
        .section-subtitle {
            font-size: 1.15rem;
            color: var(--gray-400);
            max-width: 650px;
            margin: 0 auto;
        }
        
        /* Features Grid - Webneticads Style */
        .feature-card {
            background: linear-gradient(135deg, rgba(26, 26, 58, 0.8), rgba(18, 18, 42, 0.8));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 212, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem;
            height: 100%;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 212, 255, 0.1);
        }
        
        .feature-card:hover::before {
            opacity: 1;
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            box-shadow: 0 15px 35px rgba(0, 212, 255, 0.25);
        }
        
        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--white);
        }
        
        .feature-card p {
            color: var(--gray-400);
            font-size: 1rem;
            line-height: 1.7;
        }
        
        /* Login Cards Section - Webneticads Style */
        .login-section {
            padding: 8rem 0;
            background: linear-gradient(180deg, transparent, rgba(0, 212, 255, 0.03));
        }
        
        .login-cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3rem;
            max-width: 950px;
            margin: 0 auto;
        }
        
        .login-card {
            background: linear-gradient(135deg, rgba(26, 26, 58, 0.9), rgba(18, 18, 42, 0.9));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 28px;
            padding: 3rem;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .login-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.4), 0 0 50px rgba(0, 212, 255, 0.15);
            border-color: rgba(0, 212, 255, 0.4);
        }
        
        .login-icon {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            color: var(--dark);
            margin: 0 auto 2rem;
            box-shadow: 0 20px 50px rgba(0, 212, 255, 0.3);
        }
        
        .login-card.admin-card .login-icon {
            background: linear-gradient(135deg, var(--primary), #06b6d4);
        }
        
        .login-card.publisher-card .login-icon {
            background: linear-gradient(135deg, var(--secondary), #a855f7);
        }
        
        .login-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .login-card p {
            color: var(--gray-400);
            margin-bottom: 2rem;
            font-size: 1rem;
            line-height: 1.7;
        }
        
        .login-card .btn-glow {
            width: 100%;
            justify-content: center;
        }
        
        /* Brand Partners Section */
        .partners-section {
            padding: 5rem 0;
            background: rgba(0, 212, 255, 0.02);
        }
        
        .partners-title {
            text-align: center;
            color: var(--gray-500);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 2rem;
        }
        
        /* Testimonials - Webneticads Style */
        .testimonials-section {
            padding: 8rem 0;
        }
        
        .testimonial-card {
            background: linear-gradient(135deg, rgba(26, 26, 58, 0.8), rgba(18, 18, 42, 0.8));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 212, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem;
            height: 100%;
            transition: all 0.4s ease;
        }
        
        .testimonial-card:hover {
            transform: translateY(-8px);
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }
        
        .testimonial-stars {
            color: var(--accent);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .testimonial-text {
            color: var(--gray-300);
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
            font-style: italic;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .author-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .author-info h4 {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        
        .author-info span {
            font-size: 0.85rem;
            color: var(--primary);
        }
        
        /* Footer - Webneticads Style */
        .main-footer {
            padding: 5rem 0 2rem;
            background: linear-gradient(180deg, transparent, rgba(0, 212, 255, 0.03));
            border-top: 1px solid rgba(0, 212, 255, 0.1);
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin-bottom: 1.5rem;
        }
        
        .footer-brand img {
            height: 40px;
            width: auto;
        }
        
        .footer-brand-text {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .footer-description {
            color: var(--gray-400);
            max-width: 320px;
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }
        
        .footer-social {
            display: flex;
            gap: 1rem;
        }
        
        .social-link {
            width: 48px;
            height: 48px;
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        
        .social-link:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--dark);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.3);
            border-color: transparent;
        }
        
        .footer-links h4 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--white);
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.9rem;
        }
        
        .footer-links a {
            color: var(--gray-400);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        
        .footer-links a:hover {
            color: var(--primary);
            transform: translateX(5px);
        }
        
        .footer-bottom {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 212, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .footer-bottom p {
            color: var(--gray-500);
            font-size: 0.9rem;
            margin: 0;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 2rem;
        }
        
        .footer-bottom-links a {
            color: var(--gray-500);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .footer-bottom-links a:hover {
            color: var(--primary);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .login-cards-grid {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.3rem;
            }
            
            .hero-subtitle {
                font-size: 1.05rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-glow, .btn-glass {
                width: 100%;
                max-width: 320px;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
        
        /* Navbar Toggler */
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%280, 212, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Pulse Animation */
        .pulse-animation {
            animation: pulse 2.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 10px 40px rgba(0, 212, 255, 0.35); }
            50% { box-shadow: 0 10px 60px rgba(0, 212, 255, 0.55); }
        }
        
        /* Glow Text Effect */
        .glow-text {
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-gradient-animated"></div>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <!-- Header -->
    <header class="main-header" id="header">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-0">
                    <a class="navbar-brand" href="#">
                        <img src="assets/images/logo.png" alt="webnetics-tracking Logo" class="brand-logo">
                        <span class="brand-text">tracking</span>
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item">
                                <a class="nav-link" href="#">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#features">Features</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#login">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="contact.php">Contact</a>
                            </li>
                            <li class="nav-item ms-lg-2">
                                <a class="nav-link nav-btn" href="publisher_login.php">
                                    <i class="fas fa-rocket me-1"></i> Get Started
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-bolt"></i>
                    <span>Leading URL Shortening & Advertising Platform</span>
                </div>
                <p class="hero-tagline">Creative. Strategic. Everywhere</p>
                <h1 class="hero-title">
                    360° Solutions for<br>
                    <span class="gradient-text glow-text">Your Brand</span>
                </h1>
                <p class="hero-subtitle">
                    Delivering performance on Web & Mobile to worldwide advertisers through a growing network 
                    of 1K+ affiliates with presence across India.
                </p>
                <div class="hero-buttons">
                    <a href="publisher_login.php" class="btn-glow pulse-animation">
                        <i class="fas fa-user-plus"></i> Join Publisher Agency
                    </a>
                    <a href="login.php" class="btn-glass">
                        <i class="fas fa-briefcase"></i> Join Advertiser Agency
                    </a>
                </div>
            </div>
            
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">10K+</div>
                        <div class="stat-label">Active Campaigns</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Advertisers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">1K+</div>
                        <div class="stat-label">Affiliates</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">1M+</div>
                        <div class="stat-label">Monthly Clicks</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Innovation Section -->
    <section class="innovation-section" id="features">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">First in Innovation</span>
                <h2 class="section-title">Lead the <span class="highlight">Digital Space</span></h2>
                <p class="section-subtitle">
                    Top class user experience, in-depth industry knowledge and customized solutions, 
                    to become a trendsetter for the industry in values, wisdom & revolution.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Innovation</h3>
                        <p>Pioneering cutting-edge solutions in affiliate marketing technology for maximum performance.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <h3>User Experience</h3>
                        <p>Delivering seamless and intuitive platforms for all stakeholders with modern interfaces.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h3>Industry Knowledge</h3>
                        <p>Deep expertise across multiple verticals and market segments for strategic growth.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>Custom Solutions</h3>
                        <p>Tailored strategies that align with your unique business goals and objectives.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Real-time Analytics</h3>
                        <p>Get detailed insights with live tracking, geo-location data, and performance metrics.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure Platform</h3>
                        <p>Advanced security features with fraud detection and protection for all users.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Section -->
    <section class="login-section" id="login">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Get Started</span>
                <h2 class="section-title">Choose Your <span class="highlight">Portal</span></h2>
                <p class="section-subtitle">
                    Select your role to access the appropriate dashboard and start growing
                </p>
            </div>
            
            <div class="login-cards-grid">
                <div class="login-card admin-card">
                    <div class="login-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Advertiser Portal</h3>
                    <p>Access the admin dashboard to manage campaigns, publishers, and view comprehensive analytics and reports.</p>
                    <a href="login.php" class="btn-glow">
                        <i class="fas fa-sign-in-alt"></i> Login as Admin
                    </a>
                </div>
                
                <div class="login-card publisher-card">
                    <div class="login-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h3>Publisher Portal</h3>
                    <p>Track your campaigns, view detailed performance metrics, manage your earnings, and optimize your revenue.</p>
                    <a href="publisher_login.php" class="btn-glow" style="background: linear-gradient(135deg, #8B5CF6, #a855f7);">
                        <i class="fas fa-sign-in-alt"></i> Login as Publisher
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Testimonials</span>
                <h2 class="section-title">Trusted by <span class="highlight">Thousands</span></h2>
                <p class="section-subtitle">
                    See what our publishers and advertisers have to say about us
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Amazing platform! The analytics are incredibly detailed and the payments are always on time. Highly recommended for publishers."</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">AK</div>
                            <div class="author-info">
                                <h4>Amit Kumar</h4>
                                <span>Top Publisher</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"The campaign management tools are top-notch. We've seen a 300% increase in our advertising ROI since switching."</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">RS</div>
                            <div class="author-info">
                                <h4>Rahul Sharma</h4>
                                <span>Advertiser</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="testimonial-text">"Best URL shortener platform I've used. The interface is intuitive and the support team is always helpful."</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">PV</div>
                            <div class="author-info">
                                <h4>Priya Verma</h4>
                                <span>Digital Marketer</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="footer-brand">
                        <img src="assets/images/logo.png" alt="webnetics-tracking Logo">
                        <span class="footer-brand-text">webnetics-tracking</span>
                    </div>
                    <p class="footer-description">
                        Professional URL shortening and advertising platform delivering performance on Web & Mobile to worldwide advertisers.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0 footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> Features</a></li>
                        <li><a href="#login"><i class="fas fa-chevron-right"></i> Login</a></li>
                        <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-4 mb-md-0 footer-links">
                    <h4>For Partners</h4>
                    <ul>
                        <li><a href="login.php"><i class="fas fa-chevron-right"></i> Advertiser Login</a></li>
                        <li><a href="publisher_login.php"><i class="fas fa-chevron-right"></i> Publisher Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 footer-links">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="privacy_policy.php"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                        <li><a href="terms_of_service.php"><i class="fas fa-file-contract"></i> Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 webnetics-tracking. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="privacy_policy.php">Privacy</a>
                    <a href="terms_of_service.php">Terms</a>
                    <a href="contact.php">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
        
        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px'
        };
        
        const animateValue = (element, start, end, duration) => {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const value = Math.floor(progress * (end - start) + start);
                element.textContent = value + (element.dataset.suffix || '');
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        };
        
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const text = stat.textContent;
                        const match = text.match(/(\d+)([KM+]*)/);
                        if (match && !stat.classList.contains('animated')) {
                            stat.classList.add('animated');
                            const num = parseInt(match[1]);
                            stat.dataset.suffix = match[2];
                            stat.textContent = '0' + match[2];
                            animateValue(stat, 0, num, 2000);
                        }
                    });
                }
            });
        }, observerOptions);
        
        const statsBar = document.querySelector('.stats-bar');
        if (statsBar) {
            statsObserver.observe(statsBar);
        }
    </script>
</body>
</html>