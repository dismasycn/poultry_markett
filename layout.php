<?php
// Ensure core functions are loaded
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Poultry Market' ?></title>

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/style.css">

    <!-- Favicon (optional) -->
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>assets/images/favicon.ico">

    <style>
        /* ========================================
           ENHANCED LAYOUT – TEAL THEME
           ======================================== */

        /* Override default Bootstrap success colours */
        :root {
            --bs-success: #0d6b6b;
            --bs-success-rgb: 13, 107, 107;
            --bs-primary: #0d6b6b;
            --bs-primary-rgb: 13, 107, 107;
            --bs-link-color: #0d6b6b;
            --bs-link-hover-color: #094a4a;
        }

        .btn-success {
            background-color: #0d6b6b;
            border-color: #0d6b6b;
        }
        .btn-success:hover {
            background-color: #094a4a;
            border-color: #094a4a;
        }
        .btn-outline-success {
            color: #0d6b6b;
            border-color: #0d6b6b;
        }
        .btn-outline-success:hover {
            background-color: #0d6b6b;
            border-color: #0d6b6b;
            color: #fff;
        }
        .badge.bg-success {
            background-color: #0d6b6b !important;
        }
        .bg-success {
            background-color: #0d6b6b !important;
        }

        /* Navbar */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
        }
        .navbar-brand i {
            margin-right: 8px;
        }

        /* Sidebar enhancements */
        .sidebar {
            background-color: var(--bg-sidebar);
            transition: background-color var(--transition-speed);
            border-right: 1px solid var(--border-color);
            box-shadow: 2px 0 8px rgba(0,0,0,0.04);
        }
        .sidebar .nav-link {
            color: var(--text-primary);
            font-weight: 500;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            margin: 3px 10px;
            transition: all 0.25s ease;
            font-size: 0.95rem;
        }
        .sidebar .nav-link i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        .sidebar .nav-link:hover {
            background: rgba(13, 107, 107, 0.12);
            color: #0d6b6b;
            transform: translateX(4px);
        }
        .sidebar .nav-link.active {
            background: #0d6b6b;
            color: #fff;
            box-shadow: 0 4px 12px rgba(13, 107, 107, 0.3);
        }
        .sidebar .nav-link.active i {
            color: #fff;
        }

        /* Card enhancements */
        .card {
            border-radius: 14px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: box-shadow 0.3s ease, transform 0.2s ease;
        }
        .card:hover {
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
        }
        .card-header {
            border-radius: 14px 14px 0 0 !important;
            font-weight: 600;
        }

        /* Main content padding */
        main {
            padding-top: 1rem;
            padding-bottom: 2rem;
        }

        /* Theme toggle button */
        .theme-toggle {
            background: rgba(255,255,255,0.15);
            border: none;
            color: #fff;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 50px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .theme-toggle:hover {
            background: rgba(255,255,255,0.25);
        }
        [data-theme="dark"] .theme-toggle {
            background: rgba(255,255,255,0.1);
            color: #ffc107;
        }
        [data-theme="dark"] .theme-toggle:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Navbar user badge */
        .user-badge {
            background: rgba(255,255,255,0.15);
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Footer */
        .footer {
            background-color: var(--bg-card) !important;
            border-top: 1px solid var(--border-color);
            padding: 1.5rem 0;
            margin-top: 2rem;
        }
        .footer a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer a:hover {
            color: #0d6b6b;
        }

        /* Mobile sidebar toggle */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 56px;
                left: -280px;
                width: 280px;
                height: calc(100vh - 56px);
                z-index: 1050;
                transition: left 0.3s ease;
                overflow-y: auto;
                box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 56px;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.4);
                z-index: 1040;
            }
            .sidebar-overlay.show {
                display: block;
            }
        }

        /* Toast notifications */
        .toast-container {
            z-index: 9999;
        }

        /* Smooth transitions for theme switch */
        body, .card, .sidebar, .navbar, .footer {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
    </style>
</head>
<body>

    <!-- ======================================== -->
    <!-- NAVBAR -->
    <!-- ======================================== -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
        <div class="container-fluid">
            <!-- Mobile sidebar toggle -->
            <button class="navbar-toggler me-2" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand" href="<?= SITE_URL ?>">
                <i class="bi bi-egg-fried"></i> PoultryMarket
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    <?php if (isLoggedIn()): ?>
                        <!-- Theme toggle -->
                        <li class="nav-item">
                            <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark/light mode" title="Toggle theme">
                                <i class="bi bi-moon-fill" id="themeIcon"></i>
                                <span class="d-none d-lg-inline" id="themeLabel">Light</span>
                            </button>
                        </li>

                        <!-- Messages -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?= SITE_URL ?>messages.php" title="Messages">
                                <i class="bi bi-envelope fs-5"></i>
                                <?php
                                $unread = function_exists('getUnreadCount') ? getUnreadCount($_SESSION['user_id']) : 0;
                                if ($unread > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                                        <?= $unread ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <!-- User name + role -->
                        <li class="nav-item">
                            <span class="user-badge text-white">
                                <i class="bi bi-person-circle me-1"></i>
                                <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                                <span class="text-white-50 ms-1">(<?= $_SESSION['role'] ?? 'guest' ?>)</span>
                            </span>
                        </li>

                        <!-- Logout -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>logout.php" title="Logout">
                                <i class="bi bi-box-arrow-right fs-5"></i>
                            </a>
                        </li>

                    <?php else: ?>
                        <!-- Theme toggle for guests -->
                        <li class="nav-item">
                            <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark/light mode">
                                <i class="bi bi-moon-fill" id="themeIcon"></i>
                            </button>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-light text-success px-3 rounded-pill fw-semibold" href="<?= SITE_URL ?>register.php" style="background:rgba(255,255,255,0.9);">
                                Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ======================================== -->
    <!-- MOBILE SIDEBAR OVERLAY -->
    <!-- ======================================== -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ======================================== -->
    <!-- MAIN LAYOUT -->
    <!-- ======================================== -->
    <div class="container-fluid">
        <div class="row">

            <!-- SIDEBAR (desktop) -->
            <?php if (isLoggedIn()): ?>
                <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar vh-100 position-sticky" style="top: 56px; overflow-y: auto;">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <?php
                            $role = getUserRole();
                            $unread = function_exists('getUnreadCount') ? getUnreadCount($_SESSION['user_id']) : 0;
                            $current_page = basename($_SERVER['PHP_SELF']);
                            $current_dir = basename(dirname($_SERVER['PHP_SELF']));

                            function isActive($file, $dir = null) {
                                $current_file = basename($_SERVER['PHP_SELF']);
                                $current_dir = basename(dirname($_SERVER['PHP_SELF']));
                                if ($dir && $current_dir !== $dir) return '';
                                return ($current_file === $file) ? 'active' : '';
                            }
                            ?>

                            <?php if ($role == 'admin'): ?>
                                <!-- Admin Menu -->
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('dashboard.php', 'admin') ?>" href="<?= SITE_URL ?>admin/dashboard.php">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('users.php', 'admin') ?>" href="<?= SITE_URL ?>admin/users.php">
                                        <i class="bi bi-people"></i> Users
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('batches.php', 'admin') ?>" href="<?= SITE_URL ?>admin/batches.php">
                                        <i class="bi bi-box"></i> Batches
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('orders.php', 'admin') ?>" href="<?= SITE_URL ?>admin/orders.php">
                                        <i class="bi bi-cart"></i> Orders
                                    </a>
                                </li>
                                <li class="nav-item">
    <a class="nav-link <?= isActive('activity.php', 'admin') ?>" href="<?= SITE_URL ?>admin/activity.php">
        <i class="bi bi-clock-history"></i> Activity Logs
    </a>
</li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('reports.php', 'admin') ?>" href="<?= SITE_URL ?>admin/reports.php">
                                        <i class="bi bi-bar-chart"></i> Reports
                                    </a>
                                </li>

                            <?php elseif ($role == 'farmer'): ?>
                                <!-- Farmer Menu -->
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('dashboard.php', 'farmer') ?>" href="<?= SITE_URL ?>farmer/dashboard.php">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('add_batch.php', 'farmer') ?>" href="<?= SITE_URL ?>farmer/add_batch.php">
                                        <i class="bi bi-plus-circle"></i> Add New Batch
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('my_batches.php', 'farmer') ?>" href="<?= SITE_URL ?>farmer/my_batches.php">
                                        <i class="bi bi-box-seam"></i> My Batches
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('orders.php', 'farmer') ?>" href="<?= SITE_URL ?>farmer/orders.php">
                                        <i class="bi bi-inbox"></i> Orders Received
                                        <?php
                                        $pending_count = 0;
                                        if ($role == 'farmer') {
                                            $result = $conn->query("
                                                SELECT COUNT(*) as cnt FROM orders o 
                                                JOIN batches b ON o.batch_id = b.id 
                                                WHERE b.farmer_id = {$_SESSION['user_id']} AND o.status = 'pending'
                                            ");
                                            $pending_count = $result->fetch_assoc()['cnt'] ?? 0;
                                        }
                                        if ($pending_count > 0): ?>
                                            <span class="badge bg-danger rounded-pill ms-1"><?= $pending_count ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('reports.php', 'farmer') ?>" href="<?= SITE_URL ?>farmer/reports.php">
                                        <i class="bi bi-graph-up"></i> Sales Reports
                                    </a>
                                </li>

                            <?php elseif ($role == 'buyer'): ?>
                                <!-- Buyer Menu -->
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('dashboard.php', 'buyer') ?>" href="<?= SITE_URL ?>buyer/dashboard.php">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('browse.php', 'buyer') ?>" href="<?= SITE_URL ?>buyer/browse.php">
                                        <i class="bi bi-search"></i> Browse Chickens
                                        <?php
                                        $available = $conn->query("SELECT COUNT(*) FROM batches WHERE status = 'available' AND quantity > 0")->fetch_row()[0] ?? 0;
                                        if ($available > 0): ?>
                                            <span class="badge bg-success rounded-pill ms-1"><?= $available ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= isActive('my_orders.php', 'buyer') ?>" href="<?= SITE_URL ?>buyer/my_orders.php">
                                        <i class="bi bi-receipt"></i> My Orders
                                        <?php
                                        $pending = $conn->query("SELECT COUNT(*) FROM orders WHERE buyer_id = {$_SESSION['user_id']} AND status = 'pending'")->fetch_row()[0] ?? 0;
                                        if ($pending > 0): ?>
                                            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= $pending ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                           

                            <!-- Common: Settings -->
                            <li class="nav-item">
                                <a class="nav-link <?= isActive('profile.php', '') ?>" href="<?= SITE_URL ?>profile.php">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li>

                            <!-- Divider + Logout (visible on mobile) -->
                            <li class="nav-item d-md-none">
                                <hr class="mx-3 my-2">
                            </li>
                            <li class="nav-item d-md-none">
                                <a class="nav-link text-danger" href="<?= SITE_URL ?>logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            <?php endif; ?>

            <!-- MAIN CONTENT -->
            <main class="<?= isLoggedIn() ? 'col-md-9 ms-sm-auto col-lg-10 px-md-4' : 'col-12' ?>">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'info' ?> alert-dismissible fade show mt-3" role="alert">
                        <i class="bi bi-<?= $_SESSION['msg_type'] == 'success' ? 'check-circle' : ($_SESSION['msg_type'] == 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                        <?= $_SESSION['message'];
                        unset($_SESSION['message'], $_SESSION['msg_type']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </main>

        </div>
    </div>

<!-- =========================
     FOOTER (PROFESSIONAL & MODERN)
========================= -->
<footer class="bg-dark text-white pt-5 pb-3 mt-auto" style="background-color: #0f172a !important;">
    <div class="container">
        <div class="row g-4">
            
            <!-- About Column -->
            <div class="col-lg-4 col-md-6">
                <h5 class="text-warning fw-bold mb-3">
                    <i class="bi bi-shop me-2"></i>Poultry Market
                </h5>
                <p class="text-secondary small mb-3" style="line-height: 1.7;">
                    Tanzania's premier digital marketplace connecting broiler farmers directly with buyers. Streamlining poultry trade through real-time tracking, fair pricing, and reliable market links.
                </p>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;"><i class="bi bi-twitter-x"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 ms-auto">
                <h6 class="text-white fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled mb-0 footer-links">
                    <li class="mb-2"><a href="index.php" class="text-secondary text-decoration-none small">Home</a></li>
                    <li class="mb-2"><a href="buyer/browse.php" class="text-secondary text-decoration-none small">Browse Batches</a></li>
                    <li class="mb-2"><a href="register.php" class="text-secondary text-decoration-none small">Join as Farmer</a></li>
                    <li class="mb-2"><a href="login.php" class="text-secondary text-decoration-none small">Account Login</a></li>
                </ul>
            </div>

            <!-- Platform Features -->
            <div class="col-lg-2 col-md-6">
                <h6 class="text-white fw-bold mb-3">System Features</h6>
                <ul class="list-unstyled mb-0 footer-links">
                    <li class="mb-2"><a href="#" class="text-secondary text-decoration-none small">Real-time Age Track</a></li>
                    <li class="mb-2"><a href="#" class="text-secondary text-decoration-none small">Price Calculator</a></li>
                    <li class="mb-2"><a href="#" class="text-secondary text-decoration-none small">Verified Farmers</a></li>
                    <li class="mb-2"><a href="#" class="text-secondary text-decoration-none small">Direct Orders</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3">Contact Us</h6>
                <ul class="list-unstyled text-secondary small mb-0">
                    <li class="mb-2 d-flex align-items-center">
                        <i class="bi bi-geo-alt text-warning me-2 fs-6"></i>
                        Dar es Salaam, Tanzania
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="bi bi-envelope text-warning me-2 fs-6"></i>
                        info@poultrymarket.co.tz
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="bi bi-telephone text-warning me-2 fs-6"></i>
                        +255 619 925 930
                    </li>
                    <li class="d-flex align-items-center">
                        <i class="bi bi-clock text-warning me-2 fs-6"></i>
                        Mon - Sat: 8:00 AM - 6:00 PM
                    </li>
                </ul>
            </div>

        </div>

        <hr class="my-4 border-secondary opacity-25">

        <!-- Bottom Bar -->
        <div class="row align-items-center small text-secondary">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                &copy; <?= date('Y') ?> <strong class="text-white">Poultry Market Link System</strong>. All rights reserved.
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span>Built in Tanzania</span>
            </div>
        </div>
    </div>
</footer>

<!-- Hover Animation CSS for Footer Links -->
<style>
.footer-links a {
    transition: color 0.2s ease, padding-left 0.2s ease;
}
.footer-links a:hover {
    color: #198754 !important;
    padding-left: 5px;
}
</style>

    <!-- ======================================== -->
    <!-- SCRIPTS -->
    <!-- ======================================== -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= SITE_URL ?>assets/js/script.js"></script>
    <script src="<?= SITE_URL ?>assets/js/datatables-config.js"></script>

    <script>
        // ========================================
        // THEME TOGGLE
        // ========================================
        (function() {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const themeLabel = document.getElementById('themeLabel');
            const root = document.documentElement;

            const storedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            let currentTheme = storedTheme || (prefersDark ? 'dark' : 'light');

            function applyTheme(theme) {
                if (theme === 'dark') {
                    root.setAttribute('data-theme', 'dark');
                    if (themeIcon) themeIcon.className = 'bi bi-sun-fill';
                    if (themeLabel) themeLabel.textContent = 'Dark';
                } else {
                    root.removeAttribute('data-theme');
                    if (themeIcon) themeIcon.className = 'bi bi-moon-fill';
                    if (themeLabel) themeLabel.textContent = 'Light';
                }
                localStorage.setItem('theme', theme);
                currentTheme = theme;
            }

            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    applyTheme(newTheme);
                });
            }

            applyTheme(currentTheme);

            // Listen for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem('theme')) {
                    applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        })();

        // ========================================
        // MOBILE SIDEBAR TOGGLE
        // ========================================
        (function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggleBtn = document.getElementById('sidebarToggle');

            function toggleSidebar() {
                if (sidebar) sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
                document.body.classList.toggle('sidebar-open');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleSidebar);
            }
            if (overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }

            // Close sidebar when a link is clicked (on mobile)
            if (sidebar) {
                sidebar.querySelectorAll('.nav-link').forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 768) {
                            if (sidebar) sidebar.classList.remove('show');
                            if (overlay) overlay.classList.remove('show');
                            document.body.classList.remove('sidebar-open');
                        }
                    });
                });
            }
        })();

        // ========================================
        // AUTO-DISMISS ALERTS
        // ========================================
        (function() {
            setTimeout(function() {
                document.querySelectorAll('.alert').forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        })();

        // ========================================
        // ACTIVE NAV LINK HIGHLIGHTING
        // ========================================
        // (Handled by PHP `isActive()` function above)
    </script>

</body>
</html>