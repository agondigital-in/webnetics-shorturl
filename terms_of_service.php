<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-700: #334155;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: var(--light-color);
            color: var(--gray-700);
            line-height: 1.6;
        }
        
        /* Header Styles */
        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            position: sticky;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        /* Hero Section */
        .hero-section {
            padding: 4rem 0 2rem;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-size: 1.1rem;
            color: var(--gray-700);
            max-width: 700px;
            margin-bottom: 0;
        }
        
        /* Content Section */
        .content-section {
            padding: 3rem 0;
        }
        
        .legal-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            padding: 2.5rem;
            margin-bottom: 2rem;
        }
        
        .legal-card h1 {
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
        }
        
        .legal-card h5 {
            font-weight: 700;
            color: var(--dark-color);
            margin: 1.5rem 0 1rem;
        }
        
        .legal-card p {
            color: var(--gray-700);
            margin-bottom: 1rem;
        }
        
        .effective-date {
            background: var(--gray-100);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
            transform: translateX(-5px);
        }
        
        /* Footer */
        .main-footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 2rem;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .footer-links a {
            color: var(--gray-300);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .copyright {
            text-align: center;
            color: var(--gray-300);
            font-size: 0.9rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="container-fluid">
                    <a class="navbar-brand" href="index.php">
                        <i class="fas fa-chart-line me-2"></i>AdsPlatform
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="contact.php">Contact</a>
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
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="hero-title">Terms of Service</h1>
                    <p class="hero-subtitle">Please read these terms carefully before using our services.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Content Section -->
    <section class="content-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="legal-card">
                        <div class="effective-date">
                            <i class="fas fa-calendar-alt me-2"></i><strong>Effective Date:</strong> November 13, 2025
                        </div>
                        
                        <h5>1. Acceptance of Terms</h5>
                        <p>By accessing or using the Ads Platform services, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.</p>
                        
                        <h5>2. Description of Service</h5>
                        <p>Ads Platform provides an advertising management system that allows advertisers to create and manage campaigns, publishers to display and track campaigns, and administrators to oversee the platform.</p>
                        
                        <h5>3. User Accounts</h5>
                        <p>When you create an account with us, you must provide accurate and complete information. You are responsible for maintaining the confidentiality of your account and password and for restricting access to your computer.</p>
                        
                        <h5>4. User Responsibilities</h5>
                        <p>You agree not to use the service for any illegal or unauthorized purpose. You agree to comply with all applicable laws in your jurisdiction, including but not limited to copyright and trademark laws.</p>
                        
                        <h5>5. Intellectual Property</h5>
                        <p>The service and its original content, features, and functionality are and will remain the exclusive property of Ads Platform and its licensors. The service is protected by copyright, trademark, and other laws.</p>
                        
                        <h5>6. Termination</h5>
                        <p>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>
                        
                        <h5>7. Limitation of Liability</h5>
                        <p>In no event shall Ads Platform, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages.</p>
                        
                        <h5>8. Changes to Terms</h5>
                        <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will provide notice of any significant changes by posting the new Terms on this page.</p>
                        
                        <h5>9. Contact Information</h5>
                        <p>If you have any questions about these Terms, please contact us at terms@adsplatform.com.</p>
                        
                        <a href="index.php" class="back-link">
                            <i class="fas fa-arrow-left me-2"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-links">
                <a href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                <a href="contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                <a href="privacy_policy.php"><i class="fas fa-shield-alt me-1"></i> Privacy Policy</a>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Ads Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>