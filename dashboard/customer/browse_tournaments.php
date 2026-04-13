<?php
// dashboard/customer/browse_tournaments.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Tournament.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin();

$user = currentUser();
$model = new Tournament();

$search = trim($_GET['search'] ?? '');
$sportFilter = $_GET['sport'] ?? '';
$sortFilter  = in_array($_GET['sort'] ?? '', ['asc', 'desc']) ? $_GET['sort'] : 'asc';
$dateFilter  = $_GET['date'] ?? ''; // YYYY-MM-DD
$cityFilter  = trim($_GET['city'] ?? '');
// Fetch all active tournaments, filtered server-side
$filters = ['active' => 1];
if ($cityFilter) $filters['city'] = $cityFilter;
$allTournaments = $model->getAll($filters);
$uniqueCities = $model->getUniqueCities();

$filtered = [];
foreach ($allTournaments as $t) {
    if ($sportFilter && $t['sport_type'] !== $sportFilter) continue;
    if ($search && stripos($t['name'], $search) === false && stripos($t['location'], $search) === false) continue;
    if ($dateFilter && $t['start_date'] > $dateFilter) continue; // events that start on or before chosen date
    $filtered[] = $t;
}

// Sort
usort($filtered, function($a, $b) use ($sortFilter) {
    return $sortFilter === 'desc'
        ? strcmp($b['start_date'], $a['start_date'])
        : strcmp($a['start_date'], $b['start_date']);
});

$sports = ['Cricket', 'Football', 'Badminton', 'Basketball', 'Tennis', 'Swimming', 'Others'];

layoutHead('Browse Tournaments');
layoutNavbar($user['role'], $user['name']);
layoutSidebar($user['role'], 'Browse Tournaments');
?>

<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-700 m-0">🏆 Upcoming Tournaments</h3>
        <p class="text-muted small m-0">Compete, challenge yourself, and win prizes!</p>
    </div>
</div>

<div class="card p-3 shadow-sm border-0 mb-4 bg-light rounded-4">
    <form method="GET" class="row g-2 align-items-end">
        <!-- Search -->
        <div class="col-md-4">
            <label class="form-label small fw-600 text-muted mb-1">Search</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><span class="material-icons text-muted fs-6">search</span></span>
                <input type="text" name="search" class="form-control border-start-0 ps-0 fs-6" placeholder="Event name or location..." value="<?php echo h($search); ?>">
            </div>
        </div>
        <!-- Sport -->
        <div class="col-md-2">
            <label class="form-label small fw-600 text-muted mb-1">Sport</label>
            <select name="sport" class="form-select fs-6">
                <option value="">All Sports</option>
                <?php foreach ($sports as $s): ?>
                    <option value="<?php echo h($s); ?>" <?php echo $sportFilter === $s ? 'selected' : ''; ?>><?php echo h($s); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- City -->
        <div class="col-md-2">
            <label class="form-label small fw-600 text-muted mb-1">City</label>
            <select name="city" class="form-select fs-6">
                <option value="">All Cities</option>
                <?php foreach ($uniqueCities as $c): ?>
                    <option value="<?php echo h($c); ?>" <?php echo $cityFilter === $c ? 'selected' : ''; ?>><?php echo h($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Sort -->
        <div class="col-md-2">
            <label class="form-label small fw-600 text-muted mb-1">Sort By Date</label>
            <select name="sort" class="form-select fs-6">
                <option value="asc"  <?php echo $sortFilter === 'asc'  ? 'selected' : ''; ?>>Soonest First</option>
                <option value="desc" <?php echo $sortFilter === 'desc' ? 'selected' : ''; ?>>Latest First</option>
            </select>
        </div>
        <!-- Starting From -->
        <div class="col-md-2">
            <label class="form-label small fw-600 text-muted mb-1">Starting From</label>
            <input type="date" name="date" class="form-control fs-6" value="<?php echo h($dateFilter); ?>">
        </div>
        <!-- Buttons -->
        <div class="col-12 mt-2 d-flex gap-2">
            <button class="btn btn-primary shadow-0 px-4" type="submit">Go</button>
            <?php if ($search || $sportFilter || $dateFilter || $sortFilter !== 'asc' || $cityFilter): ?>
            <a href="<?php echo BASE_URL; ?>/dashboard/customer/browse_tournaments.php" class="btn btn-outline-secondary fw-500">Clear Filters</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="row g-4 mb-5">
    <?php if (empty($filtered)): ?>
        <div class="col-12 py-5 text-center">
            <span class="material-icons text-muted opacity-25" style="font-size: 5rem;">emoji_events</span>
            <h5 class="text-muted mt-3 fw-600">No tournaments found</h5>
            <p class="text-muted small">Try adjusting your filters or search keywords.</p>
        </div>
    <?php else: foreach ($filtered as $t): 
        // Get the first photo or placeholder
        $photos = $model->getPhotos($t['id']);
        $imgSrc = !empty($photos) ? BASE_URL . '/' . $photos[0]['photo_url'] : 'https://placehold.co/600x400/eeeeee/aaaaaa?text=Event';
    ?>
    <div class="col-md-4">
        <a href="<?php echo BASE_URL; ?>/dashboard/customer/tournament_detail.php?id=<?php echo $t['id']; ?>" class="text-decoration-none text-dark">
        <div class="card h-100 shadow-sm border border-light rounded-4 hover-lift overflow-hidden">
            <div style="height:180px; position:relative;">
                <img src="<?php echo h($imgSrc); ?>" class="w-100 h-100" style="object-fit:cover;" alt="Tournament Photo">
                <span class="badge bg-white text-dark shadow-sm position-absolute" style="bottom:10px; right:10px; padding:6px 12px; font-weight:600; border-radius:20px;">
                    <?php echo h($t['sport_type']); ?>
                </span>
            </div>
            
            <div class="p-4 d-flex flex-column h-100">
                <div class="mb-2">
                    <h5 class="fw-700 m-0 text-truncate"><?php echo h($t['name']); ?></h5>
                    <div class="small fw-600 text-muted mt-1">Host: <?php echo h($t['seller_name'] ?: 'Demo Business'); ?></div>
                </div>
                
                <div class="d-flex align-items-center gap-2 mb-2 text-muted small mt-2">
                    <span class="material-icons text-primary" style="font-size:16px;">location_on</span>
                    <span class="text-truncate">
                        <?php echo h($t['location']); ?>
                        <?php if (!empty($t['city'])): echo '<br><span class="ms-4">'.h($t['city']).(!empty($t['state']) ? ', '.h($t['state']) : '').'</span>'; endif; ?>
                    </span>
                </div>
                
                <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                    <span class="material-icons text-primary" style="font-size:16px;">event</span>
                    <?php echo date('M d', strtotime($t['start_date'])); ?> – <?php echo date('M d, Y', strtotime($t['end_date'])); ?>
                </div>

                <div class="mt-auto">
                    <span class="btn btn-primary shadow-0 w-100 fw-600" style="border-radius:8px;">View Details</span>
                </div>
            </div>
        </div>
        </a>
    </div>
    <?php endforeach; endif; ?>
</div>

<style>
.hover-lift { transition: transform 0.2s, box-shadow 0.2s; }
.hover-lift:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; border-color: var(--primary) !important; }
</style>

<?php layoutFooter(); ?>
