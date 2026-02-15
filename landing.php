<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WattAWaste - Smart Composting Solution</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --brand-primary: #23ed99ff;
            --brand-secondary: #0f8156ff;
            --brand-dark: #0a5d40;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-light: #f9fafb;
            --card-bg: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
            background: var(--bg-light);
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.12);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-logo i {
            color: var(--brand-secondary);
            -webkit-text-fill-color: var(--brand-secondary);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 40px;
            align-items: center;
        }

        .nav-menu li a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            padding: 8px 0;
            position: relative;
        }

        .nav-menu li a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            transition: width 0.3s ease;
        }

        .nav-menu li a:hover::after {
            width: 100%;
        }

        .nav-login-btn {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: white !important;
            padding: 12px 28px !important;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(35, 237, 153, 0.3);
            transition: all 0.3s ease;
        }

        .nav-login-btn::after {
            display: none;
        }

        .nav-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(35, 237, 153, 0.4);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--brand-secondary);
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            min-height: 90vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(35, 237, 153, 0.05), rgba(15, 129, 86, 0.05));
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 800px;
            height: 800px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            opacity: 0.1;
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-50px, 50px) rotate(180deg); }
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 56px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 24px;
            color: var(--text-dark);
        }

        .hero-content .gradient-text {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content p {
            font-size: 18px;
            line-height: 1.8;
            color: var(--text-muted);
            margin-bottom: 40px;
        }

        .hero-cta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 16px 36px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(35, 237, 153, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(35, 237, 153, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--brand-secondary);
            border: 2px solid var(--brand-secondary);
        }

        .btn-secondary:hover {
            background: var(--brand-secondary);
            color: white;
        }

        .hero-image {
            position: relative;
        }

        .hero-image-main {
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            animation: heroImageFloat 6s infinite ease-in-out;
        }

        @keyframes heroImageFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Features/About Section */
        .about-section {
            padding: 100px 40px;
            background: white;
        }

        .section-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 70px;
        }

        .section-badge {
            display: inline-block;
            padding: 8px 20px;
            background: linear-gradient(135deg, rgba(35, 237, 153, 0.1), rgba(15, 129, 86, 0.1));
            color: var(--brand-secondary);
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .section-header h2 {
            font-size: 42px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 16px;
        }

        .section-header p {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .feature-card {
            background: var(--bg-light);
            padding: 40px 30px;
            border-radius: 20px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: var(--brand-primary);
            box-shadow: 0 20px 40px rgba(35, 237, 153, 0.15);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin-bottom: 24px;
            box-shadow: 0 8px 20px rgba(35, 237, 153, 0.3);
        }

        .feature-card h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-dark);
        }

        .feature-card p {
            font-size: 15px;
            line-height: 1.7;
            color: var(--text-muted);
        }

        /* Client Background Section */
        .client-section {
            padding: 100px 40px;
            background: linear-gradient(135deg, rgba(35, 237, 153, 0.05), rgba(15, 129, 86, 0.05));
        }

        .client-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .client-text h2 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 24px;
            color: var(--text-dark);
        }

        .client-text p {
            font-size: 16px;
            line-height: 1.9;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .client-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .client-image {
            position: relative;
        }

        .client-image img {
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        /* Garden Plants Section */
        .garden-section {
            padding: 100px 40px;
            background: white;
        }

        /* Season Carousel Container */
        .season-carousel-container {
            margin-top: 50px;
            position: relative;
        }

        /* Current Season Badge */
        .current-season-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: white;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 30px;
            box-shadow: 0 6px 20px rgba(35, 237, 153, 0.3);
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .current-season-badge i {
            font-size: 20px;
        }

        /* Season Navigation Tabs */
        .season-nav {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 50px;
            flex-wrap: wrap;
        }

        .season-nav-btn {
            padding: 20px 30px;
            background: var(--bg-light);
            border: 3px solid transparent;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            min-width: 200px;
        }

        .season-nav-btn i {
            font-size: 32px;
            color: var(--brand-secondary);
            margin-bottom: 8px;
        }

        .season-nav-btn span {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .season-nav-btn small {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .season-nav-btn:hover {
            transform: translateY(-5px);
            border-color: var(--brand-primary);
            box-shadow: 0 10px 25px rgba(35, 237, 153, 0.2);
        }

        .season-nav-btn.active {
            background: linear-gradient(135deg, rgba(35, 237, 153, 0.1), rgba(15, 129, 86, 0.1));
            border-color: var(--brand-secondary);
            box-shadow: 0 8px 20px rgba(35, 237, 153, 0.25);
        }

        .season-nav-btn.active span {
            color: var(--brand-secondary);
        }

        /* Plants Carousel */
        .plants-carousel {
            position: relative;
            overflow: hidden;
        }

        .season-plants {
            display: none;
            animation: fadeIn 0.6s ease;
        }

        .season-plants.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .plants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            margin-top: 0;
        }

        .plant-card {
            background: var(--bg-light);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .plant-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .plant-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            position: relative;
            overflow: hidden;
        }

        .plant-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle, transparent 30%, rgba(0,0,0,0.1));
        }

        .plant-info {
            padding: 24px;
        }

        .plant-info h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .plant-info .plant-name {
            font-size: 14px;
            color: var(--text-muted);
            font-style: italic;
            margin-bottom: 12px;
        }

        .plant-info p {
            font-size: 14px;
            line-height: 1.7;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        /* Fertilizer Info Badge */
        .fertilizer-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: white;
            border-radius: 12px;
            margin-bottom: 16px;
            border-left: 4px solid var(--brand-secondary);
            font-size: 13px;
            color: var(--text-dark);
        }

        .fertilizer-info i {
            font-size: 18px;
            color: var(--brand-secondary);
        }

        .fertilizer-info strong {
            font-weight: 700;
            margin-right: 4px;
        }

        /* Special styling for anaerobic compost */
        .fertilizer-info i.fa-water {
            color: #3b82f6;
        }

        .plant-tags {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .plant-tag {
            padding: 6px 14px;
            background: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--brand-secondary);
        }

        /* Carousel Navigation Buttons */
        .carousel-controls {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 50px;
        }

        .carousel-btn {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(35, 237, 153, 0.3);
        }

        .carousel-btn:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 6px 25px rgba(35, 237, 153, 0.4);
        }

        .carousel-btn:active {
            transform: translateY(-1px) scale(1.05);
        }

        /* CTA Section */
        .cta-section {
            padding: 100px 40px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .cta-section::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .cta-content h2 {
            font-size: 48px;
            font-weight: 800;
            color: white;
            margin-bottom: 24px;
        }

        .cta-content p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            line-height: 1.8;
        }

        .cta-content .btn {
            background: white;
            color: var(--brand-secondary);
            font-size: 18px;
            padding: 18px 40px;
        }

        .cta-content .btn:hover {
            background: var(--bg-light);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: white;
            padding: 60px 40px 30px;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 60px;
            margin-bottom: 40px;
        }

        .footer-logo {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .footer-desc {
            font-size: 14px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 20px;
        }

        .footer-social {
            display: flex;
            gap: 12px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            transform: translateY(-3px);
        }

        .footer-section h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--brand-primary);
            padding-left: 5px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            text-align: center;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-container,
            .client-content {
                grid-template-columns: 1fr;
                gap: 50px;
            }

            .hero-content h1 {
                font-size: 42px;
            }

            .footer-container {
                grid-template-columns: 1fr 1fr;
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 30px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                transform: translateY(-100%);
                opacity: 0;
                transition: all 0.3s ease;
            }

            .nav-menu.active {
                transform: translateY(0);
                opacity: 1;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .hero-content h1 {
                font-size: 36px;
            }

            .section-header h2,
            .client-text h2 {
                font-size: 32px;
            }

            .features-grid,
            .plants-grid {
                grid-template-columns: 1fr;
            }

            .client-stats {
                grid-template-columns: 1fr;
            }

            .footer-container {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .cta-content h2 {
                font-size: 32px;
            }
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Loading Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-on-scroll {
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-leaf"></i>
                WattAWaste
            </div>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#client">Our Story</a></li>
                <li><a href="#garden">Garden</a></li>
                <li><a href="login.php" class="nav-login-btn">Login</a></li>
            </ul>

            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <h1>
                    Transform Waste Into <span class="gradient-text">Sustainable Growth</span>
                </h1>
                <p>
                    WattAWaste is an intelligent composting solution that monitors and optimizes your organic waste transformation into nutrient-rich fertilizer. Join us in creating a greener, more sustainable future.
                </p>
                <div class="hero-cta">
                    <a href="login.php" class="btn btn-primary">
                        Get Started
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#about" class="btn btn-secondary">
                        Learn More
                        <i class="fas fa-info-circle"></i>
                    </a>
                </div>
            </div>

            <div class="hero-image">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 500 500'%3E%3Cdefs%3E%3ClinearGradient id='grad1' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%2323ed99;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%230f8156;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23grad1)' width='500' height='500' rx='30'/%3E%3Cg fill='white' opacity='0.2'%3E%3Ccircle cx='250' cy='250' r='150'/%3E%3Ccircle cx='150' cy='150' r='80'/%3E%3Ccircle cx='350' cy='350' r='100'/%3E%3C/g%3E%3Ctext x='250' y='270' font-family='Arial, sans-serif' font-size='120' fill='white' text-anchor='middle' opacity='0.9'%3E🌱%3C/text%3E%3C/svg%3E" 
                     alt="WattAWaste Smart Composting" 
                     class="hero-image-main">
            </div>
        </div>
    </section>

    <!-- About/Features Section -->
    <section class="about-section" id="about">
        <div class="section-container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">WHY CHOOSE US</span>
                <h2>Smart Composting Made Simple</h2>
                <p>
                    Our intelligent system monitors temperature, humidity, pH levels, and gas emissions to ensure optimal composting conditions. Get real-time insights and automated controls for perfect compost every time.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-thermometer-half"></i>
                    </div>
                    <h3>Real-Time Monitoring</h3>
                    <p>
                        Track temperature, humidity, pH, and gas levels in real-time with our advanced sensor network. Receive instant alerts when conditions need attention.
                    </p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>Automated Control</h3>
                    <p>
                        Smart mixer controls automatically optimize your compost. Set it and forget it with our intelligent automation system.
                    </p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analytics & Insights</h3>
                    <p>
                        Comprehensive dashboards and historical data visualization help you understand and improve your composting process over time.
                    </p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Eco-Friendly</h3>
                    <p>
                        Reduce waste, lower carbon footprint, and create nutrient-rich fertilizer for your garden. Good for you, great for the planet.
                    </p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Access</h3>
                    <p>
                        Monitor and control your compost bin from anywhere with our responsive web interface. Check status on any device, anytime.
                    </p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Smart Notifications</h3>
                    <p>
                        Get notified when your compost reaches optimal stages or needs attention. Never miss important composting milestones.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Client Background Section -->
    <section class="client-section" id="client">
        <div class="section-container">
            <div class="client-content">
                <div class="client-text animate-on-scroll">
                    <span class="section-badge">OUR STORY</span>
                    <h2>From Vision to Reality</h2>
                    <p>
                        WattAWaste was born from a simple observation: too much organic waste ends up in landfills when it could nourish our gardens instead. Our team of environmental engineers and tech enthusiasts came together to create a solution that makes composting accessible, efficient, and intelligent.
                    </p>
                    <p>
                        Starting as a university research project, we've grown into a comprehensive platform that helps households and communities transform their organic waste into valuable resources. Our mission is to make sustainable living effortless through technology.
                    </p>
                    <p>
                        Today, WattAWaste serves gardening enthusiasts, eco-conscious families, and urban farmers who want to minimize waste while maximizing their garden's potential. We're proud to be part of the circular economy movement, one compost bin at a time.
                    </p>

                    <div class="client-stats">
                        <div class="stat-card">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">Active Users</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">10K+</div>
                            <div class="stat-label">Tons Composted</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">95%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Monitoring</div>
                        </div>
                    </div>
                </div>

                <div class="client-image animate-on-scroll">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 600'%3E%3Cdefs%3E%3ClinearGradient id='grad2' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%2323ed99;stop-opacity:0.8' /%3E%3Cstop offset='100%25' style='stop-color:%230f8156;stop-opacity:0.8' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23grad2)' width='600' height='600' rx='30'/%3E%3Cg fill='white' opacity='0.3'%3E%3Crect x='50' y='100' width='200' height='300' rx='10'/%3E%3Crect x='300' y='150' width='250' height='350' rx='10'/%3E%3Ccircle cx='450' cy='100' r='60'/%3E%3C/g%3E%3Ctext x='300' y='340' font-family='Arial, sans-serif' font-size='150' fill='white' text-anchor='middle' opacity='0.8'%3E🌍%3C/text%3E%3C/svg%3E" 
                         alt="Our Story">
                </div>
            </div>
        </div>
    </section>

    <!-- Garden Plants Section -->
    <section class="garden-section" id="garden">
        <div class="section-container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">OUR GARDEN</span>
                <h2>Plants Thriving with WattAWaste</h2>
                <p>
                    See the incredible results from our community members who are growing beautiful, healthy plants using compost created with WattAWaste. From vegetables to ornamentals, our fertilizer supports diverse plant life.
                </p>
            </div>

            <!-- Philippine Season Carousel -->
            <div class="season-carousel-container">
                <!-- Current Season Indicator -->
                <div class="current-season-badge" id="currentSeasonBadge">
                    <i class="fas fa-sun"></i>
                    <span id="currentSeasonText">Loading season...</span>
                </div>

                <!-- Season Navigation -->
                <div class="season-nav">
                    <button class="season-nav-btn active" data-season="amihan">
                        <i class="fas fa-wind"></i>
                        <span>Amihan</span>
                        <small>Cool & Dry (Nov-Feb)</small>
                    </button>
                    <button class="season-nav-btn" data-season="tag-init">
                        <i class="fas fa-sun"></i>
                        <span>Tag-init</span>
                        <small>Hot & Dry (Mar-May)</small>
                    </button>
                    <button class="season-nav-btn" data-season="tag-ulan">
                        <i class="fas fa-cloud-rain"></i>
                        <span>Tag-ulan</span>
                        <small>Rainy (Jun-Oct)</small>
                    </button>
                </div>

                <!-- Plants Grid (Carousel Content) -->
                <div class="plants-carousel">
                    <!-- Amihan Season (Cool & Dry: November - February) -->
                    <div class="season-plants active" data-season="amihan">
                        <div class="plants-grid">
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥜</div>
                                <div class="plant-info">
                                    <h3>Peanuts (Mani)</h3>
                                    <p class="plant-name">Arachis hypogaea</p>
                                    <p>
                                        Protein-rich legumes thriving in cool, dry conditions. Excellent for nitrogen fixation and soil improvement.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Legumes</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥬</div>
                                <div class="plant-info">
                                    <h3>Lettuce</h3>
                                    <p class="plant-name">Lactuca sativa</p>
                                    <p>
                                        Crisp, fresh lettuce perfect for the cool Amihan season. Harvest multiple times throughout the growing period.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Leafy Greens</span>
                                        <span class="plant-tag">Partial Shade</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🫛</div>
                                <div class="plant-info">
                                    <h3>Okra</h3>
                                    <p class="plant-name">Abelmoschus esculentus</p>
                                    <p>
                                        Fast-growing vegetable producing tender pods. Thrives in warm days and cool Amihan nights.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Vegetables</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥕</div>
                                <div class="plant-info">
                                    <h3>Carrots</h3>
                                    <p class="plant-name">Daucus carota</p>
                                    <p>
                                        Sweet root vegetables flourishing in cool weather. Loose, aerobic compost creates perfect growing conditions.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Root Vegetables</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌽</div>
                                <div class="plant-info">
                                    <h3>Sweet Corn</h3>
                                    <p class="plant-name">Zea mays</p>
                                    <p>
                                        Golden ears of corn perfect for the dry season. Benefits from rich, well-aerated compost for strong growth.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Grains</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🧅</div>
                                <div class="plant-info">
                                    <h3>Onions</h3>
                                    <p class="plant-name">Allium cepa</p>
                                    <p>
                                        Cool-season bulb crop ideal for Amihan planting. Develops best flavor with steady aerobic compost nutrition.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Bulb Vegetables</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tag-init Season (Hot & Dry: March - May) -->
                    <div class="season-plants" data-season="tag-init">
                        <div class="plants-grid">
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🍅</div>
                                <div class="plant-info">
                                    <h3>Tomatoes</h3>
                                    <p class="plant-name">Solanum lycopersicum</p>
                                    <p>
                                        Heat-loving plants producing abundant fruit. Aerobic compost provides essential nutrients for fruiting.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Fruit Vegetables</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌶️</div>
                                <div class="plant-info">
                                    <h3>Hot Peppers (Sili)</h3>
                                    <p class="plant-name">Capsicum frutescens</p>
                                    <p>
                                        Fiery peppers thriving in hot weather. Well-aerated compost ensures strong, productive plants.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Spicy</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥒</div>
                                <div class="plant-info">
                                    <h3>Bitter Gourd (Ampalaya)</h3>
                                    <p class="plant-name">Momordica charantia</p>
                                    <p>
                                        Hardy vine producing nutritious fruit. Thrives with rich aerobic compost even in intense heat.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Vining Crops</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🍆</div>
                                <div class="plant-info">
                                    <h3>Eggplant (Talong)</h3>
                                    <p class="plant-name">Solanum melongena</p>
                                    <p>
                                        Heat-tolerant crop producing glossy fruits. Benefits from steady feeding with aerobic compost.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Vegetables</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🫘</div>
                                <div class="plant-info">
                                    <h3>Yard Long Beans (Sitaw)</h3>
                                    <p class="plant-name">Vigna unguiculata</p>
                                    <p>
                                        Fast-growing beans perfect for hot season. Light aerobic compost supports vigorous vine growth.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Legumes</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌻</div>
                                <div class="plant-info">
                                    <h3>Sunflowers</h3>
                                    <p class="plant-name">Helianthus annuus</p>
                                    <p>
                                        Sun-loving ornamentals reaching impressive heights. Aerobic compost provides energy for massive blooms.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Ornamental</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tag-ulan Season (Rainy: June - October) -->
                    <div class="season-plants" data-season="tag-ulan">
                        <div class="plants-grid">
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌾</div>
                                <div class="plant-info">
                                    <h3>Rice (Palay)</h3>
                                    <p class="plant-name">Oryza sativa</p>
                                    <p>
                                        Traditional rainy season crop. Benefits from anaerobic conditions and moisture-rich compost.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-water"></i>
                                        <strong>Fertilizer Type:</strong> Anaerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Grains</span>
                                        <span class="plant-tag">Wetland</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥔</div>
                                <div class="plant-info">
                                    <h3>Taro (Gabi)</h3>
                                    <p class="plant-name">Colocasia esculenta</p>
                                    <p>
                                        Root crop thriving in wet conditions. Anaerobic compost mimics natural swamp environments.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-water"></i>
                                        <strong>Fertilizer Type:</strong> Anaerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Root Vegetables</span>
                                        <span class="plant-tag">Partial Shade</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🫛</div>
                                <div class="plant-info">
                                    <h3>Mung Beans (Munggo)</h3>
                                    <p class="plant-name">Vigna radiata</p>
                                    <p>
                                        Quick-growing legume ideal for rainy season. Well-drained aerobic compost prevents root issues.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Legumes</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥒</div>
                                <div class="plant-info">
                                    <h3>Cucumber (Pipino)</h3>
                                    <p class="plant-name">Cucumis sativus</p>
                                    <p>
                                        Moisture-loving vine producing crisp fruits. Aerobic compost with good drainage prevents disease.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Vining Crops</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌿</div>
                                <div class="plant-info">
                                    <h3>Water Spinach (Kangkong)</h3>
                                    <p class="plant-name">Ipomoea aquatica</p>
                                    <p>
                                        Semi-aquatic leafy green perfect for rainy season. Thrives with anaerobic compost in wet conditions.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-water"></i>
                                        <strong>Fertilizer Type:</strong> Anaerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Leafy Greens</span>
                                        <span class="plant-tag">Wetland</span>
                                    </div>
                                </div>
                            </div>

                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🎋</div>
                                <div class="plant-info">
                                    <h3>Lemongrass (Tanglad)</h3>
                                    <p class="plant-name">Cymbopogon citratus</p>
                                    <p>
                                        Aromatic grass thriving in humid conditions. Aerobic compost promotes healthy, fragrant growth.
                                    </p>
                                    <div class="fertilizer-info">
                                        <i class="fas fa-leaf"></i>
                                        <strong>Fertilizer Type:</strong> Aerobic Compost
                                    </div>
                                    <div class="plant-tags">
                                        <span class="plant-tag">Herbs</span>
                                        <span class="plant-tag">Full Sun</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carousel Controls -->
                <div class="carousel-controls">
                    <button class="carousel-btn prev" id="prevSeason">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-btn next" id="nextSeason">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Ready to Start Your Composting Journey?</h2>
            <p>
                Join hundreds of users who are already transforming waste into valuable resources. Get started with WattAWaste today and experience the future of sustainable living.
            </p>
            <a href="login.php" class="btn">
                Login to Your Account
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-about">
                <div class="footer-logo">WattAWaste</div>
                <p class="footer-desc">
                    Transforming organic waste into sustainable resources through intelligent monitoring and automation. Making composting accessible for everyone.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#client">Our Story</a></li>
                    <li><a href="#garden">Garden</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Resources</h4>
                <ul class="footer-links">
                    <li><a href="login.php">Login</a></li>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">Support</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Contact</h4>
                <ul class="footer-links">
                    <li><a href="/cdn-cgi/l/email-protection#51383f373e11263025253026302225347f323e3c"><span class="__cf_email__" data-cfemail="bad3d4dcd5facddbcecedbcddbc9cedf94d9d5d7">[email&#160;protected]</span></a></li>
                    <li><a href="tel:+639212429795">+63 921 242 9795</a></li>
                    <li><a href="#">Quezon City, Philippines</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 WattAWaste. All rights reserved. | Built with 💚 for a sustainable future</p>
        </div>
    </footer>

    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script>
        // ==================== PHILIPPINE SEASONAL CAROUSEL ====================
        
        // Detect current Philippine season based on month
        function getCurrentPhilippineSeason() {
            const month = new Date().getMonth() + 1; // 1-12
            
            if (month >= 11 || month <= 2) {
                return {
                    id: 'amihan',
                    name: 'Amihan (Cool & Dry)',
                    icon: 'fa-wind'
                };
            } else if (month >= 3 && month <= 5) {
                return {
                    id: 'tag-init',
                    name: 'Tag-init (Hot & Dry)',
                    icon: 'fa-sun'
                };
            } else {
                return {
                    id: 'tag-ulan',
                    name: 'Tag-ulan (Rainy)',
                    icon: 'fa-cloud-rain'
                };
            }
        }

        // Initialize season carousel
        function initSeasonCarousel() {
            const currentSeason = getCurrentPhilippineSeason();
            const seasonBadge = document.getElementById('currentSeasonBadge');
            const seasonText = document.getElementById('currentSeasonText');
            
            // Update current season badge
            if (seasonBadge && seasonText) {
                seasonBadge.querySelector('i').className = `fas ${currentSeason.icon}`;
                seasonText.textContent = `Current Season: ${currentSeason.name}`;
            }

            // Set initial active season
            showSeason(currentSeason.id);

            // Season navigation buttons
            const seasonNavButtons = document.querySelectorAll('.season-nav-btn');
            seasonNavButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const season = this.getAttribute('data-season');
                    showSeason(season);
                    
                    // Update active state
                    seasonNavButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Carousel prev/next buttons
            const prevBtn = document.getElementById('prevSeason');
            const nextBtn = document.getElementById('nextSeason');
            const seasons = ['amihan', 'tag-init', 'tag-ulan'];
            let currentIndex = seasons.indexOf(currentSeason.id);

            if (prevBtn && nextBtn) {
                prevBtn.addEventListener('click', () => {
                    currentIndex = (currentIndex - 1 + seasons.length) % seasons.length;
                    const newSeason = seasons[currentIndex];
                    showSeason(newSeason);
                    updateActiveNavButton(newSeason);
                });

                nextBtn.addEventListener('click', () => {
                    currentIndex = (currentIndex + 1) % seasons.length;
                    const newSeason = seasons[currentIndex];
                    showSeason(newSeason);
                    updateActiveNavButton(newSeason);
                });
            }
        }

        function showSeason(seasonId) {
            const seasonPlants = document.querySelectorAll('.season-plants');
            seasonPlants.forEach(sp => {
                if (sp.getAttribute('data-season') === seasonId) {
                    sp.classList.add('active');
                } else {
                    sp.classList.remove('active');
                }
            });
        }

        function updateActiveNavButton(seasonId) {
            const navButtons = document.querySelectorAll('.season-nav-btn');
            navButtons.forEach(btn => {
                if (btn.getAttribute('data-season') === seasonId) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            initSeasonCarousel();
        });

        // ==================== EXISTING SCRIPTS ====================
        
        // Mobile Menu Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const navMenu = document.getElementById('navMenu');

        mobileMenuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileMenuToggle.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                const icon = mobileMenuToggle.querySelector('i');
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            });
        });

        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Animate on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.animation = 'fadeInUp 0.8s ease forwards';
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = ta