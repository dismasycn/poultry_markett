<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Fetch some dynamic content
$total_farmers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetch_row()[0] ?? 0;
$total_batches = $conn->query("SELECT COUNT(*) FROM batches WHERE status = 'available' AND quantity > 0")->fetch_row()[0] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0] ?? 0;
$total_buyers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetch_row()[0] ?? 0;

// Fetch featured batches (latest 6 available)
$featured_batches = $conn->query("
    SELECT b.*, u.name as farmer_name 
    FROM batches b 
    JOIN users u ON b.farmer_id = u.id 
    WHERE b.status = 'available' AND b.quantity > 0 
    ORDER BY b.created_at DESC 
    LIMIT 8
");

// Fetch some farmer testimonials
$testimonials = [
    [
        'name' => 'John Mwangi',
        'role' => 'Broiler Farmer, Arusha',
        'quote' => 'Since joining Poultry Market, I sell my chickens twice as fast. No more losses from delayed sales!',
        'avatar' => 'https://ui-avatars.com/api/?name=John+Mwangi&background=198754&color=fff&size=60'
    ],
    [
        'name' => 'Sarah Kilewo',
        'role' => 'Restaurant Owner, Dar es Salaam',
        'quote' => 'I can now find quality broilers within my budget without wasting time calling multiple farmers.',
        'avatar' => 'https://ui-avatars.com/api/?name=Sarah+Kilewo&background=0d6efd&color=fff&size=60'
    ],
    [
        'name' => 'David Mushi',
        'role' => 'Poultry Dealer, Mbeya',
        'quote' => 'The automatic age tracking helps me plan my orders perfectly. A game-changer for my business.',
        'avatar' => 'https://ui-avatars.com/api/?name=David+Mushi&background=ffc107&color=000&size=60'
    ]
];

$page_title = "Poultry Market – Connect, Trade, Grow";
ob_start();
?>

<!-- =========================
     HERO SECTION (PROFESSIONAL & MODERN)
========================= -->
<section
    class="hero-section"
    style="
        position: relative;
        min-height: 620px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #ffffff;
        overflow: hidden;
        background-image: url('assets/images/bb.jpg');
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
    "
>
    <!-- Dark Gradient Overlay for Maximum Text Contrast -->
    <div
        style="
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(10, 15, 29, 0.75) 0%,
                rgba(15, 23, 42, 0.85) 100%
            );
            z-index: 1;
        "
    ></div>

    <!-- Hero Content -->
    <div
        class="container"
        style="
            position: relative;
            z-index: 2;
            padding: 90px 20px 80px 20px;
        "
    >
        <!-- Top Glassmorphism Tag/Badge -->
        <div class="mb-4 d-flex justify-content-center">
            <span class="hero-top-badge">
                <i class="bi bi-patch-check-fill text-warning fs-6"></i>
                <span>Tanzania's #1 Poultry Marketplace</span>
            </span>
        </div>

        <!-- Main Title -->
        <h1 class="hero-title">
            Poultry Market Link System
        </h1>

        <!-- Subtitle Description -->
        <p class="hero-subtitle">
            The smartest platform connecting broiler farmers and buyers to 
            <span class="highlight-text">connect, trade, and scale</span> their agribusiness.
        </p>

        <!-- CTA Buttons -->
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
            <?php if (isLoggedIn()): ?>
                <a
                    href="<?= match(getUserRole()) {
                        'admin' => 'admin/dashboard.php',
                        'farmer' => 'farmer/dashboard.php',
                        default => 'buyer/dashboard.php'
                    } ?>"
                    class="btn btn-lg px-4 py-3 fw-bold custom-theme-btn hero-btn"
                >
                    <i class="bi bi-speedometer2 me-2"></i>
                    Go to Dashboard
                </a>
            <?php else: ?>
                <a
                    href="register.php"
                    class="btn btn-lg px-4 py-3 fw-bold custom-theme-btn hero-btn"
                >
                    <i class="bi bi-person-plus-fill me-2"></i>
                    Get Started Free
                </a>

                <a
                    href="login.php"
                    class="btn btn-lg px-4 py-3 fw-bold btn-outline-light hero-outline-btn"
                >
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Account Login
                </a>
            <?php endif; ?>
        </div>

        <!-- Value Proposition Feature Pills -->
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <div class="hero-feature-pill">
                <i class="bi bi-check-circle-fill text-success"></i>
                <span>Free Registration</span>
            </div>

            <div class="hero-feature-pill">
                <i class="bi bi-clock-history text-info"></i>
                <span>Real-time Age Tracking</span>
            </div>

            <div class="hero-feature-pill">
                <i class="bi bi-truck text-warning"></i>
                <span>Reliable Delivery Options</span>
            </div>
        </div>
    </div>
