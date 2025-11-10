<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduAssign Resources</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header-container {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        .logo i {
            font-size: 2rem;
            color: #667eea;
        }
        .logo h1 {
            font-size: 1.5rem;
            color: #333;
            font-weight: 700;
        }
        nav {
            display: flex;
            gap: 1.5rem;
        }
        nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 0.55rem 1.1rem;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        nav a:hover,
        nav a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.25);
        }
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 5px;
        }
        .menu-toggle span {
            width: 26px;
            height: 3px;
            background: #333;
            border-radius: 2px;
        }
        main {
            padding: 3rem 1.5rem 4rem;
        }
        .hero {
            max-width: 1100px;
            margin: 0 auto 3rem;
            background: rgba(255, 255, 255, 0.92);
            border-radius: 28px;
            padding: 3rem;
            box-shadow: 0 20px 45px rgba(31, 38, 135, 0.18);
            text-align: center;
        }
        .hero h2 {
            font-size: 2.6rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        .hero p {
            color: #555;
            font-size: 1.1rem;
            max-width: 760px;
            margin: 0 auto 2rem;
            line-height: 1.7;
        }
        .hero .cta {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.9rem 1.9rem;
            border-radius: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.25);
            transition: transform 0.3s ease;
        }
        .hero .cta:hover {
            transform: translateY(-3px);
        }
        .info-container {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }
        .info-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 22px;
            padding: 2.2rem;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 22px 55px rgba(102, 126, 234, 0.25);
        }
        .info-card h3 {
            font-size: 1.7rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .info-card p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .info-card ul {
            padding-left: 1.1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .info-card li {
            margin-bottom: 0.6rem;
        }
        .contact-links p {
            margin-bottom: 0.4rem;
        }
        .contact-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .contact-links a:hover {
            text-decoration: underline;
        }
        footer {
            background: rgba(44, 62, 80, 0.75);
            color: #fff;
            padding: 2.5rem 1.5rem;
            text-align: center;
        }
        footer .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        footer .footer-links a {
            color: #fff;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        footer .footer-links a:hover {
            opacity: 0.75;
        }
        footer p {
            opacity: 0.85;
            font-size: 0.95rem;
        }
        @media (max-width: 820px) {
            nav {
                position: absolute;
                top: 100%;
                right: 0;
                background: rgba(255, 255, 255, 0.98);
                border-radius: 0 0 20px 20px;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
                padding: 1.5rem;
                flex-direction: column;
                gap: 1rem;
                display: none;
                min-width: 220px;
            }
            nav.active {
                display: flex;
            }
            .menu-toggle {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h1>EduAssign</h1>
            </a>
            <nav id="primary-nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="student_login.php"><i class="fas fa-user-graduate"></i> Student Login</a>
                <a href="teacher_login.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Login</a>
                <a href="admin_login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
            </nav>
            <div class="menu-toggle" id="menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <h2>EduAssign Resource Hub</h2>
            <p>Stay informed about how EduAssign protects your data, supports your teams, and keeps your academic operations running smoothly. Explore the sections below for full details.</p>
            <a class="cta" href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </section>

        <section class="info-container">
            <article class="info-card" id="about">
                <h3><i class="fas fa-graduation-cap"></i> About EduAssign</h3>
                <p>EduAssign modernizes assignment management for colleges and universities. The platform keeps students, teachers, and administrators aligned with intuitive dashboards, automated notifications, and real-time progress tracking.</p>
                <ul>
                    <li>Role-specific workspaces that simplify daily academic tasks.</li>
                    <li>Centralized tracking for assignments, grades, and submissions.</li>
                    <li>Analytics that spotlight learning trends and engagement.</li>
                </ul>
                <p>Our mission is to empower academic communities with technology that supports collaboration, accountability, and measurable student success.</p>
            </article>

            <article class="info-card" id="contact">
                <h3><i class="fas fa-headset"></i> Contact Support</h3>
                <p>Questions about logging in, configuring notifications, or managing users? Reach out and we will guide you through every step.</p>
                <div class="contact-links">
                    <p><strong>Email:</strong> <a href="mailto:support@eduassign.com">support@eduassign.com</a></p>
                    <p><strong>Phone:</strong> +91 9947060543 &nbsp;|&nbsp; Mon–Fri, 9am–6pm IST</p>
                    <p><strong>Knowledge Base:</strong> <a href="#help">Browse Help Center</a></p>
                </div>
                <p>For urgent issues, include your institution name, role, and a short description so we can prioritize your request.</p>
            </article>

            <article class="info-card" id="privacy">
                <h3><i class="fas fa-user-secret"></i> Privacy Policy</h3>
                <p>EduAssign adheres to strict security standards to ensure student and institutional data remains protected throughout the platform lifecycle.</p>
                <ul>
                    <li>Data encryption at rest and in transit using modern TLS standards.</li>
                    <li>Granular permissions that limit data visibility by user role.</li>
                    <li>No sale or external sharing of personal information without consent.</li>
                </ul>
                <p>Need access logs, data exports, or deletion requests? Contact support and we will respond within 3–5 business days.</p>
            </article>

            <article class="info-card" id="terms">
                <h3><i class="fas fa-file-contract"></i> Terms of Service</h3>
                <p>Use of EduAssign requires adherence to institutional policies and a commitment to academic integrity.</p>
                <ul>
                    <li>Accounts are issued per user and should not be shared or redistributed.</li>
                    <li>Uploaded content must comply with copyright laws and campus guidelines.</li>
                    <li>We reserve the right to suspend accounts involved in misuse or security threats.</li>
                </ul>
                <p>Administrators can request tailored agreements and documentation by contacting our success team.</p>
            </article>

            <article class="info-card" id="help">
                <h3><i class="fas fa-life-ring"></i> Help Center</h3>
                <p>Explore self-service resources to troubleshoot issues, learn new features, and train your team.</p>
                <ul>
                    <li><strong>Quick Start Guides:</strong> Onboarding for students, teachers, and administrators.</li>
                    <li><strong>Troubleshooting Library:</strong> Solutions for login issues, uploads, and notification settings.</li>
                    <li><strong>Release Notes:</strong> Stay current with feature updates and maintenance schedules.</li>
                </ul>
                <p>Still need assistance? Open a support ticket and we will follow up promptly.</p>
            </article>
        </section>
    </main>

    <footer>
        <div class="footer-links">
            <a href="#about">About EduAssign</a>
            <a href="#contact">Contact Support</a>
            <a href="#privacy">Privacy Policy</a>
            <a href="#terms">Terms of Service</a>
            <a href="#help">Help Center</a>
        </div>
        <p>&copy; <?php echo date('Y'); ?> EduAssign. All rights reserved.</p>
    </footer>

    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const nav = document.getElementById('primary-nav');
        menuToggle.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
        document.addEventListener('click', (event) => {
            if (!nav.contains(event.target) && !menuToggle.contains(event.target)) {
                nav.classList.remove('active');
            }
        });
    </script>
</body>
</html>
