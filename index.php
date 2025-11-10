<?php
session_start();

// Include database connection
require_once 'db.php';

// Get statistics from database
try {
    // Count total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $total_users = $stmt->fetchColumn();
    
    // Count total students
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'");
    $total_students = $stmt->fetchColumn();
    
    // Count total teachers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active'");
    $total_teachers = $stmt->fetchColumn();
    
    // Count total assignments
    $stmt = $pdo->query("SELECT COUNT(*) FROM assignments WHERE status = 'active'");
    $total_assignments = $stmt->fetchColumn();
    
    // Count total submissions
    $stmt = $pdo->query("SELECT COUNT(*) FROM submissions");
    $total_submissions = $stmt->fetchColumn();
    
    // Count pending registrations (users who are not approved yet)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $pending_registrations = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // Default values if database query fails
    $total_users = 0;
    $total_students = 0;
    $total_teachers = 0;
    $total_assignments = 0;
    $total_submissions = 0;
    $pending_registrations = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduAssign - Online College Assignment Management System</title>
    <meta name="description" content="Streamline your academic workflow with EduAssign - the comprehensive assignment management system for students, teachers, and administrators.">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Typed.js for typing animation -->
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .floating-element {
            position: absolute;
            color: rgba(255, 255, 255, 0.08);
            animation: float 8s ease-in-out infinite;
            pointer-events: none;
        }

        .floating-element:nth-child(1) {
            top: 5%;
            left: 8%;
            font-size: 3.2rem;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 15%;
            right: 12%;
            font-size: 2.8rem;
            animation-delay: 1.2s;
        }

        .floating-element:nth-child(3) {
            top: 55%;
            left: 3%;
            font-size: 2.4rem;
            animation-delay: 2.4s;
        }

        .floating-element:nth-child(4) {
            top: 75%;
            right: 8%;
            font-size: 3.8rem;
            animation-delay: 3.6s;
        }

        .floating-element:nth-child(5) {
            top: 35%;
            left: 85%;
            font-size: 3rem;
            animation-delay: 4.8s;
        }

        .floating-element:nth-child(6) {
            top: 25%;
            left: 45%;
            font-size: 2.6rem;
            animation-delay: 6s;
        }

        .floating-element:nth-child(7) {
            top: 85%;
            left: 25%;
            font-size: 2.9rem;
            animation-delay: 1.8s;
        }

        .floating-element:nth-child(8) {
            top: 10%;
            left: 65%;
            font-size: 2.7rem;
            animation-delay: 3s;
        }

        .floating-element:nth-child(9) {
            top: 45%;
            right: 20%;
            font-size: 3.4rem;
            animation-delay: 4.2s;
        }

        .floating-element:nth-child(10) {
            top: 90%;
            right: 3%;
            font-size: 2.5rem;
            animation-delay: 5.4s;
        }

        .floating-element:nth-child(11) {
            top: 65%;
            left: 60%;
            font-size: 2.3rem;
            animation-delay: 7.2s;
        }

        .floating-element:nth-child(12) {
            top: 30%;
            left: 15%;
            font-size: 2.8rem;
            animation-delay: 0.6s;
        }

        /* Geometric shapes animation */
        .geometric-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            opacity: 0.04;
            animation: rotate 25s linear infinite;
        }

        .shape:nth-child(1) {
            top: 18%;
            left: 22%;
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            top: 65%;
            right: 18%;
            width: 90px;
            height: 90px;
            background: white;
            transform: rotate(45deg);
            animation-delay: 6s;
        }

        .shape:nth-child(3) {
            top: 42%;
            left: 65%;
            width: 0;
            height: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
            border-bottom: 85px solid white;
            animation-delay: 12s;
        }

        .shape:nth-child(4) {
            top: 72%;
            left: 8%;
            width: 140px;
            height: 140px;
            background: white;
            border-radius: 25px;
            animation-delay: 18s;
        }

        .shape:nth-child(5) {
            top: 8%;
            right: 35%;
            width: 100px;
            height: 100px;
            background: white;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation-delay: 3s;
        }

        /* Floating particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            animation: particleFloat 10s linear infinite;
        }

        .particle:nth-child(1) { left: 5%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 15%; animation-delay: 1.5s; }
        .particle:nth-child(3) { left: 25%; animation-delay: 3s; }
        .particle:nth-child(4) { left: 35%; animation-delay: 4.5s; }
        .particle:nth-child(5) { left: 45%; animation-delay: 6s; }
        .particle:nth-child(6) { left: 55%; animation-delay: 7.5s; }
        .particle:nth-child(7) { left: 65%; animation-delay: 9s; }
        .particle:nth-child(8) { left: 75%; animation-delay: 1s; }
        .particle:nth-child(9) { left: 85%; animation-delay: 2.5s; }
        .particle:nth-child(10) { left: 95%; animation-delay: 4s; }

        /* Animation Keyframes */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg) scale(1);
                opacity: 0.08;
            }
            25% {
                transform: translateY(-15px) rotate(90deg) scale(1.1);
                opacity: 0.15;
            }
            50% {
                transform: translateY(-25px) rotate(180deg) scale(0.9);
                opacity: 0.25;
            }
            75% {
                transform: translateY(-10px) rotate(270deg) scale(1.05);
                opacity: 0.12;
            }
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.1);
            }
            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            15% {
                opacity: 1;
                transform: translateY(85vh) scale(1);
            }
            85% {
                opacity: 1;
                transform: translateY(15vh) scale(1);
            }
            100% {
                transform: translateY(-10vh) scale(0);
                opacity: 0;
            }
        }

        /* Header Styles */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            box-shadow: 0 2px 25px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        /* On scroll, add background for readability */
        header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 30px rgba(0, 0, 0, 0.15);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo i {
            font-size: 2.2rem;
            color: #667eea;
            animation: pulse 2s infinite;
        }

        .logo h1 {
            font-size: 1.6rem;
            color: #333;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Navigation Styles */
        nav {
            display: flex;
            gap: 2rem;
        }

        nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: left 0.3s ease;
            z-index: -1;
        }

        nav a:hover::before {
            left: 0;
        }

        nav a:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            z-index: 1001;
        }

        .menu-toggle span {
            width: 28px;
            height: 3px;
            background: #333;
            margin: 4px 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-6px, 6px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(45deg) translate(-6px, -6px);
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            padding: 5rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero h2 {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            font-weight: 800;
            letter-spacing: -1px;
        }

        .hero .subtitle {
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            animation: fadeInUp 1s ease 0.2s both;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.4s both;
        }

        .cta-btn {
            padding: 1.2rem 2.5rem;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .cta-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
        }

        .cta-btn:hover::before {
            left: 100%;
        }

        .cta-btn:hover {
            background: white;
            color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .cta-btn.primary {
            background: white;
            color: #667eea;
            border: 2px solid white;
        }

        .cta-btn.primary:hover {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        /* Quick Access Section */
        .quick-access {
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(15px);
            position: relative;
            z-index: 1;
        }

        .quick-access-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .quick-access h3 {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: #333;
            font-weight: 700;
        }

        .quick-access p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .access-card {
            background: white;
            padding: 2.5rem;
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .access-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.05), transparent);
            transition: left 0.6s;
        }

        .access-card:hover::before {
            left: 100%;
        }

        .access-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border-color: rgba(102, 126, 234, 0.3);
        }

        .access-icon {
            font-size: 3.5rem;
            color: #667eea;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .access-card:hover .access-icon {
            transform: scale(1.1);
            color: #764ba2;
        }

        .access-card h4 {
            font-size: 1.6rem;
            margin-bottom: 1rem;
            color: #333;
            font-weight: 600;
        }

        .access-card p {
            color: #666;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .access-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .access-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #764ba2, #667eea);
            transition: left 0.3s ease;
        }

        .access-btn:hover::before {
            left: 0;
        }

        .access-btn span {
            position: relative;
            z-index: 1;
        }

        .access-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        /* Features Preview */
        .features-preview {
            padding: 4rem 2rem;
            background: rgba(102, 126, 234, 0.5);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            position: relative;
            z-index: 1;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .features-preview h3 {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .features-preview .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-item {
            text-align: center;
            padding: 1.5rem;
        }

        .feature-item i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .feature-item h4 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .feature-item p {
            opacity: 0.8;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(15px);
            position: relative;
            z-index: 1;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            padding: 1.5rem;
        }

        .stat-item h4 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            color: #667eea;
            font-weight: 800;
        }

        .stat-item p {
            font-size: 1.2rem;
            color: #666;
            font-weight: 500;
        }


        /* Footer */
        footer {
            background: rgba(44, 62, 80, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            text-align: center;
            padding: 3rem 2rem 2rem;
            position: relative;
            z-index: 1;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .footer-links a:hover {
            color: #667eea;
            transform: translateY(-2px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
            margin-top: 2rem;
        }

        /* Animations */
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }

            nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(15px);
                flex-direction: column;
                padding: 2rem;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
                border-radius: 0 0 20px 20px;
            }

            nav.active {
                display: flex;
            }

            nav a {
                margin: 0.5rem 0;
                text-align: center;
            }

            .hero h2 {
                font-size: 2.8rem;
            }

            .hero .subtitle {
                font-size: 1.2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .header-container {
                padding: 1rem;
            }

            .access-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }


            .floating-element {
                font-size: 1.8rem !important;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .hero h2 {
                font-size: 2.2rem;
            }

            .hero .subtitle {
                font-size: 1.1rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .floating-element {
                font-size: 1.5rem !important;
            }

            .cta-btn {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }
        /* Glassmorphism and 3D depth enhancements */
        .access-card, .feature-item {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
            backdrop-filter: blur(12px);
            border-radius: 25px;
            border: 1.5px solid rgba(255, 255, 255, 0.18);
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .access-card:hover, .feature-item:hover {
            box-shadow: 0 16px 48px 0 rgba(31, 38, 135, 0.28);
            border-color: rgba(102, 126, 234, 0.3);
        }
        .cta-btn, .access-btn {
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
            border: 2px solid rgba(255,255,255,0.25);
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(8px);
        }
        .cta-btn:hover, .access-btn:hover {
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.25);
            border: 2px solid #764ba2;
        }
        /* 3D tilt effect cursor */
        .access-card, .feature-item {
            will-change: transform;
        }
        /* Animated gradient border for hero */
        .hero {
            border-radius: 32px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
        }
        /* Vanta background container */
        #vanta-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -2;
        }
        /* Study/Assignment Animation Section */
        .study-animations {
            position: relative;
            width: 100vw;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2;
            padding-top: 40px;
            padding-bottom: 10px;
            margin-top: 90px;
        }
        .animated-sheets {
            position: relative;
            height: 60px;
            margin-bottom: 10px;
        }
        .sheet {
            font-size: 2.5rem;
            color: #667eea;
            position: absolute;
            opacity: 0.85;
            animation: floatSheet 3s ease-in-out infinite, sheetEntrance 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55) 0.2s 1 both;
        }
        .sheet1 { left: 0; animation-delay: 0s, 0.1s; }
        .sheet2 { left: 40px; animation-delay: 0.7s, 0.3s; color: #764ba2; }
        .sheet3 { left: 80px; animation-delay: 1.4s, 0.5s; color: #ffb347; }
        @keyframes floatSheet {
            0%, 100% { transform: translateY(0) rotate(-5deg); }
            50% { transform: translateY(-18px) rotate(8deg); }
        }
        @keyframes sheetEntrance {
            0% { opacity: 0; transform: translateX(-60px) scale(0.7) rotate(-20deg); }
            80% { opacity: 1; transform: translateX(8px) scale(1.05) rotate(5deg); }
            100% { opacity: 1; transform: translateX(0) scale(1) rotate(0deg); }
        }
        .animated-pencil {
            position: relative;
            margin-bottom: 10px;
            height: 32px;
        }
        .pencil {
            font-size: 2.2rem;
            color: #ffb347;
            position: absolute;
            left: 0;
            top: 0;
            animation: pencilWrite 2.2s linear infinite, pencilEntrance 0.9s cubic-bezier(0.68, -0.55, 0.27, 1.55) 0.7s 1 both;
        }
        @keyframes pencilWrite {
            0% { left: 0; }
            60% { left: 60px; }
            100% { left: 0; }
        }
        @keyframes pencilEntrance {
            0% { opacity: 0; transform: translateX(-80px) scale(0.7) rotate(-30deg); }
            80% { opacity: 1; transform: translateX(10px) scale(1.1) rotate(10deg); }
            100% { opacity: 1; transform: translateX(0) scale(1) rotate(0deg); }
        }
        .writing-line {
            display: inline-block;
            position: absolute;
            left: 36px;
            top: 18px;
            width: 0;
            height: 3px;
            background: #764ba2;
            border-radius: 2px;
            animation: lineWrite 2.2s linear infinite, lineEntrance 0.9s cubic-bezier(0.68, -0.55, 0.27, 1.55) 0.8s 1 both;
        }
        @keyframes lineWrite {
            0% { width: 0; }
            60% { width: 60px; }
            100% { width: 0; }
        }
        @keyframes lineEntrance {
            0% { opacity: 0; width: 0; }
            100% { opacity: 1; width: 0; }
        }
        .study-icons {
            display: flex;
            gap: 30px;
            margin-top: 10px;
        }
        .study-icon {
            font-size: 2.1rem;
            color: #667eea;
            opacity: 0.85;
            animation: bounceIcon 2.5s infinite, iconEntrance 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55) 1.1s 1 both;
        }
        .study-icon:nth-child(2) { color: #764ba2; animation-delay: 0.5s, 1.3s; }
        .study-icon:nth-child(3) { color: #ffb347; animation-delay: 1s, 1.5s; }
        @keyframes bounceIcon {
            0%, 100% { transform: translateY(0); }
            30% { transform: translateY(-18px) scale(1.1); }
            60% { transform: translateY(0); }
        }
        @keyframes iconEntrance {
            0% { opacity: 0; transform: translateY(40px) scale(0.7) rotate(-10deg); }
            80% { opacity: 1; transform: translateY(-8px) scale(1.1) rotate(8deg); }
            100% { opacity: 1; transform: translateY(0) scale(1) rotate(0deg); }
        }
    </style>
</head>
<body>
    <!-- Vanta.js Animated Background -->
    <div id="vanta-bg"></div>
    <!-- Assignment/Study Animation Section -->
    <div class="study-animations">
        <!-- Animated sheets and pencil removed as requested -->
    </div>

    <!-- Animated Background -->
    <div class="background-animation">
        <!-- Floating Educational Icons -->
        <div class="floating-element"><i class="fas fa-book-open"></i></div>
        <div class="floating-element"><i class="fas fa-graduation-cap"></i></div>
        <div class="floating-element"><i class="fas fa-pencil-alt"></i></div>
        <div class="floating-element"><i class="fas fa-laptop-code"></i></div>
        <div class="floating-element"><i class="fas fa-calculator"></i></div>
        <div class="floating-element"><i class="fas fa-microscope"></i></div>
        <div class="floating-element"><i class="fas fa-atom"></i></div>
        <div class="floating-element"><i class="fas fa-flask"></i></div>
        <div class="floating-element"><i class="fas fa-globe-americas"></i></div>
        <div class="floating-element"><i class="fas fa-lightbulb"></i></div>
        <div class="floating-element"><i class="fas fa-brain"></i></div>
        <div class="floating-element"><i class="fas fa-dna"></i></div>

        <!-- Geometric Shapes -->
        <div class="geometric-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <!-- Floating Particles -->
        <div class="particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
    </div>

    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h1>EduAssign</h1>
            </div>
            <nav id="nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="student_login.php"><i class="fas fa-user-graduate"></i> Student Login</a>
                <a href="teacher_login.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Login</a>
                <a href="admin_login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
            </nav>
            <div class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <h2>Transform Your Academic Journey</h2>
            <p class="subtitle">Experience seamless assignment management with EduAssign - where education meets innovation. Streamline submissions, enhance collaboration, and achieve academic excellence.</p>
            <div class="cta-buttons">
                <a href="student_login.php" class="cta-btn primary">Start Learning</a>
                <a href="#quick-access" class="cta-btn">Explore Features</a>
            </div>
        </div>
    </section>

    <!-- Quick Access Section -->
    <section class="quick-access" id="quick-access">
        <div class="quick-access-container">
            <h3>Choose Your Portal</h3>
            <p>Access your personalized dashboard based on your role in the academic community</p>
            
            <div class="access-grid">
                <div class="access-card">
                    <div class="access-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h4>Student Portal</h4>
                    <p>Submit assignments, track progress, receive feedback, and collaborate with peers. Your academic success starts here.</p>
                    <a href="student_login.php" class="access-btn">
                        <span>Access Student Portal</span>
                    </a>
                </div>

                <div class="access-card">
                    <div class="access-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h4>Teacher Portal</h4>
                    <p>Create assignments, evaluate submissions, provide meaningful feedback, and monitor student performance effectively.</p>
                    <a href="teacher_login.php" class="access-btn">
                        <span>Access Teacher Portal</span>
                    </a>
                </div>

                <div class="access-card">
                    <div class="access-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h4>Admin Portal</h4>
                    <p>Manage system operations, oversee user accounts, generate comprehensive reports, and maintain institutional standards.</p>
                    <a href="admin_login.php" class="access-btn">
                        <span>Access Admin Portal</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Preview -->
    <section class="features-preview">
        <div class="features-container">
            <h3>Why Choose EduAssign?</h3>
            <p class="subtitle">Discover the powerful features that make academic management effortless</p>
            
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Easy Submissions</h4>
                    <p>Upload assignments with drag-and-drop simplicity</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-clock"></i>
                    <h4>Real-time Tracking</h4>
                    <p>Monitor deadlines and progress instantly</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-comments"></i>
                    <h4>Interactive Feedback</h4>
                    <p>Receive detailed, constructive feedback</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <h4>Performance Analytics</h4>
                    <p>Track academic progress with detailed insights</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    <h4>Mobile Friendly</h4>
                    <p>Access your work from any device, anywhere</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Secure Platform</h4>
                    <p>Your data is protected with enterprise-grade security</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat-item">
                <h4><?php echo number_format($total_students); ?></h4>
                <p><i class="fas fa-user-graduate"></i> Active Students</p>
            </div>
            <div class="stat-item">
                <h4><?php echo number_format($total_teachers); ?></h4>
                <p><i class="fas fa-chalkboard-teacher"></i> Active Teachers</p>
            </div>
            <div class="stat-item">
                <h4><?php echo number_format($total_assignments); ?></h4>
                <p><i class="fas fa-tasks"></i> Active Assignments</p>
            </div>
            <div class="stat-item">
                <h4><?php echo number_format($total_submissions); ?></h4>
                <p><i class="fas fa-file-upload"></i> Total Submissions</p>
            </div>
            <?php if ($pending_registrations > 0): ?>
            <div class="stat-item">
                <h4><?php echo number_format($pending_registrations); ?></h4>
                <p><i class="fas fa-clock"></i> Pending Approvals</p>
            </div>
            <?php endif; ?>
        </div>
    </section>


    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="info.php#about">About EduAssign</a>
                <a href="info.php#contact">Contact Support</a>
                <a href="info.php#privacy">Privacy Policy</a>
                <a href="info.php#terms">Terms of Service</a>
                <a href="info.php#help">Help Center</a>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> EduAssign - Online College Assignment Management System. All rights reserved.</p>
                <p style="margin-top: 0.5rem; opacity: 0.8; font-size: 0.9rem;">Empowering education through technology</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const nav = document.getElementById('nav');
            const toggle = document.querySelector('.menu-toggle');
            nav.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.access-card, .feature-item, .stat-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.getElementById('nav');
            const toggle = document.querySelector('.menu-toggle');
            const header = document.querySelector('header');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (!header.contains(e.target) && !menuToggle.contains(e.target) && nav.classList.contains('active')) {
                nav.classList.remove('active');
                toggle.classList.remove('active');
            }
        });

        // 3D Tilt effect for cards
        function add3DTilt(selector) {
            document.querySelectorAll(selector).forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / centerY) * 10;
                    const rotateY = ((x - centerX) / centerX) * 10;
                    card.style.transform = `perspective(600px) rotateX(${-rotateX}deg) rotateY(${rotateY}deg) scale(1.04)`;
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(600px) rotateX(0deg) rotateY(0deg) scale(1)';
                });
            });
        }
        add3DTilt('.access-card');
        add3DTilt('.feature-item');

        // New modern animations using GSAP
        document.addEventListener('DOMContentLoaded', function() {
            // Hero section text animation
            const heroTitle = document.querySelector('.hero h2');
            const heroSubtitle = document.querySelector('.hero .subtitle');
            const ctaButtons = document.querySelector('.cta-buttons');
            
            gsap.set([heroTitle, heroSubtitle, ctaButtons], {opacity: 0, y: 30});
            
            const heroTimeline = gsap.timeline({delay: 0.5});
            heroTimeline.to(heroTitle, {opacity: 1, y: 0, duration: 0.8, ease: "power3.out"})
                       .to(heroSubtitle, {opacity: 1, y: 0, duration: 0.8, ease: "power3.out"}, "-=0.6")
                       .to(ctaButtons, {opacity: 1, y: 0, duration: 0.8, ease: "power3.out"}, "-=0.6");
            
            // Floating icons animation enhancement
            gsap.to('.floating-element', {
                y: "random(-20, 20)",
                x: "random(-20, 20)",
                rotation: "random(-15, 15)",
                duration: "random(3, 6)",
                ease: "sine.inOut",
                repeat: -1,
                yoyo: true,
                stagger: 0.1
            });
            
            // Scroll animations
            const scrollElements = ['.access-card', '.feature-item', '.stat-item'];
            scrollElements.forEach(selector => {
                gsap.utils.toArray(selector).forEach(element => {
                    gsap.set(element, {opacity: 0, y: 30});
                    
                    ScrollTrigger.create({
                        trigger: element,
                        start: "top 85%",
                        onEnter: () => {
                            gsap.to(element, {
                                opacity: 1,
                                y: 0,
                                duration: 0.8,
                                ease: "power3.out"
                            });
                        },
                        once: true
                    });
                });
            });
            
            // Stats counter animation
            const statItems = document.querySelectorAll('.stat-item h4');
            statItems.forEach(stat => {
                const value = stat.innerText;
                const hasPlus = value.includes('+');
                const numericValue = parseFloat(value.replace('+', '').replace('%', ''));
                
                gsap.set(stat, {innerText: '0'});
                
                ScrollTrigger.create({
                    trigger: stat,
                    start: "top 85%",
                    onEnter: () => {
                        gsap.to(stat, {
                            innerText: numericValue,
                            duration: 2,
                            ease: "power2.out",
                            snap: {innerText: 1},
                            onUpdate: () => {
                                if (hasPlus) {
                                    stat.innerText = stat.innerText + '+';
                                } else if (value.includes('%')) {
                                    stat.innerText = stat.innerText + '%';
                                }
                            }
                        });
                    },
                    once: true
                });
            });
            
            // Logo pulse animation
            gsap.to('.logo i', {
                scale: 1.1,
                duration: 1.5,
                repeat: -1,
                yoyo: true,
                ease: "sine.inOut"
            });
            
            // Button hover animations
            const buttons = document.querySelectorAll('.cta-btn, .access-btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', () => {
                    gsap.to(button, {
                        scale: 1.05,
                        duration: 0.3,
                        ease: "power1.out"
                    });
                });
                
                button.addEventListener('mouseleave', () => {
                    gsap.to(button, {
                        scale: 1,
                        duration: 0.3,
                        ease: "power1.in"
                    });
                });
            });
        });
    </script>
</body>
</html>