</section>


<!-- =========================
     STATS SECTION
========================= -->
<section class="py-5 bg-body-tertiary">
    <div class="container">

        <div class="row text-center g-4">

            <div class="col-md-3 col-6">
                <div class="stat-card p-3 bg-body rounded shadow-sm border">
                    <h2 class="display-5 fw-bold text-success">
                        <?= number_format($total_farmers) ?>
                    </h2>

                    <p class="text-body-secondary mb-0">
                        <i class="bi bi-person-badge"></i>
                        Farmers
                    </p>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="stat-card p-3 bg-body rounded shadow-sm border">
                    <h2 class="display-5 fw-bold text-primary">
                        <?= number_format($total_buyers) ?>
                    </h2>

                    <p class="text-body-secondary mb-0">
                        <i class="bi bi-person"></i>
                        Buyers
                    </p>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="stat-card p-3 bg-body rounded shadow-sm border">
                    <h2 class="display-5 fw-bold text-warning">
                        <?= number_format($total_batches) ?>
                    </h2>

                    <p class="text-body-secondary mb-0">
                        <i class="bi bi-box-seam"></i>
                        Available Batches
                    </p>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="stat-card p-3 bg-body rounded shadow-sm border">
                    <h2 class="display-5 fw-bold text-info">
                        <?= number_format($total_orders) ?>
                    </h2>

                    <p class="text-body-secondary mb-0">
                        <i class="bi bi-cart-check"></i>
                        Orders Placed
                    </p>
                </div>
            </div>

        </div>

    </div>
</section>


<!-- =========================
     FEATURED BATCHES
========================= -->

<?php if ($featured_batches->num_rows > 0): ?>

<section class="py-5 bg-body-tertiary">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h2 class="fw-bold text-body">
                🔥 Featured Available Batches
            </h2>

            <?php if (!isLoggedIn() || getUserRole() == 'buyer'): ?>

                <a
                    href="<?= SITE_URL ?>buyer/browse.php"
                    class="btn text-white custom-theme-btn px-4"
                >
                    View All
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>

            <?php endif; ?>

        </div>

        <div class="row g-4">

            <?php while ($batch = $featured_batches->fetch_assoc()):

                $age = getAgeInDays($batch['hatch_date']);

                $ageBadge = ($age < 21)
                    ? 'bg-success text-white'
                    : (($age <= 28)
                        ? 'bg-warning text-dark'
                        : 'bg-danger text-white');

                $priceInfo = getPriceBreakdown(
                    $batch['price_per_bird'],
                    $batch['hatch_date']
                );

            ?>

            <div class="col-md-4 col-lg-3">

                <div class="card h-100 border shadow-sm rounded-3 overflow-hidden bg-body custom-batch-card">

                    <?php if ($batch['image']): ?>

                        <img
                            src="<?= SITE_URL . $batch['image'] ?>"
                            class="card-img-top"
                            alt="<?= htmlspecialchars($batch['breed']) ?>"
                            style="
                                height:170px;
                                object-fit:cover;
                            "
                        >

                    <?php else: ?>

                        <div
                            class="card-img-top bg-body-secondary d-flex align-items-center justify-content-center text-body-secondary"
                            style="height:170px;"
                        >
                            <i class="bi bi-image fs-1"></i>
                        </div>

                    <?php endif; ?>

                    <div class="card-body p-3 d-flex flex-column justify-content-between">

                        <div>
                            <h5 class="card-title fw-bold text-body mb-2">
                                <?= htmlspecialchars($batch['breed']) ?>
                            </h5>

                            <div class="small text-body-secondary mb-3">

                                <div class="mb-1">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                    <?= htmlspecialchars($batch['location']) ?>
                                </div>

                                <div class="mb-1">
                                    <i class="bi bi-person-circle text-primary me-1"></i>
                                    <?= htmlspecialchars($batch['farmer_name']) ?>
                                </div>

                                <div class="fw-bold text-body fs-6 mt-2">
                                    <i class="bi bi-tag-fill text-success me-1"></i>
                                    Tsh <?= number_format($priceInfo['current'], 2) ?>
                                    <span class="fs-7 fw-normal text-body-secondary">/ bird</span>
                                </div>

                                <?php if ($priceInfo['is_increased']): ?>
                                    <div class="text-danger small mt-1">
                                        (+Tsh <?= number_format($priceInfo['increase'], 2) ?> age fee)
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                        <!-- Badges Section -->
                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            <span class="badge <?= $ageBadge ?> rounded-pill px-2 py-1 fw-semibold">
                                <i class="bi bi-calendar-event me-1"></i><?= $age ?> days old
                            </span>

                            <span class="badge bg-body-tertiary text-body border rounded-pill px-2 py-1">
                                <i class="bi bi-box-seam me-1"></i><?= number_format($batch['quantity']) ?> left
                            </span>
                        </div>

                    </div>

                    <?php if (!isLoggedIn() || getUserRole() == 'buyer'): ?>

                        <div class="card-footer bg-transparent border-top-0 p-3 pt-0">

                            <a
                                href="<?= SITE_URL ?>buyer/order.php?batch_id=<?= $batch['id'] ?>"
                                class="btn text-white w-100 fw-bold py-2 custom-theme-btn"
                            >
                                <i class="bi bi-cart-plus-fill me-1"></i>
                                Order Now
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <?php endwhile; ?>

        </div>

    </div>

