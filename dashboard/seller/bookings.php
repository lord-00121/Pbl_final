<?php
// dashboard/seller/bookings.php — View venue bookings
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('seller');

$user = currentUser();
$db = getPDO();

// Get bookings for venues owned by this seller
$stmt = $db->prepare("
    SELECT b.*, v.name as venue_name, v.sport_type, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    JOIN users u ON b.customer_id = u.id
    WHERE v.seller_id = ?
    ORDER BY b.slot_date DESC, b.slot_start DESC
");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

layoutHead('Manage Bookings');
layoutNavbar('seller', $user['name']);
layoutSidebar('seller', 'Manage Bookings');
?>

<div class="mb-4">
    <h4 class="fw-700 m-0">Venue Bookings</h4>
    <p class="text-muted small">Manage reservations for your sports facilities</p>
</div>

<?php if (empty($bookings)): ?>
    <div class="card p-5 text-center shadow-0 border mt-4">
        <span class="material-icons text-muted mb-3" style="font-size:3rem">event_busy</span>
        <h5>No bookings found</h5>
        <p class="text-muted">Once customers book your slots, they will appear here.</p>
    </div>
<?php else: ?>
    <div class="card p-0 shadow-sm border-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Venue & Sport</th>
                        <th>Customer</th>
                        <th>Date & Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-600"><?php echo h($b['venue_name']); ?></div>
                                <span class="badge rounded-pill bg-info text-dark x-small"><?php echo h($b['sport_type']); ?></span>
                            </td>
                            <td>
                                <div class="fw-500 small"><?php echo h($b['customer_name']); ?></div>
                                <div class="text-muted x-small"><?php echo h($b['customer_phone'] ?: $b['customer_email']); ?></div>
                            </td>
                            <td>
                                <div class="small fw-600"><?php echo date('d M Y', strtotime($b['slot_date'])); ?></div>
                                <div class="text-muted small"><?php echo date('h:i A', strtotime($b['slot_start'])); ?> - <?php echo date('h:i A', strtotime($b['slot_end'])); ?></div>
                            </td>
                            <td class="fw-600">₹<?php echo number_format($b['total_price']); ?></td>
                            <td>
                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <span class="badge bg-success small">Confirmed</span>
                                <?php elseif ($b['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark small">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-danger small"><?php echo ucfirst($b['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-light btn-sm shadow-0" title="Contact"><span class="material-icons align-middle fs-6">mail</span></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php layoutFooter(); ?>
