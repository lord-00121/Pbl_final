<?php
// dashboard/seller/tournaments.php — Manage tournaments
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('seller');

$user = currentUser();
$db = getPDO();

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("UPDATE tournaments SET is_deleted = 1, is_active = 0 WHERE id = ? AND seller_id = ?");
    $stmt->execute([$id, $user['id']]);
    header('Location: tournaments.php?deleted=1');
    exit;
}

// Handle deletion (POST/CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tournament'])) {
    verifyCsrf();
    $id = (int)$_POST['tournament_id'];
    $stmt = $db->prepare("UPDATE tournaments SET is_deleted = 1, is_active = 0 WHERE id = ? AND seller_id = ?");
    $stmt->execute([$id, $user['id']]);
    header('Location: tournaments.php?msg=deleted');
    exit;
}

$stmt = $db->prepare("SELECT * FROM tournaments WHERE seller_id = ? AND is_deleted = 0 ORDER BY start_date DESC");
$stmt->execute([$user['id']]);
$tournaments = $stmt->fetchAll();
$msg = $_GET['msg'] ?? '';

layoutHead('Tournaments');
layoutNavbar('seller', $user['name']);
layoutSidebar('seller', 'Tournaments');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-700 m-0">My Tournaments</h4>
        <p class="text-muted small">Manage your upcoming sport events</p>
    </div>
    <a href="add_tournament.php" class="btn btn-primary shadow-0">
        <span class="material-icons align-middle fs-6 me-1">add</span> Add Tournament
    </a>
</div>

<?php if ($msg === 'deleted'): ?><div class="alert alert-danger py-2 small fw-600">Tournament successfully removed.</div><?php endif; ?>

<?php if (empty($tournaments)): ?>
    <div class="card p-5 text-center">
        <span class="material-icons text-muted mb-3" style="font-size:3rem">emoji_events</span>
        <h5>No tournaments hosted yet</h5>
        <p class="text-muted">Host a tournament to boost engagement and revenue.</p>
        <div><a href="add_tournament.php" class="btn btn-primary px-4">Host Your First Event</a></div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($tournaments as $t): ?>
            <div class="col-md-6">
                <div class="card h-100 p-0 overflow-hidden border shadow-sm">
                    <div class="p-4">
                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <h5 class="fw-700 m-0"><?php echo h($t['name']); ?></h5>
                            <div class="d-flex gap-1">
                                <?php if (!$t['is_active'] && strtotime($t['end_date']) < time()): ?>
                                    <span class="badge rounded-pill bg-light text-muted border px-3">Ended</span>
                                <?php endif; ?>
                                <span class="badge rounded-pill bg-warning text-dark px-3"><?php echo h($t['sport_type']); ?></span>
                            </div>
                        </div>
                        <p class="text-muted small mb-3"><span class="material-icons fs-6 align-middle me-1">location_on</span> <?php echo h($t['location']); ?></p>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">STARTS</small>
                                <span class="fw-600 small"><?php echo date('d M Y', strtotime($t['start_date'])); ?></span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">ENDS</small>
                                <span class="fw-600 small"><?php echo date('d M Y', strtotime($t['end_date'])); ?></span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="edit_tournament.php?id=<?php echo $t['id']; ?>" class="btn btn-light shadow-0 flex-grow-1">Edit</a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this tournament?')">
                                <?php echo csrfInput(); ?>
                                <input type="hidden" name="delete_tournament" value="1">
                                <input type="hidden" name="tournament_id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-outline-danger shadow-0"><span class="material-icons fs-6">delete</span></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php layoutFooter(); ?>