</section>

<?php endif; ?>


<!-- =========================
     TESTIMONIALS
========================= -->

<section class="py-5 bg-body-tertiary">

    <div class="container">

        <h2 class="text-center mb-5 fw-bold text-body">
            What Our Users Say
        </h2>

        <div class="row g-4">

            <?php foreach ($testimonials as $t): ?>

                <div class="col-md-4">

                    <div class="card h-100 border shadow-sm bg-body">

                        <div class="card-body p-4">

                            <div class="d-flex align-items-center mb-3">

                                <img
                                    src="<?= $t['avatar'] ?>"
                                    alt="<?= $t['name'] ?>"
                                    class="rounded-circle me-3"
                                    width="50"
                                    height="50"
                                >

                                <div>

                                    <h6 class="mb-0 fw-bold text-body">
                                        <?= $t['name'] ?>
                                    </h6>

                                    <small class="text-body-secondary">
                                        <?= $t['role'] ?>
                                    </small>

                                </div>

                            </div>

                            <p class="card-text text-body-secondary">
                                "<?= $t['quote'] ?>"
                            </p>

                            <div class="text-warning">

                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<!-- =========================
     FOOTER
========================= -->



<!-- =========================
     CUSTOM STYLES
========================= -->

<style>

/* Hero Section Enhancements */
.hero-top-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #ffffff;
    padding: 8px 22px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.hero-title {
    font-size: clamp(2.5rem, 5.5vw, 4.2rem);
    line-height: 1.15;
    font-weight: 800;
    margin-bottom: 22px;
    color: #ffffff;
    text-shadow: 0 4px 18px rgba(0, 0, 0, 0.6);
    letter-spacing: -0.5px;
}

.hero-subtitle {
    max-width: 780px;
    margin: 0 auto 38px auto;
    font-size: clamp(1.1rem, 2vw, 1.35rem);
    line-height: 1.6;
    font-weight: 400;
    color: #f1f5f9;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}

.highlight-text {
    color: #ffc107;
    font-weight: 700;
}

.hero-btn {
    border-radius: 50px !important;
    min-width: 180px;
}

.hero-outline-btn {
    border-radius: 50px !important;
    min-width: 160px;
    backdrop-filter: blur(6px);
    background: rgba(255, 255, 255, 0.08);
    border: 1.5px solid rgba(255, 255, 255, 0.4);
    transition: all 0.25s ease-in-out;
}

.hero-outline-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: #ffffff;
    transform: translateY(-2px);
}

.hero-feature-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: #ffffff;
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 500;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
}

/* Universal Custom Theme Button (#198754) */
.custom-theme-btn {
    background-color: #198754 !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 50px !important;
    transition: all 0.25s ease-in-out !important;
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.35) !important;
}

.custom-theme-btn:hover {
    background-color: #146c43 !important;
    box-shadow: 0 6px 20px rgba(25, 135, 84, 0.5) !important;
    transform: translateY(-2px);
}

/* Stat Cards */
.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
}

/* Batch Cards */
.custom-batch-card {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.custom-batch-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15) !important;
}

.fs-7 {
    font-size: 0.85rem;
}

</style>


<?php
$content = ob_get_clean();
include 'layout.php';
?>