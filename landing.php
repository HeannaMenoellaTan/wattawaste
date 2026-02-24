<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leafcycle - Smart Composting Solution</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --brand-primary: #23ed99ff;
            --brand-secondary: #0f8156ff;
            --brand-dark: #0a5d40;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-light: #f9fafb;
            --card-bg: #ffffff;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
            background: var(--bg-light);
        }

        /* ── Navbar ── */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .navbar.scrolled { box-shadow: 0 4px 30px rgba(0,0,0,0.12); }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            font-size: clamp(22px, 4vw, 28px);
            font-weight: 800;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .nav-logo i { color: var(--brand-secondary); -webkit-text-fill-color: var(--brand-secondary); }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 32px;
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
            bottom: 0; left: 0;
            width: 0; height: 2px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            transition: width 0.3s ease;
        }
        .nav-menu li a:hover::after { width: 100%; }

        .nav-login-btn {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: white !important;
            padding: 10px 24px !important;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(35,237,153,0.3);
        }
        .nav-login-btn::after { display: none !important; }
        .nav-login-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(35,237,153,0.4); }

        /* Hamburger */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: 2px solid var(--brand-secondary);
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 18px;
            color: var(--brand-secondary);
            cursor: pointer;
            transition: all 0.2s;
            z-index: 1001;
        }
        .mobile-menu-toggle:hover { background: var(--brand-secondary); color: white; }

        /* ── Hero ── */
        .hero {
            margin-top: 70px;
            min-height: 90vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(35,237,153,0.05), rgba(15,129,86,0.05));
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 700px; height: 700px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            opacity: 0.08;
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }
        @keyframes float {
            0%, 100% { transform: translate(0,0) rotate(0deg); }
            50% { transform: translate(-50px,50px) rotate(180deg); }
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: clamp(40px, 8vw, 80px) clamp(20px, 5vw, 40px);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: clamp(40px, 8vw, 80px);
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: clamp(30px, 5vw, 56px);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
        }
        .gradient-text {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-content p {
            font-size: clamp(15px, 2vw, 18px);
            line-height: 1.8;
            color: var(--text-muted);
            margin-bottom: 36px;
        }
        .hero-cta { display: flex; gap: 16px; flex-wrap: wrap; }

        .btn {
            padding: clamp(12px, 2vw, 16px) clamp(24px, 4vw, 36px);
            border-radius: 50px;
            font-weight: 600;
            font-size: clamp(14px, 2vw, 16px);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(35,237,153,0.3);
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 6px 25px rgba(35,237,153,0.4); }
        .btn-secondary {
            background: white;
            color: var(--brand-secondary);
            border: 2px solid var(--brand-secondary);
        }
        .btn-secondary:hover { background: var(--brand-secondary); color: white; }

        .hero-image img {
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            animation: heroFloat 6s infinite ease-in-out;
        }
        @keyframes heroFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* ── Sections Common ── */
        .section-container { max-width: 1400px; margin: 0 auto; }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-badge {
            display: inline-block;
            padding: 7px 18px;
            background: linear-gradient(135deg, rgba(35,237,153,0.1), rgba(15,129,86,0.1));
            color: var(--brand-secondary);
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 14px;
        }
        .section-header h2 {
            font-size: clamp(26px, 4vw, 42px);
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 14px;
        }
        .section-header p {
            font-size: clamp(14px, 2vw, 18px);
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* ── About/Features ── */
        .about-section {
            padding: clamp(60px, 10vw, 100px) clamp(20px, 5vw, 40px);
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr));
            gap: 30px;
        }
        .feature-card {
            background: var(--bg-light);
            padding: clamp(24px, 4vw, 40px) clamp(20px, 3vw, 30px);
            border-radius: 20px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: var(--brand-primary);
            box-shadow: 0 20px 40px rgba(35,237,153,0.15);
        }
        .feature-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(35,237,153,0.3);
        }
        .feature-card h3 { font-size: clamp(18px, 2.5vw, 22px); font-weight: 700; margin-bottom: 10px; }
        .feature-card p { font-size: 14px; line-height: 1.7; color: var(--text-muted); }

        /* ── Client Section ── */
        .client-section {
            padding: clamp(60px, 10vw, 100px) clamp(20px, 5vw, 40px);
            background: linear-gradient(135deg, rgba(35,237,153,0.05), rgba(15,129,86,0.05));
        }
        .client-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: clamp(40px, 8vw, 60px);
            align-items: center;
        }
        .client-text h2 {
            font-size: clamp(26px, 4vw, 42px);
            font-weight: 800;
            margin-bottom: 20px;
        }
        .client-text p { font-size: clamp(14px, 1.8vw, 16px); line-height: 1.9; color: var(--text-muted); margin-bottom: 16px; }

        .client-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 36px;
        }
        .stat-card {
            background: white;
            padding: clamp(20px, 3vw, 30px);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .stat-number {
            font-size: clamp(26px, 4vw, 36px);
            font-weight: 800;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
        }
        .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }

        .client-image img {
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        /* ── Garden Section ── */
        .garden-section {
            padding: clamp(60px, 10vw, 100px) clamp(20px, 5vw, 40px);
            background: white;
        }

        .current-season-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: white;
            border-radius: 50px;
            font-weight: 700;
            font-size: clamp(13px, 2vw, 16px);
            margin-bottom: 28px;
            box-shadow: 0 6px 20px rgba(35,237,153,0.3);
            animation: pulseBadge 2s infinite;
        }
        @keyframes pulseBadge { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }

        .season-nav {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .season-nav-btn {
            padding: clamp(14px, 2.5vw, 20px) clamp(16px, 3vw, 30px);
            background: var(--bg-light);
            border: 3px solid transparent;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            min-width: clamp(120px, 20vw, 200px);
            flex: 1;
            max-width: 220px;
        }
        .season-nav-btn i { font-size: clamp(22px, 4vw, 32px); color: var(--brand-secondary); }
        .season-nav-btn span { font-size: clamp(14px, 2.2vw, 18px); font-weight: 700; color: var(--text-dark); }
        .season-nav-btn small { font-size: clamp(11px, 1.5vw, 13px); color: var(--text-muted); font-weight: 500; text-align: center; }
        .season-nav-btn:hover { transform: translateY(-5px); border-color: var(--brand-primary); box-shadow: 0 10px 25px rgba(35,237,153,0.2); }
        .season-nav-btn.active {
            background: linear-gradient(135deg, rgba(35,237,153,0.1), rgba(15,129,86,0.1));
            border-color: var(--brand-secondary);
            box-shadow: 0 8px 20px rgba(35,237,153,0.25);
        }
        .season-nav-btn.active span { color: var(--brand-secondary); }

        .season-plants { display: none; animation: fadeIn 0.6s ease; }
        .season-plants.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .plants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(260px, 100%), 1fr));
            gap: 28px;
        }
        .plant-card {
            background: var(--bg-light);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .plant-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }

        .plant-image {
            width: 100%;
            height: clamp(160px, 25vw, 250px);
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(50px, 10vw, 80px);
            position: relative;
            overflow: hidden;
        }
        .plant-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle, transparent 30%, rgba(0,0,0,0.1));
        }

        .plant-info { padding: clamp(16px, 3vw, 24px); }
        .plant-info h3 { font-size: clamp(16px, 2.5vw, 20px); font-weight: 700; margin-bottom: 6px; }
        .plant-info .plant-name { font-size: 13px; color: var(--text-muted); font-style: italic; margin-bottom: 10px; }
        .plant-info p { font-size: 13px; line-height: 1.7; color: var(--text-muted); margin-bottom: 14px; }

        .fertilizer-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: white;
            border-radius: 10px;
            margin-bottom: 14px;
            border-left: 4px solid var(--brand-secondary);
            font-size: 13px;
        }
        .fertilizer-info i { font-size: 16px; color: var(--brand-secondary); }
        .fertilizer-info i.fa-water { color: #3b82f6; }
        .fertilizer-info strong { font-weight: 700; margin-right: 3px; }

        .plant-tags { display: flex; gap: 8px; flex-wrap: wrap; }
        .plant-tag {
            padding: 5px 12px;
            background: white;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: var(--brand-secondary);
        }

        .carousel-controls { display: flex; justify-content: center; gap: 16px; margin-top: 40px; }
        .carousel-btn {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(35,237,153,0.3);
        }
        .carousel-btn:hover { transform: translateY(-3px) scale(1.1); box-shadow: 0 6px 25px rgba(35,237,153,0.4); }
        .carousel-btn:active { transform: scale(1.05); }

        /* ── CTA Section ── */
        .cta-section {
            padding: clamp(60px, 10vw, 100px) clamp(20px, 5vw, 40px);
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cta-section::before, .cta-section::after {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .cta-section::before { top: -50%; left: -10%; }
        .cta-section::after { bottom: -50%; right: -10%; }

        .cta-content { max-width: 800px; margin: 0 auto; position: relative; z-index: 1; }
        .cta-content h2 { font-size: clamp(26px, 5vw, 48px); font-weight: 800; color: white; margin-bottom: 20px; }
        .cta-content p { font-size: clamp(14px, 2vw, 18px); color: rgba(255,255,255,0.9); margin-bottom: 36px; line-height: 1.8; }
        .cta-content .btn {
            background: white;
            color: var(--brand-secondary);
            font-size: clamp(14px, 2vw, 18px);
            padding: clamp(14px, 2.5vw, 18px) clamp(28px, 5vw, 40px);
        }
        .cta-content .btn:hover { background: var(--bg-light); transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }

        /* ── Footer ── */
        .footer { background: var(--text-dark); color: white; padding: clamp(40px, 8vw, 60px) clamp(20px, 5vw, 40px) 30px; }
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-logo {
            font-size: 26px; font-weight: 800; margin-bottom: 14px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .footer-desc { font-size: 14px; line-height: 1.8; color: rgba(255,255,255,0.7); margin-bottom: 18px; }
        .footer-social { display: flex; gap: 10px; flex-wrap: wrap; }
        .social-icon {
            width: 38px; height: 38px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; text-decoration: none;
            transition: all 0.3s ease;
        }
        .social-icon:hover {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            transform: translateY(-3px);
        }
        .footer-section h4 { font-size: 15px; font-weight: 700; margin-bottom: 18px; }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .footer-links a:hover { color: var(--brand-primary); padding-left: 5px; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 24px;
            text-align: center;
            font-size: 13px;
            color: rgba(255,255,255,0.6);
        }

        /* ── Scroll animation ── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-on-scroll { opacity: 0; }

        /* ══════════════════════════════════════════
           RESPONSIVE BREAKPOINTS
        ══════════════════════════════════════════ */

        /* Tablet (≤1024px) */
        @media (max-width: 1024px) {
            .hero-container,
            .client-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .hero-image { max-width: 500px; margin: 0 auto; width: 100%; }
            .footer-container { grid-template-columns: 1fr 1fr; gap: 36px; }
        }

        /* Mobile (≤768px) */
        @media (max-width: 768px) {
            /* Nav */
            .nav-container { padding: 14px 20px; }
            .mobile-menu-toggle { display: block; }

            .nav-menu {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(255,255,255,0.98);
                backdrop-filter: blur(16px);
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 28px;
                transform: translateX(100%);
                transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 999;
                padding: 40px 20px;
            }
            .nav-menu.active { transform: translateX(0); }
            .nav-menu li a { font-size: 20px; }
            .nav-login-btn { padding: 14px 32px !important; font-size: 18px !important; }

            /* Hero */
            .hero { margin-top: 62px; min-height: auto; padding: 20px 0; }
            .hero-container { grid-template-columns: 1fr; gap: 32px; text-align: center; }
            .hero-cta { justify-content: center; }
            .hero-image { max-width: 340px; width: 90%; margin: 0 auto; }

            /* Features */
            .features-grid { grid-template-columns: 1fr; gap: 20px; }

            /* Client */
            .client-content { grid-template-columns: 1fr; }
            .client-image { max-width: 400px; margin: 0 auto; width: 100%; }
            .client-stats { grid-template-columns: 1fr 1fr; gap: 14px; }

            /* Garden */
            .season-nav { gap: 10px; }
            .season-nav-btn { min-width: unset; max-width: unset; flex: 1 1 calc(33% - 10px); padding: 12px 8px; }
            .season-nav-btn small { display: none; } /* hide dates on mobile to save space */
            .plants-grid { grid-template-columns: 1fr; gap: 20px; }

            /* Footer */
            .footer-container { grid-template-columns: 1fr; gap: 32px; }
            .footer-social { justify-content: flex-start; }
        }

        /* Small mobile (≤480px) */
        @media (max-width: 480px) {
            .hero-content h1 { font-size: 26px; }
            .btn { padding: 12px 20px; font-size: 14px; }
            .client-stats { grid-template-columns: 1fr 1fr; }
            .stat-card { padding: 16px; }
            .stat-number { font-size: 24px; }
            .season-nav-btn span { font-size: 12px; }
            .season-nav-btn i { font-size: 20px; }
            .cta-content h2 { font-size: 22px; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a class="nav-logo" href="#home">
                <i class="fas fa-leaf"></i>
                Leafcycle
            </a>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#client">Our Story</a></li>
                <li><a href="#garden">Garden</a></li>
                <li><a href="login.html" class="nav-login-btn">Login</a></li>
            </ul>

            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <h1>
                    Transform Waste Into <span class="gradient-text">Sustainable Growth</span>
                </h1>
                <p>
                    Leafcycle is an intelligent composting solution that monitors and optimizes your organic waste transformation into nutrient-rich fertilizer. Join us in creating a greener, more sustainable future.
                </p>
                <div class="hero-cta">
                    <a href="login.html" class="btn btn-primary">
                        Get Started <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#about" class="btn btn-secondary">
                        Learn More <i class="fas fa-info-circle"></i>
                    </a>
                </div>
            </div>

            <div class="hero-image">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 500 500'%3E%3Cdefs%3E%3ClinearGradient id='g1' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%2323ed99'/%3E%3Cstop offset='100%25' style='stop-color:%230f8156'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23g1)' width='500' height='500' rx='30'/%3E%3Cg fill='white' opacity='0.2'%3E%3Ccircle cx='250' cy='250' r='150'/%3E%3Ccircle cx='150' cy='150' r='80'/%3E%3Ccircle cx='350' cy='350' r='100'/%3E%3C/g%3E%3Ctext x='250' y='270' font-family='Arial' font-size='120' fill='white' text-anchor='middle' opacity='0.9'%3E🌱%3C/text%3E%3C/svg%3E"
                     alt="Leafcycle Smart Composting">
            </div>
        </div>
    </section>

    <!-- About/Features -->
    <section class="about-section" id="about">
        <div class="section-container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">WHY CHOOSE US</span>
                <h2>Smart Composting Made Simple</h2>
                <p>Our intelligent system monitors temperature, humidity, pH levels, and gas emissions to ensure optimal composting conditions. Get real-time insights and automated controls for perfect compost every time.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-thermometer-half"></i></div>
                    <h3>Real-Time Monitoring</h3>
                    <p>Track temperature, humidity, pH, and gas levels in real-time with our advanced sensor network. Receive instant alerts when conditions need attention.</p>
                </div>
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-cogs"></i></div>
                    <h3>Automated Control</h3>
                    <p>Smart mixer controls automatically optimize your compost. Set it and forget it with our intelligent automation system.</p>
                </div>
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Analytics & Insights</h3>
                    <p>Comprehensive dashboards and historical data visualization help you understand and improve your composting process over time.</p>
                </div>
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-leaf"></i></div>
                    <h3>Eco-Friendly</h3>
                    <p>Reduce waste, lower carbon footprint, and create nutrient-rich fertilizer for your garden. Good for you, great for the planet.</p>
                </div>
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3>Mobile Access</h3>
                    <p>Monitor and control your compost bin from anywhere with our responsive web interface. Check status on any device, anytime.</p>
                </div>
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-bell"></i></div>
                    <h3>Smart Notifications</h3>
                    <p>Get notified when your compost reaches optimal stages or needs attention. Never miss important composting milestones.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Client/Our Story -->
    <section class="client-section" id="client">
        <div class="section-container">
            <div class="client-content">
                <div class="client-text animate-on-scroll">
                    <span class="section-badge">OUR STORY</span>
                    <h2>From Vision to Reality</h2>
                    <p>Leafcycle was born from a simple observation: too much organic waste ends up in landfills when it could nourish our gardens instead. Our team of environmental engineers and tech enthusiasts came together to create a solution that makes composting accessible, efficient, and intelligent.</p>
                    <p>Starting as a university research project, we've grown into a comprehensive platform that helps households and communities transform their organic waste into valuable resources. Our mission is to make sustainable living effortless through technology.</p>
                    <p>Today, Leafcycle serves gardening enthusiasts, eco-conscious families, and urban farmers who want to minimize waste while maximizing their garden's potential.</p>
                    <div class="client-stats">
                        <div class="stat-card"><div class="stat-number">500+</div><div class="stat-label">Active Users</div></div>
                        <div class="stat-card"><div class="stat-number">10K+</div><div class="stat-label">Tons Composted</div></div>
                        <div class="stat-card"><div class="stat-number">95%</div><div class="stat-label">Success Rate</div></div>
                        <div class="stat-card"><div class="stat-number">24/7</div><div class="stat-label">Monitoring</div></div>
                    </div>
                </div>
                <div class="client-image animate-on-scroll">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 600'%3E%3Cdefs%3E%3ClinearGradient id='g2' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%2323ed99;stop-opacity:0.8'/%3E%3Cstop offset='100%25' style='stop-color:%230f8156;stop-opacity:0.8'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23g2)' width='600' height='600' rx='30'/%3E%3Ctext x='300' y='340' font-family='Arial' font-size='150' fill='white' text-anchor='middle' opacity='0.8'%3E🌍%3C/text%3E%3C/svg%3E"
                         alt="Our Story">
                </div>
            </div>
        </div>
    </section>

    <!-- Garden Plants -->
    <section class="garden-section" id="garden">
        <div class="section-container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">OUR GARDEN</span>
                <h2>Plants Thriving with Leafcycle</h2>
                <p>See incredible results from our community members growing healthy plants using Leafcycle compost. From vegetables to ornamentals, our fertilizer supports diverse plant life across all Philippine seasons.</p>
            </div>

            <div class="season-carousel-container">
                <div class="current-season-badge" id="currentSeasonBadge">
                    <i class="fas fa-sun"></i>
                    <span id="currentSeasonText">Loading season...</span>
                </div>

                <div class="season-nav">
                    <button class="season-nav-btn" data-season="amihan">
                        <i class="fas fa-wind"></i>
                        <span>Amihan</span>
                        <small>Cool &amp; Dry (Nov–Feb)</small>
                    </button>
                    <button class="season-nav-btn" data-season="tag-init">
                        <i class="fas fa-sun"></i>
                        <span>Tag-init</span>
                        <small>Hot &amp; Dry (Mar–May)</small>
                    </button>
                    <button class="season-nav-btn" data-season="tag-ulan">
                        <i class="fas fa-cloud-rain"></i>
                        <span>Tag-ulan</span>
                        <small>Rainy (Jun–Oct)</small>
                    </button>
                </div>

                <div class="plants-carousel">
                    <!-- Amihan -->
                    <div class="season-plants" data-season="amihan">
                        <div class="plants-grid">
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥜</div>
                                <div class="plant-info">
                                    <h3>Peanuts (Mani)</h3>
                                    <p class="plant-name">Arachis hypogaea</p>
                                    <p>Protein-rich legumes thriving in cool, dry conditions. Excellent for nitrogen fixation and soil improvement.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Legumes</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥬</div>
                                <div class="plant-info">
                                    <h3>Lettuce</h3>
                                    <p class="plant-name">Lactuca sativa</p>
                                    <p>Crisp, fresh lettuce perfect for the cool Amihan season. Harvest multiple times throughout the growing period.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Leafy Greens</span><span class="plant-tag">Partial Shade</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🫛</div>
                                <div class="plant-info">
                                    <h3>Okra</h3>
                                    <p class="plant-name">Abelmoschus esculentus</p>
                                    <p>Fast-growing vegetable producing tender pods. Thrives in warm days and cool Amihan nights.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Vegetables</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥕</div>
                                <div class="plant-info">
                                    <h3>Carrots</h3>
                                    <p class="plant-name">Daucus carota</p>
                                    <p>Sweet root vegetables flourishing in cool weather. Loose, aerobic compost creates perfect growing conditions.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Root Vegetables</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌽</div>
                                <div class="plant-info">
                                    <h3>Sweet Corn</h3>
                                    <p class="plant-name">Zea mays</p>
                                    <p>Golden ears of corn perfect for the dry season. Benefits from rich, well-aerated compost for strong growth.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Grains</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🧅</div>
                                <div class="plant-info">
                                    <h3>Onions</h3>
                                    <p class="plant-name">Allium cepa</p>
                                    <p>Cool-season bulb crop ideal for Amihan planting. Develops best flavor with steady aerobic compost nutrition.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Bulb Vegetables</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tag-init -->
                    <div class="season-plants" data-season="tag-init">
                        <div class="plants-grid">
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🍅</div>
                                <div class="plant-info">
                                    <h3>Tomatoes</h3>
                                    <p class="plant-name">Solanum lycopersicum</p>
                                    <p>Heat-loving plants producing abundant fruit. Aerobic compost provides essential nutrients for fruiting.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Fruit Vegetables</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌶️</div>
                                <div class="plant-info">
                                    <h3>Hot Peppers (Sili)</h3>
                                    <p class="plant-name">Capsicum frutescens</p>
                                    <p>Fiery peppers thriving in hot weather. Well-aerated compost ensures strong, productive plants.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Spicy</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥒</div>
                                <div class="plant-info">
                                    <h3>Bitter Gourd (Ampalaya)</h3>
                                    <p class="plant-name">Momordica charantia</p>
                                    <p>Hardy vine producing nutritious fruit. Thrives with rich aerobic compost even in intense heat.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Vining Crops</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🍆</div>
                                <div class="plant-info">
                                    <h3>Eggplant (Talong)</h3>
                                    <p class="plant-name">Solanum melongena</p>
                                    <p>Heat-tolerant crop producing glossy fruits. Benefits from steady feeding with aerobic compost.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Vegetables</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🫘</div>
                                <div class="plant-info">
                                    <h3>Yard Long Beans (Sitaw)</h3>
                                    <p class="plant-name">Vigna unguiculata</p>
                                    <p>Fast-growing beans perfect for hot season. Light aerobic compost supports vigorous vine growth.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Legumes</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌻</div>
                                <div class="plant-info">
                                    <h3>Sunflowers</h3>
                                    <p class="plant-name">Helianthus annuus</p>
                                    <p>Sun-loving ornamentals reaching impressive heights. Aerobic compost provides energy for massive blooms.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Ornamental</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tag-ulan -->
                    <div class="season-plants" data-season="tag-ulan">
                        <div class="plants-grid">
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌾</div>
                                <div class="plant-info">
                                    <h3>Rice (Palay)</h3>
                                    <p class="plant-name">Oryza sativa</p>
                                    <p>Traditional rainy season crop. Benefits from anaerobic conditions and moisture-rich compost.</p>
                                    <div class="fertilizer-info"><i class="fas fa-water"></i><strong>Fertilizer Type:</strong> Anaerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Grains</span><span class="plant-tag">Wetland</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥔</div>
                                <div class="plant-info">
                                    <h3>Taro (Gabi)</h3>
                                    <p class="plant-name">Colocasia esculenta</p>
                                    <p>Root crop thriving in wet conditions. Anaerobic compost mimics natural swamp environments.</p>
                                    <div class="fertilizer-info"><i class="fas fa-water"></i><strong>Fertilizer Type:</strong> Anaerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Root Vegetables</span><span class="plant-tag">Partial Shade</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🫛</div>
                                <div class="plant-info">
                                    <h3>Mung Beans (Munggo)</h3>
                                    <p class="plant-name">Vigna radiata</p>
                                    <p>Quick-growing legume ideal for rainy season. Well-drained aerobic compost prevents root issues.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Legumes</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🥒</div>
                                <div class="plant-info">
                                    <h3>Cucumber (Pipino)</h3>
                                    <p class="plant-name">Cucumis sativus</p>
                                    <p>Moisture-loving vine producing crisp fruits. Aerobic compost with good drainage prevents disease.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Vining Crops</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🌿</div>
                                <div class="plant-info">
                                    <h3>Water Spinach (Kangkong)</h3>
                                    <p class="plant-name">Ipomoea aquatica</p>
                                    <p>Semi-aquatic leafy green perfect for rainy season. Thrives with anaerobic compost in wet conditions.</p>
                                    <div class="fertilizer-info"><i class="fas fa-water"></i><strong>Fertilizer Type:</strong> Anaerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Leafy Greens</span><span class="plant-tag">Wetland</span></div>
                                </div>
                            </div>
                            <div class="plant-card animate-on-scroll">
                                <div class="plant-image">🎋</div>
                                <div class="plant-info">
                                    <h3>Lemongrass (Tanglad)</h3>
                                    <p class="plant-name">Cymbopogon citratus</p>
                                    <p>Aromatic grass thriving in humid conditions. Aerobic compost promotes healthy, fragrant growth.</p>
                                    <div class="fertilizer-info"><i class="fas fa-leaf"></i><strong>Fertilizer Type:</strong> Aerobic Compost</div>
                                    <div class="plant-tags"><span class="plant-tag">Herbs</span><span class="plant-tag">Full Sun</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="carousel-controls">
                    <button class="carousel-btn" id="prevSeason" aria-label="Previous season"><i class="fas fa-chevron-left"></i></button>
                    <button class="carousel-btn" id="nextSeason" aria-label="Next season"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Ready to Start Your Composting Journey?</h2>
            <p>Join hundreds of users already transforming waste into valuable resources. Get started with Leafcycle today.</p>
            <a href="login.html" class="btn">Login to Your Account <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-about">
                <div class="footer-logo">Leafcycle</div>
                <p class="footer-desc">Transforming organic waste into sustainable resources through intelligent monitoring and automation. Making composting accessible for everyone.</p>
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
                    <li><a href="login.html">Login</a></li>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">Support</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contact</h4>
                <ul class="footer-links">
                    <li><a href="mailto:info@leafcycle.com">info@leafcycle.com</a></li>
                    <li><a href="tel:+639212429795">+63 921 242 9795</a></li>
                    <li><a href="#">Quezon City, Philippines</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Leafcycle. All rights reserved. | Built with 💚 for a sustainable future</p>
        </div>
    </footer>

    <script>
    // ── Philippine Season Detection ──
    function getCurrentPhilippineSeason() {
        const month = new Date().getMonth() + 1;
        if (month >= 11 || month <= 2) return { id: 'amihan',   name: 'Amihan (Cool & Dry)',  icon: 'fa-wind' };
        if (month >= 3  && month <= 5) return { id: 'tag-init', name: 'Tag-init (Hot & Dry)', icon: 'fa-sun' };
        return { id: 'tag-ulan', name: 'Tag-ulan (Rainy)', icon: 'fa-cloud-rain' };
    }

    const seasons = ['amihan', 'tag-init', 'tag-ulan'];
    let currentIndex = 0;

    function showSeason(seasonId) {
        document.querySelectorAll('.season-plants').forEach(sp => {
            sp.classList.toggle('active', sp.getAttribute('data-season') === seasonId);
        });
        document.querySelectorAll('.season-nav-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-season') === seasonId);
        });
        currentIndex = seasons.indexOf(seasonId);
    }

    function initSeasonCarousel() {
        const currentSeason = getCurrentPhilippineSeason();
        const badge = document.getElementById('currentSeasonBadge');
        const text  = document.getElementById('currentSeasonText');
        if (badge && text) {
            badge.querySelector('i').className = `fas ${currentSeason.icon}`;
            text.textContent = `Current Season: ${currentSeason.name}`;
        }
        showSeason(currentSeason.id);

        document.querySelectorAll('.season-nav-btn').forEach(btn => {
            btn.addEventListener('click', () => showSeason(btn.getAttribute('data-season')));
        });

        document.getElementById('prevSeason').addEventListener('click', () => {
            showSeason(seasons[(currentIndex - 1 + seasons.length) % seasons.length]);
        });
        document.getElementById('nextSeason').addEventListener('click', () => {
            showSeason(seasons[(currentIndex + 1) % seasons.length]);
        });
    }

    // ── Mobile Menu ──
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');

    mobileMenuToggle.addEventListener('click', () => {
        const isOpen = navMenu.classList.toggle('active');
        mobileMenuToggle.querySelector('i').className = isOpen ? 'fas fa-times' : 'fas fa-bars';
        document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
            document.body.style.overflow = '';
        });
    });

    // ── Navbar Scroll Effect ──
    window.addEventListener('scroll', () => {
        document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
    });

    // ── Scroll Animations ──
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.animation = 'fadeInUp 0.8s ease forwards';
                }, i * 80);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });

    document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));

    // ── Smooth Scroll ──
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const offset = document.querySelector('.navbar').offsetHeight + 10;
                window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });
            }
        });
    });

    // ── Init ──
    document.addEventListener('DOMContentLoaded', initSeasonCarousel);
    </script>
</body>
</html>