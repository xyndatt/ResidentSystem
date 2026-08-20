<?php
session_start();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["role"] === "admin"){
        header("location: staff/dashboard.php");
    } else {
        header("location: resident/dashboard.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Information System - Barangay Health Center</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #EDF6F9 0%, #BEE3F8 50%, #48CAE4 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-background {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .floating-shape {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: #5AA9E6;
            border-radius: 50%;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            background: #48CAE4;
            border-radius: 50%;
            bottom: 20%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            background: #2ECC71;
            border-radius: 50%;
            top: 50%;
            right: 5%;
            animation-delay: 4s;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 600px;
            padding: 2rem;
        }

        .hero-icon {
            font-size: 5rem;
            color: #5AA9E6;
            margin-bottom: 1rem;
            animation: fadeInDown 0.8s ease-out;
        }

        .hero-icon .health-cross {
            font-size: 2rem;
            color: #2ECC71;
            vertical-align: super;
            margin-left: 0.2rem;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: #5AA9E6;
            margin-bottom: 0.5rem;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .hero-description {
            font-size: 1.1rem;
            color: #95A5A6;
            margin-bottom: 2rem;
            line-height: 1.8;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .hero-buttons .btn {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            width: auto;
        }

        .btn-login {
            background: linear-gradient(135deg, #5AA9E6 0%, #48CAE4 100%);
            color: white;
            box-shadow: 10px 10px 20px rgba(0, 0, 0, 0.08), -10px -10px 20px white;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 15px 15px 30px rgba(0, 0, 0, 0.1), -15px -15px 30px white;
        }

        .features-section {
            padding: 4rem 2rem;
            background: linear-gradient(180deg, white 0%, #EDF6F9 100%);
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-title {
            text-align: center;
            font-size: 2.5rem;
            color: #2C3E50;
            margin-bottom: 3rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 25px;
            padding: 2rem;
            text-align: center;
            box-shadow: 10px 10px 20px rgba(0, 0, 0, 0.08), -10px -10px 20px white;
            transition: all 0.3s ease;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 15px 15px 30px rgba(0, 0, 0, 0.1), -15px -15px 30px white;
        }

        .feature-icon {
            font-size: 3rem;
            color: #5AA9E6;
            margin-bottom: 1rem;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 0.5rem;
        }

        .feature-description {
            color: #95A5A6;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .global-footer {
            margin-left: 0;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .hero-description {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-buttons .btn {
                width: 100%;
            }

            .features-title {
                font-size: 1.8rem;
            }

            .floating-shape {
                display: none;
            }
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>

        <div class="hero-content">
            <div class="hero-icon">
                <i class="bi bi-hospital"></i><i class="bi bi-plus-circle health-cross"></i>
            </div>
            <h1 class="hero-title">Resident Information System</h1>
            <p class="hero-subtitle">Barangay Health Center</p>
            <p class="hero-description">
                A modern, secure system for managing resident information and health surveys.
                Residents can easily update their personal information, participate in health surveys,
                upload photos, and access their profiles. Staff members have full administrative control
                to manage records and surveys.
            </p>

            <div class="hero-buttons">
                <a href="auth/login.php" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <h2 class="features-title">System Features</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h3 class="feature-title">Secure Login</h3>
                <p class="feature-description">
                    Password-protected access with role-based authentication for residents and staff.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-person-vcard"></i>
                </div>
                <h3 class="feature-title">Profile Management</h3>
                <p class="feature-description">
                    Easily update personal information, family details, and contact information.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-image"></i>
                </div>
                <h3 class="feature-title">Photo Upload</h3>
                <p class="feature-description">
                    Upload and manage passport-size photos with automatic validation.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-printer"></i>
                </div>
                <h3 class="feature-title">Print Profile</h3>
                <p class="feature-description">
                    Generate and print professional A4 profile documents with all information.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h3 class="feature-title">Staff Dashboard</h3>
                <p class="feature-description">
                    Comprehensive statistics and analytics for staff members to monitor records.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-file-earmark-pdf"></i>
                </div>
                <h3 class="feature-title">Generate Reports</h3>
                <p class="feature-description">
                    Create detailed reports and export data for administrative purposes.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <h3 class="feature-title">Health Surveys</h3>
                <p class="feature-description">
                    Participate in health surveys and help the Barangay Health Center improve services.
                </p>
            </div>
        </div>
    </section>

    <!-- Global Footer -->
    <footer class="global-footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3><i class="bi bi-hospital"></i> Barangay Health Center</h3>
                <p>Empowering our community through efficient health services and modern technology.</p>
            </div>
            <div class="footer-column">
                <h3>Contact</h3>
                <p><i class="bi bi-telephone"></i> (02) 8888-1234</p>
                <p><i class="bi bi-envelope"></i> health@barangay.gov</p>
            </div>
            <div class="footer-column">
                <h3>Address</h3>
                <p><i class="bi bi-geo-alt"></i> 123 Health Street, Barangay Center, Metro Manila</p>
            </div>
            <div class="footer-column">
                <h3>Office Hours</h3>
                <p><i class="bi bi-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Barangay Health Center. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
