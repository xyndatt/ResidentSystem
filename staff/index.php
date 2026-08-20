<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$admin_name = $_SESSION["username"] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ── Layout wrapper so footer always below sidebar ── */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* sidebar fixed, everything else offset */
        .layout-shell {
            margin-left: 220px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 0;   /* reset – layout-shell handles offset */
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content > .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            flex-shrink: 0;
        }

        #spa-content {
            flex: 1;
            padding: 2rem;
        }

        .global-footer {
            margin-left: 0;
        }

        /* Navbar brand shows current page */
        .navbar-brand span { font-weight: 700; color: var(--primary); }
        .navbar-user {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
        }
        .navbar-user strong { color: var(--dark); }
        .navbar-menu { gap: 1rem; }
        .navbar-menu a {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Fixed sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-hospital"></i> RIS Admin</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active" data-spa><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="residents.php" data-spa><i class="bi bi-people"></i> Residents</a></li>
            <li><a href="add_resident.php" data-spa><i class="bi bi-person-plus"></i> Add Resident</a></li>
            <li><a href="manage_staff.php" data-spa><i class="bi bi-person-badge"></i> Manage Staff</a></li>
            <li><a href="search.php" data-spa><i class="bi bi-search"></i> Search</a></li>
            <li><a href="reports.php" data-spa><i class="bi bi-file-earmark-pdf"></i> Reports</a></li>
            <li><a href="activity_logs.php" data-spa><i class="bi bi-clock-history"></i> Activity Logs</a></li>
            <li class="sidebar-divider"><span>Surveys</span></li>
            <li><a href="surveys.php" data-spa><i class="bi bi-clipboard-check"></i> Manage Surveys</a></li>
            <li><a href="survey_results.php" data-spa><i class="bi bi-bar-chart"></i> Survey Results</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Offset wrapper (sidebar width) -->
    <div class="layout-shell">
        <div class="main-content">
            <nav class="navbar">
                <div class="navbar-container">
                    <div class="navbar-brand" id="navPageTitle">Staff Dashboard</div>
                    <div style="display:flex;align-items:center;gap:1.2rem;">
                        <span class="navbar-user">Logged in as <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
                        <a href="../auth/logout.php" class="btn btn-sm btn-secondary" style="display:inline-flex;align-items:center;gap:0.3rem;">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <div id="spa-content">
                <div class="spa-loading">
                    <div class="spinner"></div>
                    <p>Loading...</p>
                </div>
            </div>
        </div>

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
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            initSPA('spa-content');
        });

        // Update navbar title when SPA navigates
        var _origUpdatePageTitle = updatePageTitle;
        function updatePageTitle(url) {
            _origUpdatePageTitle(url);
            var page = url.split('?')[0].replace('.php','').replace(/_/g,' ');
            page = page.charAt(0).toUpperCase() + page.slice(1);
            var titles = {
                'dashboard': 'Staff Dashboard',
                'residents': 'Residents',
                'add resident': 'Add Resident',
                'edit resident': 'Edit Resident',
                'view resident': 'View Resident',
                'manage staff': 'Manage Staff',
                'search': 'Search',
                'reports': 'Reports',
                'activity logs': 'Activity Logs',
                'surveys': 'Manage Surveys',
                'survey results': 'Survey Results',
                'create survey': 'Create Survey',
                'edit survey': 'Edit Survey',
            };
            var title = titles[page.toLowerCase()] || page;
            var el = document.getElementById('navPageTitle');
            if (el) el.textContent = title;
        }
    </script>
</body>
</html>
