<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zambia Police Service - Anti-Fraud and Cyber Crime Unit</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1E3A8A; /* Deep blue */
            --secondary: #047857; /* Green */
            --accent: #DC2626; /* Red */
            --light: #F9FAFB;
            --dark: #111827;
            --gray: #6B7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Section */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 60px;
            margin-right: 15px;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-text h1 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.2;
        }

        .logo-text p {
            font-size: 14px;
            color: var(--gray);
        }

        .login-btn {
            background-color: var(--primary);
            color: white;
            padding: 10px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background-color: #1E40AF;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('/imgs/hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding-top: 60px;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h2 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .primary-btn {
            background-color: var(--primary);
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .primary-btn:hover {
            background-color: #1E40AF;
            transform: translateY(-2px);
        }

        .secondary-btn {
            background-color: transparent;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            border: 2px solid white;
            transition: all 0.3s ease;
        }

        .secondary-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Features Section */
        .features {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h3 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            height: 60px;
            width: 60px;
            background-color: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .feature-icon i {
            font-size: 24px;
            color: var(--primary);
        }

        .feature-card h4 {
            font-size: 20px;
            margin-bottom: 15px;
        }

        /* About Section */
        .about {
            padding: 80px 0;
            background-color: #F3F4F6;
        }

        .about-content {
            display: flex;
            align-items: center;
            gap: 60px;
        }

        .about-text {
            flex: 1;
        }

        .about-text h3 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .about-text p {
            margin-bottom: 20px;
        }

        .about-image {
            flex: 1;
        }

        .about-image img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 60px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 40px;
            height: 2px;
            background-color: var(--accent);
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul li a {
            color: #D1D5DB;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-col ul li a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            height: 40px;
            width: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #9CA3AF;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 20px;
            }

            .hero h2 {
                font-size: 32px;
            }

            .hero-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .about-content {
                flex-direction: column;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="/imgs/logo.png" alt="Zambia Police Service Logo">
                    <div class="logo-text">
                        <h1>ZAMBIA POLICE SERVICE</h1>
                        <p>Anti-Fraud & Cyber Crime Unit SHQ</p>
                    </div>
                </div>
                <a href="/admin" class="login-btn">Login to System</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Case Management System</h2>
                <p>Streamlining investigation processes and case tracking for the Anti-Fraud and Cyber Crime Unit. Enhancing efficiency, collaboration, and accountability in fraud investigations.</p>
                <div class="hero-buttons">
                    <a href="/admin" class="primary-btn">Login to System</a>
                    <a href="#about" class="secondary-btn">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h3>System Features</h3>
                <p>Our case management system streamlines investigation workflows with powerful tools designed for the Anti-Fraud and Cyber Crime Unit.</p>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h4>Case Tracking</h4>
                    <p>Monitor cases from initial complaint to case closure with comprehensive tracking of all investigation activities.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Officer Management</h4>
                    <p>Assign cases to investigators and track officer performance with customized dashboards for supervisors.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Performance Analytics</h4>
                    <p>Generate reports and visualize data on case progress, clearance rates, and investigator performance.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h4>Notifications</h4>
                    <p>Automated SMS and in-system notifications for case updates, assignments, and supervisor comments.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h4>Report Generation</h4>
                    <p>Create and export comprehensive PDF reports for cases, investigations, and statistical analysis.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Secure Access</h4>
                    <p>Role-based access control ensures officers only see information relevant to their responsibilities.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h3>About Our Case Management System</h3>
                    <p>The Anti-Fraud and Cyber Crime Unit Case Management System is designed to enhance the efficiency and effectiveness of fraud investigations by the Zambia Police Service.</p>
                    <p>Our system provides a comprehensive solution for managing cases from initial complaint to final resolution, with features designed specifically for the unique needs of fraud investigators.</p>
                    <p>By streamlining workflows, improving communication between officers and supervisors, and providing powerful reporting tools, our system helps the Anti-Fraud Unit better serve the public and bring offenders to justice.</p>
                </div>
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1557597774-9d273605dfa9?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Case Management System">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h4>Zambia Police Service</h4>
                    <p>Anti-Fraud & Cyber Crime Unit<br>Service Headquarters<br>Lusaka, Zambia</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/admin">Login</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Help</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Contact Information</h4>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Independence Avenue, Lusaka</li>
                        <li><i class="fas fa-phone"></i> +260 211 123456</li>
                        <li><i class="fas fa-envelope"></i> antifraud@zps.gov.zm</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2025 Zambia Police Service Anti-Fraud & Cyber Crime Unit. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
