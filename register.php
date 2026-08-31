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

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone = sanitize($_POST['phone']);
        $location = sanitize($_POST['location']);
        $role = sanitize($_POST['role']);

        // Validation
        if (empty($name)) $errors[] = "Full name is required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
        if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
        if (!in_array($role, ['farmer', 'buyer'])) $role = 'buyer';

        // Check if email already exists
        if (empty($errors)) {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $errors[] = "Email already registered. Please login.";
            }
            $check->close();
        }

        if (empty($errors)) {
            $hashed = hashPassword($password);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, location, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $email, $hashed, $phone, $location, $role);
            if ($stmt->execute()) {
                // 🔍 LOG ACTIVITY
    logActivity($user_id, 'register', "New user registered: $name ($email) as $role");
                $_SESSION['message'] = "Registration successful! Please login.";
                $_SESSION['msg_type'] = "success";
                //logActivity($conn->insert_id, 'register', 'New user registered');
                redirect('login.php');
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = "Register – Poultry Market";
ob_start();
?>
<div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-md-8 col-lg-7 col-xl-6">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <!-- Header with brand -->
                <div class="card-header bg-success text-white text-center py-3 border-0">
                    <h2 class="fw-bold mb-0">
                        <i class="bi bi-egg-fried"></i> Create Account
                    </h2>
                    <p class="mb-0 small">Join the Poultry Market community</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Please fix the following:</strong>
                            <ul class="mb-0 mt-1">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="registerForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i class="bi bi-person"></i> Full Name</label>
                            <input type="text" name="name" class="form-control form-control-lg" 
                                   placeholder="John Doe" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i class="bi bi-envelope"></i> Email</label>
                            <input type="email" name="email" class="form-control form-control-lg" 
                                   placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i class="bi bi-lock"></i> Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control form-control-lg" 
                                       placeholder="Min 6 characters" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <!-- Password strength meter -->
                            <div class="mt-2">
                                <div class="progress" style="height: 4px;">
                                    <div id="passwordStrength" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                </div>
                                <small id="strengthText" class="text-muted">Password strength: <span id="strengthLabel">Weak</span></small>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i class="bi bi-lock-fill"></i> Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control form-control-lg" 
                                   placeholder="Confirm your password" required>
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i class="bi bi-phone"></i> Phone</label>
                            <input type="text" name="phone" class="form-control form-control-lg" 
                                   placeholder="+255 123 456 789" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>

                        <!-- Location -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i class="bi bi-geo-alt"></i> Location</label>
                            <input type="text" name="location" class="form-control form-control-lg" 
                                   placeholder="City / Area" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                        </div>

                        <!-- Role Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><i class="bi bi-person-badge"></i> I want to register as</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="form-check role-card p-3 border rounded-3 <?= (!isset($_POST['role']) || $_POST['role'] === 'buyer') ? 'border-success bg-success bg-opacity-10' : '' ?>">
                                        <input class="form-check-input" type="radio" name="role" value="buyer" id="buyer" 
                                               <?= (!isset($_POST['role']) || $_POST['role'] === 'buyer') ? 'checked' : '' ?>>
                                        <label class="form-check-label w-100 cursor-pointer" for="buyer">
                                            <i class="bi bi-person fs-3 d-block text-primary"></i>
                                            <strong>Buyer</strong>
                                            <div class="small text-muted">I want to buy chickens</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check role-card p-3 border rounded-3 <?= (isset($_POST['role']) && $_POST['role'] === 'farmer') ? 'border-success bg-success bg-opacity-10' : '' ?>">
                                        <input class="form-check-input" type="radio" name="role" value="farmer" id="farmer" 
                                               <?= (isset($_POST['role']) && $_POST['role'] === 'farmer') ? 'checked' : '' ?>>
                                        <label class="form-check-label w-100 cursor-pointer" for="farmer">
                                            <i class="bi bi-person-badge fs-3 d-block text-success"></i>
                                            <strong>Farmer</strong>
                                            <div class="small text-muted">I want to sell chickens</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                            <i class="bi bi-person-plus"></i> Create Account
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center text-muted">
                        Already have an account? <a href="login.php" class="text-decoration-none fw-semibold">Sign in here</a>
                    </p>
                    <p class="text-center text-muted small">
                        <a href="index.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Home</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password visibility toggle
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

// Password strength meter
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthLabel = document.getElementById('strengthLabel');
    let strength = 0;
    let label = 'Weak';
    let color = '#dc3545';

    if (password.length >= 6) strength += 1;
    if (password.length >= 10) strength += 1;
    if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength += 1;
    if (/\d/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;

    if (strength >= 4) { label = 'Strong'; color = '#198754'; }
    else if (strength >= 3) { label = 'Good'; color = '#ffc107'; }
    else if (strength >= 2) { label = 'Fair'; color = '#fd7e14'; }
    else { label = 'Weak'; color = '#dc3545'; }

    const percentage = (strength / 5) * 100;
    strengthBar.style.width = percentage + '%';
    strengthBar.style.backgroundColor = color;
    strengthLabel.textContent = label;
    strengthLabel.style.color = color;
});

// Role card click selection
document.querySelectorAll('.role-card').forEach(card => {
    card.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        radio.checked = true;
        // Update styling
        document.querySelectorAll('.role-card').forEach(c => {
            c.classList.remove('border-success', 'bg-success', 'bg-opacity-10');
        });
        this.classList.add('border-success', 'bg-success', 'bg-opacity-10');
    });
});

// Form validation (client side)
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.querySelector('input[name="confirm_password"]').value;
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match.');
    }
});
</script>

<style>
body {
    background: linear-gradient(135deg, #eef2f7 0%, #dce3ed 100%);
}
[data-theme="dark"] body {
    background: linear-gradient(135deg, #1a1e26 0%, #14181f 100%);
}
.card {
    backdrop-filter: blur(4px);
}
.cursor-pointer {
    cursor: pointer;
}
.role-card {
    transition: all 0.3s ease;
    cursor: pointer;
}
.role-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.role-card input[type="radio"] {
    display: none;
}
.role-card .form-check-label {
    cursor: pointer;
    text-align: center;
}
</style>
<?php
$content = ob_get_clean();
include 'layout.php';
?>