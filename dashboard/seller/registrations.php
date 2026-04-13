<?php
// dashboard/seller/registrations.php — View tournament registrations
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('seller');

$user = currentUser();
$db = getPDO();

// Get registrations for tournaments owned by this seller
$stmt = $db->prepare("
    SELECT tr.*, t.name as tournament_name, t.sport_type, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM tournament_registrations tr
    JOIN tournaments t ON tr.tournament_id = t.id
    JOIN users u ON tr.customer_id = u.id
    WHERE t.seller_id = ?
    ORDER BY tr.created_at DESC
");
$stmt->execute([$user['id']]);
$registrations = $stmt->fetchAll();

layoutHead('Tournament Registrations');
layoutNavbar('seller', $user['name']);
layoutSidebar('seller', 'Registrations');
?>

<div class="mb-4">
    <h4 class="fw-700 m-0">Tournament Registrations</h4>
    <p class="text-muted small">Manage participants and team rosters</p>
</div>

<?php if (empty($registrations)): ?>
    <div class="card p-5 text-center shadow-0 border mt-4">
        <span class="material-icons text-muted mb-3" style="font-size:3rem">people_outline</span>
        <h5>No registrations yet</h5>
        <p class="text-muted">Once athletes sign up for your tournaments, they will appear here.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($registrations as $r): ?>
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-light pt-3 pb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-700 m-0 text-primary"><?php echo h($r['tournament_name']); ?></h6>
                            <span class="badge rounded-pill bg-light text-muted x-small"><?php echo h($r['sport_type']); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6 border-end">
                                <small class="text-muted d-block text-uppercase x-small fw-700 mb-1">Registrar / Leader</small>
                                <div class="fw-600"><?php echo h($r['customer_name']); ?></div>
                                <div class="text-muted small"><?php echo h($r['customer_phone'] ?: $r['customer_email']); ?></div>
                            </div>
                            <div class="col-md-6 ps-md-3">
                                <small class="text-muted d-block text-uppercase x-small fw-700 mb-1">Registration Date</small>
                                <div class="small fw-600"><?php echo date('d M Y, h:i A', strtotime($r['created_at'])); ?></div>
                                <span class="badge bg-success x-small mt-1">Paid / Confirmed</span>
                            </div>
                        </div>
                        
                        <div class="bg-light p-3 rounded">
                            <small class="text-muted d-block text-uppercase x-small fw-700 mb-2 border-bottom pb-1">Team Roster / Player Details</small>
                            <div style="white-space: pre-line;" class="small text-dark fw-500">
                                <?php echo h($r['player_details'] ?: 'No details provided.'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-end">
                        <button class="btn btn-outline-primary btn-sm shadow-0 py-1" onclick="window.print()"><span class="material-icons fs-6 align-middle me-1">print</span> Export Roster</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php layoutFooter(); ?>
