<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #0ea5e9;
            --light-color: #f8fafc;
            --dark-color: #0f172a;
            --success-color: #10b981;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-700: #334155;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
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
            padding: 6rem 0 3rem;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .hero-subtitle {
            font-size: 1.1rem;
            color: var(--gray-700);
            margin-bottom: 2rem;
            max-width: 600px;
        }
        
        /* Contact Cards */
        .contact-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .contact-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .contact-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .contact-card p {
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .contact-card .highlight {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .contact-card .hours {
            background: var(--gray-100);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        /* Office Cards */
        .office-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            height: 100%;
        }
        
        .office-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .office-card h3 i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        .office-card address {
            font-style: normal;
            color: var(--gray-700);
            line-height: 1.8;
        }
        
        .office-card address strong {
            display: block;
            margin-top: 1rem;
            color: var(--dark-color);
        }
        
        /* Footer */
        .main-footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
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
                                <a class="nav-link active" href="contact.php">Contact</a>
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
                    <h1 class="hero-title">Get in Touch</h1>
                    <p class="hero-subtitle">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="container my-5">
        <div class="row g-4">
            <!-- Phone Contact -->
            <div class="col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3>Phone</h3>
                    <p class="highlight">+91 87399 79125</p>
                    <div class="hours">
                        <p><strong>Monday to Friday, 9am to 6pm</strong></p>
                    </div>
                </div>
            </div>
            
            <!-- Email Contact -->
            <div class="col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email</h3>
                    <p class="highlight">sales@webneticads.com</p>
                    <div class="hours">
                        <p><strong>Online support 24/7</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Office Locations -->
    <section class="container my-5">
        <div class="row g-4">
            <!-- Registered Office -->
            <div class="col-md-6">
                <div class="office-card">
                    <h3><i class="fas fa-building"></i> Registered Office</h3>
                    <address>
                        FF 29, Jtm Mall, Model Town, Jagatpura<br>
                        Jaipur, Rajasthan 302017
                    </address>
                </div>
            </div>
            
            <!-- Corporate Office -->
            <div class="col-md-6">
                <div class="office-card">
                    <h3><i class="fas fa-landmark"></i> Corporate Office</h3>
                    <address>
                        Plot 335 Phase 3, Udyog Vihar Sector 18<br>
                        Gurugram 122022
                    </address>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-links">
                <a href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                <a href="privacy_policy.php"><i class="fas fa-shield-alt me-1"></i> Privacy Policy</a>
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