<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect(match(getUserRole()) {
        'admin' => 'admin/dashboard.php',
        'farmer' => 'farmer/dashboard.php',
        default => 'buyer/dashboard.php'
    });
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (verifyPassword($password, $row['password'])) {
                // Security: Regenerate session ID to prevent Session Fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $row['id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];

                 // 🔍 LOG ACTIVITY
    logActivity($row['id'], 'login', "User logged in from IP: " . $_SERVER['REMOTE_ADDR']);

                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    // Security: Added HttpOnly flag to cookie
                    setcookie('remember_token', $token, [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }

                redirect(match($row['role']) {
                    'admin' => 'admin/dashboard.php',
                    'farmer' => 'farmer/dashboard.php',
                    default => 'buyer/dashboard.php'
                });
            } else {
                // Security: Generic error message to prevent enumeration
                $error = 'Invalid email or password.';
            }
        } else {
            // Security: Generic error message to prevent enumeration
            $error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}

$page_title = "Login – Poultry Market";
ob_start();
?>

<!-- =========================
     LOGIN FORM SECTION
========================= -->
<div class="container d-flex align-items-center justify-content-center py-5" style="min-height: 80vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">

                <!-- Card Header -->
                <div class="card-header bg-success text-white text-center py-4 border-0">
                    <h3 class="fw-bold mb-1">
                        <i class="bi bi-egg-fried me-2"></i> Poultry Market
                    </h3>
                    <p class="mb-0 small opacity-75">Sign in to your account</p>
                </div>

                <!-- Card Body -->
                <div class="card-body p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-envelope me-1"></i> Email Address
                            </label>
                            <!-- Security Fix: Removed value auto-fill to protect user details -->
                            <input type="email" name="email" class="form-control form-control-lg" 
                                   placeholder="your@email.com" required autofocus autocomplete="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-lock me-1"></i> Password
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control form-control-lg border-end-0" 
                                       placeholder="••••••••" required autocomplete="">
                                <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label small" for="remember">Remember me</label>
                            </div>
                            <a href="#" class="text-success text-decoration-none small fw-semibold">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn btn-success custom-theme-btn btn-lg w-100 fw-bold py-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>

                    <hr class="my-4 opacity-25">

                    <p class="text-center text-muted mb-2">
                        Don't have an account? <a href="register.php" class="text-success text-decoration-none fw-bold">Register here</a>
                    </p>
                    <p class="text-center text-muted small mb-0">
                        <a href="index.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Home</a>
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordField = document.getElementById('password');
    const icon = this.querySelector('i');
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        passwordField.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>

<style>
/* Custom Green Theme Button */
.custom-theme-btn {
    background-color: #198754 !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 50px !important;
    transition: all 0.25s ease-in-out !important;
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3) !important;
}

.custom-theme-btn:hover {
    background-color: #146c43 !important;
    box-shadow: 0 6px 20px rgba(25, 135, 84, 0.45) !important;
    transform: translateY(-2px);
}
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>