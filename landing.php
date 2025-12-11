<?php
// landing.php - Landing page for Efficient Trash Management System
// Place your UKM logo at: assets/ukmlogo.png (recommended size: 300x300 or similar, transparent PNG)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Efficient Trash Management System — Our College, Our Home Cannot Login</title>
  <meta name="description" content="Efficient Trash Management System for Kolej Kediaman UKM — Smart scheduling, easy reporting, cleaner campus.">

  <!-- Font & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --bg: #f6fff7;                /* soft pastel mint */
      --card: #ffffff;
      --text: #1f2d1f;
      --muted: #587165;
      --accent: #7fc49b;            /* pastel green accent */
      --accent-2: #a8d9b8;          /* lighter */
      --accent-dark: #5fa87e;
      --glass: rgba(255,255,255,0.85);
      --radius: 16px;
      --radius-lg: 24px;
      --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
      --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
      --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }

    section {
      padding: 100px 0;
      position: relative;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Navigation */
    .nav {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      padding: 20px 0;
      z-index: 1000;
      background: var(--glass);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(127, 196, 155, 0.1);
      transition: var(--transition);
    }

    .nav.scrolled {
      padding: 15px 0;
      box-shadow: 0 5px 20px rgba(46, 64, 43, 0.05);
    }

    .nav-inner {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 15px;
      text-decoration: none;
      color: var(--text);
      transition: var(--transition);
    }

    .brand:hover {
      transform: translateY(-2px);
    }

    .brand img {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      object-fit: contain;
      background: white;
      padding: 5px;
      box-shadow: var(--shadow-light);
    }

    .brand-text h1 {
      font-size: 18px;
      font-weight: 700;
      margin: 0;
    }

    .brand-text p {
      font-size: 12px;
      color: var(--muted);
      margin: 0;
    }

    .nav-actions {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .btn {
      padding: 12px 24px;
      border-radius: 12px;
      border: none;
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: inline-block;
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--accent);
      color: var(--accent);
    }

    .btn-outline:hover {
      background: rgba(127, 196, 155, 0.08);
      transform: translateY(-3px);
      box-shadow: var(--shadow-light);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent), var(--accent-dark));
      color: white;
      box-shadow: 0 8px 25px rgba(124, 196, 153, 0.25);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(124, 196, 153, 0.35);
    }

    /* Hero Section */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding-top: 120px;
      position: relative;
      overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: -200px;
      right: -200px;
      width: 600px;
      height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(127, 196, 155, 0.08), transparent 70%);
      z-index: -1;
    }

    .hero-content {
      max-width: 700px;
    }

    .hero-title {
      font-size: 3.5rem;
      line-height: 1.1;
      margin-bottom: 20px;
      font-weight: 700;
    }

    .hero-subtitle {
      font-size: 1.25rem;
      color: var(--muted);
      margin-bottom: 40px;
      max-width: 600px;
    }

    .hero-stats {
      display: flex;
      gap: 40px;
      margin-top: 60px;
    }

    .stat {
      text-align: center;
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--accent);
      line-height: 1;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 0.9rem;
      color: var(--muted);
      font-weight: 500;
    }

    /* Features Section */
    .section-title {
      text-align: center;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .section-subtitle {
      text-align: center;
      color: var(--muted);
      max-width: 700px;
      margin: 0 auto 60px;
      font-size: 1.1rem;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
    }

    .feature-card {
      background: var(--card);
      border-radius: var(--radius);
      padding: 40px 30px;
      box-shadow: var(--shadow);
      border: 1px solid rgba(160, 200, 170, 0.1);
      transition: var(--transition);
      text-align: center;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 50px rgba(46, 64, 43, 0.12);
    }

    .feature-icon {
      width: 70px;
      height: 70px;
      border-radius: 20px;
      background: linear-gradient(135deg, rgba(127, 196, 155, 0.15), rgba(168, 217, 184, 0.05));
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
      font-size: 28px;
      color: var(--accent);
    }

    .feature-card h3 {
      font-size: 1.5rem;
      margin-bottom: 15px;
    }

    .feature-card p {
      color: var(--muted);
      font-size: 1rem;
    }

    /* How It Works Section */
    .how-it-works {
      background: linear-gradient(180deg, rgba(168, 217, 184, 0.05), rgba(127, 196, 155, 0.02));
      border-radius: var(--radius-lg);
      position: relative;
      overflow: hidden;
    }

    .how-it-works::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%237fc49b' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
      z-index: -1;
    }

    .steps-container {
      display: flex;
      flex-direction: column;
      gap: 60px;
      max-width: 800px;
      margin: 0 auto;
      position: relative;
    }

    .steps-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 40px;
      width: 4px;
      height: 100%;
      background: linear-gradient(to bottom, var(--accent-2), var(--accent));
      border-radius: 2px;
    }

    .step {
      display: flex;
      gap: 40px;
      position: relative;
    }

    .step-number {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: var(--card);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      font-weight: 700;
      color: var(--accent);
      box-shadow: var(--shadow);
      flex-shrink: 0;
      z-index: 1;
    }

    .step-content {
      background: var(--card);
      border-radius: var(--radius);
      padding: 30px;
      box-shadow: var(--shadow);
      flex-grow: 1;
    }

    .step-content h3 {
      font-size: 1.5rem;
      margin-bottom: 15px;
      color: var(--text);
    }

    .step-content p {
      color: var(--muted);
      font-size: 1rem;
    }

    /* Call to Action */
    .cta-section {
      text-align: center;
    }

    .cta-card {
      background: linear-gradient(135deg, var(--accent), var(--accent-dark));
      border-radius: var(--radius-lg);
      padding: 80px 40px;
      color: white;
      box-shadow: 0 25px 60px rgba(124, 196, 153, 0.3);
      max-width: 900px;
      margin: 0 auto;
    }

    .cta-title {
      font-size: 2.8rem;
      margin-bottom: 20px;
      font-weight: 700;
    }

    .cta-subtitle {
      font-size: 1.2rem;
      margin-bottom: 40px;
      opacity: 0.9;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }

    .cta-buttons {
      display: flex;
      gap: 20px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn-white {
      background: white;
      color: var(--accent-dark);
    }

    .btn-white:hover {
      background: rgba(255, 255, 255, 0.9);
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(255, 255, 255, 0.2);
    }

    .btn-transparent {
      background: transparent;
      border: 2px solid white;
      color: white;
    }

    .btn-transparent:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-3px);
    }

    /* Footer */
    footer {
      background: rgba(31, 45, 31, 0.95);
      color: white;
      padding: 60px 0 30px;
      margin-top: 100px;
    }

    .footer-content {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 40px;
      margin-bottom: 40px;
    }

    .footer-logo {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }

    .footer-logo img {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      object-fit: contain;
      background: white;
      padding: 5px;
    }

    .footer-logo-text h3 {
      font-size: 20px;
      margin-bottom: 5px;
    }

    .footer-logo-text p {
      font-size: 14px;
      opacity: 0.8;
    }

    .footer-links {
      display: flex;
      gap: 40px;
      flex-wrap: wrap;
    }

    .footer-column h4 {
      font-size: 18px;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .footer-column ul {
      list-style: none;
    }

    .footer-column ul li {
      margin-bottom: 10px;
    }

    .footer-column ul li a {
      color: rgba(255, 255, 255, 0.7);
      text-decoration: none;
      transition: var(--transition);
    }

    .footer-column ul li a:hover {
      color: white;
      padding-left: 5px;
    }

    .footer-bottom {
      text-align: center;
      padding-top: 30px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 14px;
      opacity: 0.7;
    }

    /* Animations */
    .fade-up {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.8s ease, transform 0.8s ease;
    }

    .fade-up.in-view {
      opacity: 1;
      transform: translateY(0);
    }

    .stagger-delay-1 { transition-delay: 0.1s; }
    .stagger-delay-2 { transition-delay: 0.2s; }
    .stagger-delay-3 { transition-delay: 0.3s; }
    .stagger-delay-4 { transition-delay: 0.4s; }
    .stagger-delay-5 { transition-delay: 0.5s; }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .hero-title {
        font-size: 2.8rem;
      }
      
      .features-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.2rem;
      }
      
      .hero-subtitle {
        font-size: 1.1rem;
      }
      
      .features-grid {
        grid-template-columns: 1fr;
      }
      
      .step {
        flex-direction: column;
        gap: 20px;
      }
      
      .steps-container::before {
        left: 50%;
        transform: translateX(-50%);
      }
      
      .step-number {
        align-self: center;
      }
      
      .nav-actions {
        display: none;
      }
      
      .hero-stats {
        flex-direction: column;
        gap: 30px;
      }
      
      .cta-title {
        font-size: 2rem;
      }
      
      .footer-content {
        flex-direction: column;
      }
    }

    @media (max-width: 480px) {
      .hero-title {
        font-size: 1.8rem;
      }
      
      .section-title {
        font-size: 1.8rem;
      }
      
      .cta-card {
        padding: 50px 20px;
      }
      
      .cta-title {
        font-size: 1.6rem;
      }
      
      .btn {
        padding: 10px 20px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="nav" id="navbar">
    <div class="nav-inner">
      <a class="brand" href="#">
        <!-- Replace the src below with your UKM logo file: assets/ukmlogo.png -->
        <img src="assets/ukmlogo.png" alt="UKM logo" onerror="this.style.opacity=0.5;this.title='Place assets/ukmlogo.png'">
        <div class="brand-text">
          <h1>Efficient Trash Management</h1>
          <p>Our College, Our Home</p>
        </div>
      </a>
      
      <div class="nav-actions">
        <a class="btn btn-outline" href="#features">Features</a>
        <a class="btn btn-outline" href="#how-it-works">How it works</a>
        <a class="btn btn-primary" href="index.php">Log in</a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section id="hero" class="hero">
    <div class="container">
      <div class="hero-content fade-up">
        <h1 class="hero-title">A cleaner campus starts with smarter management</h1>
        <p class="hero-subtitle">Efficient Trash Management System for Kolej Kediaman UKM. Automate scheduling, simplify reporting, and track cleanliness in real-time — creating a healthier environment for everyone.</p>
        
        <div class="hero-buttons">
          <a href="index.php" class="btn btn-primary">Get Started Now</a>
          <a href="#features" class="btn btn-outline">Learn More</a>
        </div>
        
        <div class="hero-stats fade-up stagger-delay-1">
          <div class="stat">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Monitoring</div>
          </div>
          <div class="stat">
            <div class="stat-number">99%</div>
            <div class="stat-label">Satisfaction</div>
          </div>
          <div class="stat">
            <div class="stat-number">50+</div>
            <div class="stat-label">Clean Areas</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="features">
    <div class="container">
      <h2 class="section-title fade-up">Everything you need for a cleaner campus</h2>
      <p class="section-subtitle fade-up stagger-delay-1">Our system streamlines trash management through automation, tracking, and collaboration.</p>
      
      <div class="features-grid">
        <div class="feature-card fade-up stagger-delay-1">
          <div class="feature-icon">
            <i class="fas fa-calendar-check"></i>
          </div>
          <h3>Auto Scheduling</h3>
          <p>Smart algorithms assign cleaning tasks based on location, priority, and staff availability. No more manual assignments or missed areas.</p>
        </div>
        
        <div class="feature-card fade-up stagger-delay-2">
          <div class="feature-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <h3>Cleaner Tracking</h3>
          <p>Real-time location tracking for cleaning staff with completion status. Know exactly which areas have been cleaned and when.</p>
        </div>
        
        <div class="feature-card fade-up stagger-delay-3">
          <div class="feature-icon">
            <i class="fas fa-bullhorn"></i>
          </div>
          <h3>Easy Reporting</h3>
          <p>Students can report issues with photos in seconds. Maintenance teams receive instant notifications for quick resolution.</p>
        </div>
        
        <div class="feature-card fade-up stagger-delay-2">
          <div class="feature-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <h3>Analytics Dashboard</h3>
          <p>Monitor cleanliness trends, staff performance, and problem areas with comprehensive data visualization tools.</p>
        </div>
        
        <div class="feature-card fade-up stagger-delay-3">
          <div class="feature-icon">
            <i class="fas fa-bell"></i>
          </div>
          <h3>Smart Notifications</h3>
          <p>Automated alerts for schedule changes, overdue tasks, and urgent reports keep everyone informed and responsive.</p>
        </div>
        
        <div class="feature-card fade-up stagger-delay-4">
          <div class="feature-icon">
            <i class="fas fa-mobile-alt"></i>
          </div>
          <h3>Mobile-Friendly</h3>
          <p>Access all features on any device. Cleaners can check in, students can report, and supervisors can monitor from anywhere.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section id="how-it-works" class="how-it-works">
    <div class="container">
      <h2 class="section-title fade-up">How it works in 3 simple steps</h2>
      <p class="section-subtitle fade-up stagger-delay-1">From scheduling to resolution, our streamlined process keeps the campus spotless.</p>
      
      <div class="steps-container">
        <div class="step fade-up">
          <div class="step-number">1</div>
          <div class="step-content">
            <h3>Schedule & Assign</h3>
            <p>The system automatically creates optimized cleaning schedules based on historical data, current needs, and staff availability. Tasks are assigned to cleaners with clear instructions and priority levels.</p>
          </div>
        </div>
        
        <div class="step fade-up stagger-delay-1">
          <div class="step-number">2</div>
          <div class="step-content">
            <h3>Execute & Monitor</h3>
            <p>Cleaners check in at assigned locations via mobile app. Supervisors monitor progress in real-time with completion status, photos, and location data. Any deviations trigger immediate notifications.</p>
          </div>
        </div>
        
        <div class="step fade-up stagger-delay-2">
          <div class="step-number">3</div>
          <div class="step-content">
            <h3>Report & Resolve</h3>
            <p>Students and staff report issues with photo evidence. Maintenance teams receive prioritized tickets and resolve problems efficiently. The system tracks resolution times and satisfaction ratings.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Call to Action -->
  <section id="cta" class="cta-section">
    <div class="container">
      <div class="cta-card fade-up">
        <h2 class="cta-title">Ready for a cleaner campus?</h2>
        <p class="cta-subtitle">Join Kolej Kediaman UKM in creating a healthier, more sustainable living environment through efficient trash management.</p>
        
        <div class="cta-buttons">
          <a href="index.php" class="btn btn-white">Login to System</a>
          <a href="#features" class="btn btn-transparent">View Features</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="footer-content">
        <div class="footer-logo-section">
          <div class="footer-logo">
            <img src="assets/ukmlogo.png" alt="UKM Logo">
            <div class="footer-logo-text">
              <h3>Efficient Trash Management</h3>
              <p>Our College, Our Home</p>
            </div>
          </div>
          <p style="max-width: 300px; opacity: 0.8; margin-top: 15px;">Creating a cleaner, healthier campus environment through technology and community collaboration.</p>
        </div>
        
        <div class="footer-links">
          <div class="footer-column">
            <h4>System</h4>
            <ul>
              <li><a href="#features">Features</a></li>
              <li><a href="#how-it-works">How it works</a></li>
              <li><a href="index.php">Login</a></li>
            </ul>
          </div>
          
          <div class="footer-column">
            <h4>Kolej Kediaman</h4>
            <ul>
              <li><a href="#">About UKM</a></li>
              <li><a href="#">Campus Life</a></li>
              <li><a href="#">Sustainability</a></li>
            </ul>
          </div>
          
          <div class="footer-column">
            <h4>Contact</h4>
            <ul>
              <li><a href="#">Maintenance Team</a></li>
              <li><a href="#">Support</a></li>
              <li><a href="#">Feedback</a></li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Efficient Trash Management System — Kolej Kediaman UKM</p>
        <p style="margin-top: 10px;">Made with ♥ for a cleaner, greener campus</p>
      </div>
    </div>
  </footer>

  <script>
    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });

    // Intersection Observer for fade-up animations
    const fadeUpElements = document.querySelectorAll('.fade-up');
    
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
        }
      });
    }, observerOptions);
    
    fadeUpElements.forEach(element => {
      observer.observe(element);
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          window.scrollTo({
            top: targetElement.offsetTop - 80,
            behavior: 'smooth'
          });
        }
      });
    });

    // Initialize on load
    window.addEventListener('DOMContentLoaded', () => {
      // Trigger initial animations
      setTimeout(() => {
        document.querySelectorAll('.fade-up').forEach(el => {
          const rect = el.getBoundingClientRect();
          if (rect.top < window.innerHeight - 100) {
            el.classList.add('in-view');
          }
        });
      }, 300);
    });
  </script>
</body>
</html>