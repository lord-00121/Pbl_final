<?php
// dashboard/seller/index.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Venue.php';
require_once __DIR__ . '/../../models/Revenue.php';
require_once __DIR__ . '/../../models/Booking.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('seller');

$user = currentUser();

// Fetch live metrics
$venueModel = new Venue();
$revenueModel = new Revenue();
$bookingModel = new Booking();

$myVenues = $venueModel->getBySeller($user['id']);
$activeVenuesCount = count(array_filter($myVenues, fn($v) => $v['is_active'] == 1));

$revSummary = $revenueModel->getSummary($user['id']);
$recentBookings = $revenueModel->getRecentTransactions($user['id'], 5);

layoutHead('Seller Dashboard');
layoutNavbar('seller', $user['name']);
layoutSidebar('seller', 'Dashboard');
?>

<div class="mb-4">
    <h3 class="fw-700 m-0">🏟️ Welcome back, <?php echo h(explode(' ', $user['name'])[0]); ?>!</h3>
    <p class="text-muted small">Here's what's happening with your sports venues today.</p>
</div>

<div class="row mt-4 g-4 mb-5">
    <!-- Active Venues Metric -->
    <div class="col-md-4">
        <div class="card p-4 h-100 shadow-sm border-0 border-start border-4 border-primary" style="border-radius:12px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="mb-0 text-muted small text-uppercase fw-600">Active Venues</p>
                <span class="material-icons text-primary bg-light p-2 rounded-circle">stadium</span>
            </div>
            <h2 class="fw-700 text-dark mb-0"><?php echo $activeVenuesCount; ?></h2>
            <div class="mt-2 text-muted" style="font-size:0.8rem;">Out of <?php echo count($myVenues); ?> total listed</div>
        </div>
    </div>
    
    <!-- Monthly Revenue Metric -->
    <div class="col-md-4">
        <div class="card p-4 h-100 shadow-sm border-0 border-start border-4 border-success" style="border-radius:12px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="mb-0 text-muted small text-uppercase fw-600">Monthly Revenue</p>
                <span class="material-icons text-success bg-light p-2 rounded-circle">payments</span>
            </div>
            <h2 class="fw-700 text-dark mb-0">₹ <span class="text-success"><?php echo number_format($revSummary['month'] ?? 0); ?></span></h2>
            <div class="mt-2 text-muted" style="font-size:0.8rem;">Current month total</div>
        </div>
    </div>

    <!-- All-Time Revenue Metric -->
    <div class="col-md-4">
        <div class="card p-4 h-100 shadow-sm border-0 border-start border-4 border-warning" style="border-radius:12px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="mb-0 text-muted small text-uppercase fw-600">Total Earrings</p>
                <span class="material-icons text-warning bg-light p-2 rounded-circle">account_balance</span>
            </div>
            <h2 class="fw-700 text-dark mb-0">₹ <span class="text-warning"><?php echo number_format($revSummary['alltime'] ?? 0); ?></span></h2>
            <div class="mt-2 text-muted" style="font-size:0.8rem;">Since you joined</div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0" style="border-radius:12px;">
    <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
        <h6 class="fw-700 m-0"><span class="material-icons text-primary align-middle me-1">bolt</span> Recent Booking Activity</h6>
        <a href="<?php echo BASE_URL; ?>/dashboard/seller/revenue.php" class="btn btn-light btn-sm shadow-0 fw-600">View All</a>
    </div>
    <div class="card-body p-4">
        <?php if(empty($recentBookings)): ?>
            <div class="text-center py-4">
                <span class="material-icons text-muted opacity-25" style="font-size:3rem;">receipt_long</span>
                <p class="text-muted mt-2 fw-500">No booking activity yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0">
                    <thead class="bg-light rounded text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3 fw-600 rounded-start">Date</th>
                            <th class="fw-600">Customer</th>
                            <th class="fw-600">Venue</th>
                            <th class="pe-3 fw-600 text-end rounded-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentBookings as $b): ?>
                        <tr class="border-bottom">
                            <td class="ps-3 py-3">
                                <div class="fw-600 text-dark"><?php echo date('M d', strtotime($b['slot_date'])); ?></div>
                                <div class="text-muted small"><?php echo date('h:i A', strtotime($b['recorded_at'])); ?></div>
                            </td>
                            <td>
                                <span class="fw-600"><?php echo h(explode(' ', $b['customer_name'])[0]); ?></span>
                            </td>
                            <td>
                                <div class="badge bg-light text-dark fw-600 border"><?php echo h($b['venue_name']); ?></div>
                            </td>
                            <td class="pe-3 text-end py-3">
                                <span class="fw-700 text-success fs-6">+ ₹<?php echo number_format($b['amount']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php layoutFooter(); ?>
