<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --accent-color: #34d399;
            --light-color: #f0fdf4;
            --dark-color: #065f46;
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
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
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
                    <h1 class="hero-title">Privacy Policy</h1>
                    <p class="hero-subtitle">We are committed to protecting your privacy and personal information.</p>
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
                        
                        <h5>1. Information We Collect</h5>
                        <p>We collect information you provide directly to us, such as when you create an account, use our services, or contact us for support. This may include your name, email address, company information, and payment details.</p>
                        
                        <h5>2. How We Use Your Information</h5>
                        <p>We use the information we collect to provide, maintain, and improve our services, to process transactions, and to communicate with you. We may also use this information for analytics and research purposes.</p>
                        
                        <h5>3. Information Sharing</h5>
                        <p>We do not sell, trade, or otherwise transfer your personally identifiable information to outside parties. This does not include trusted third parties who assist us in operating our website, conducting our business, or servicing you, as long as those parties agree to keep this information confidential.</p>
                        
                        <h5>4. Data Security</h5>
                        <p>We implement a variety of security measures to maintain the safety of your personal information. All supplied sensitive information is transmitted via Secure Socket Layer (SSL) technology and then encrypted into our databases.</p>
                        
                        <h5>5. Cookies</h5>
                        <p>We use cookies and similar tracking technologies to track activity on our services and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>
                        
                        <h5>6. Your Rights</h5>
                        <p>You have the right to access, update, or delete your personal information at any time. You may also have the right to object to or restrict certain processing of your personal information.</p>
                        
                        <h5>7. Changes to This Policy</h5>
                        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the effective date.</p>
                        
                        <h5>8. Contact Us</h5>
                        <p>If you have any questions about this Privacy Policy, please contact us at privacy@adsplatform.com.</p>
                        
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
                <a href="terms_of_service.php"><i class="fas fa-file-contract me-1"></i> Terms of Service</a>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Ads Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>