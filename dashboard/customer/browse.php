<?php
// dashboard/customer/browse.php — Browse Venues
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Venue.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('customer');

$user = currentUser();
$venueModel = new Venue();

// Handle search filters
$validSorts = ['rating', 'price_asc', 'price_desc'];
$sort = in_array($_GET['sort'] ?? '', $validSorts) ? $_GET['sort'] : 'rating';

// Validate start_time (HH:MM)
$startTime = '';
if (!empty($_GET['start_time']) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $_GET['start_time'])) {
    $startTime = $_GET['start_time'];
}

$filters = [
    'q'          => trim($_GET['q'] ?? ''),
    'sport'      => isset($_GET['sport']) && $_GET['sport'] !== '' ? [trim($_GET['sport'])] : [],
    'sort'       => $sort,
    'start_time' => $startTime,
    'city'       => trim($_GET['city'] ?? ''),
];
$venues = $venueModel->search($filters);
$uniqueCities = $venueModel->getUniqueCities();

$sports = ['Cricket','Football','Badminton','Basketball','Tennis','Swimming','Squash','Table Tennis','Skating','Others'];

layoutHead('Browse Venues');
layoutNavbar('customer', $user['name']);
layoutSidebar('customer', 'Browse Venues');
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card p-4 shadow-sm border-0 border-top border-4 border-primary">
            <h5 class="fw-700 mb-3"><span class="material-icons align-middle text-primary me-2">search</span> Find Your Venue</h5>
            <form method="GET" action="browse.php" class="row g-3">
                <!-- Row 1: Search + Sport -->
                <div class="col-md-6">
                    <label class="form-label small fw-600 text-muted">Search Name or Location</label>
                    <input type="text" name="q" class="form-control bg-light" placeholder="e.g., Andheri, Turf, Arena..." value="<?php echo h($filters['q']); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-600 text-muted">Sport Type</label>
                    <select name="sport" class="form-select bg-light">
                        <option value="">All Sports</option>
                        <?php foreach ($sports as $s): ?>
                            <option value="<?php echo h($s); ?>" <?php echo (in_array($s, $filters['sport'])) ? 'selected' : ''; ?>><?php echo h($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-600 text-muted">City</label>
                    <select name="city" class="form-select bg-light">
                        <option value="">All Cities</option>
                        <?php foreach ($uniqueCities as $c): ?>
                            <option value="<?php echo h($c); ?>" <?php echo $filters['city'] === $c ? 'selected' : ''; ?>><?php echo h($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-600 text-muted">Sort By</label>
                    <select name="sort" class="form-select bg-light">
                        <option value="rating"      <?php echo $sort === 'rating'     ? 'selected' : ''; ?>>Best Rated</option>
                        <option value="price_asc"   <?php echo $sort === 'price_asc'  ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc"  <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
                <!-- Row 2: Start Time + Search button -->
                <div class="col-md-3">
                    <label class="form-label small fw-600 text-muted">Available From (Start Time)</label>
                    <select name="start_time" class="form-select bg-light">
                        <option value="">Any Time</option>
                        <?php
                        $slots = [];
                        for ($h = 5; $h <= 23; $h++) {
                            foreach (['00', '30'] as $m) {
                                $slots[] = sprintf('%02d:%s', $h, $m);
                            }
                        }
                        foreach ($slots as $slot):
                            $label = date('g:i A', strtotime($slot));
                        ?>
                        <option value="<?php echo $slot; ?>" <?php echo $startTime === $slot ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end"></div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 fw-600 shadow-0">Search</button>
                </div>
                <?php if (!empty($filters['q']) || !empty($filters['sport']) || $sort !== 'rating' || !empty($startTime) || !empty($filters['city'])): ?>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="browse.php" class="btn btn-outline-secondary w-100 fw-500">Clear Filters</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <?php if (empty($venues)): ?>
        <div class="col-12 text-center py-5">
            <span class="material-icons text-muted" style="font-size:4rem; opacity:0.3">search_off</span>
            <h5 class="fw-600 mt-3 text-muted">No venues found</h5>
            <p class="text-muted">Try adjusting your filters or search terms to find more venues.</p>
            <a href="browse.php" class="btn btn-outline-primary">Clear Filters</a>
        </div>
    <?php else: ?>
        <?php foreach ($venues as $v): 
            $sportUrl = ($v['primary_photo'] ?? '') ? BASE_URL . '/' . $v['primary_photo'] : '';
            $stars = str_repeat('★', round($v['rating_avg'])) . str_repeat('☆', 5 - round($v['rating_avg']));
        ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow-sm border-0 venue-card" style="transition: transform 0.2s ease, box-shadow 0.2s ease; border-radius:12px; overflow:hidden;">
                    <div style="height: 200px; position:relative; background:#f5f5f5;">
                        <?php if ($sportUrl): ?>
                            <img src="<?php echo h($sportUrl); ?>" style="width:100%; height:100%; object-fit:cover;" alt="Venue">
                        <?php else: ?>
                            <div class="d-flex h-100 align-items-center justify-content-center">
                                <span class="material-icons text-muted" style="font-size:3rem; opacity:0.3">stadium</span>
                            </div>
                        <?php endif; ?>
                        <div style="position:absolute; top:10px; right:10px;">
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-600 shadow-sm"><span class="material-icons align-top" style="font-size:0.9rem">star</span> <?php echo number_format($v['rating_avg'], 1); ?></span>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-700 m-0"><?php echo h($v['name']); ?></h5>
                        </div>
                        <p class="text-muted small fw-500 mb-3">
                            <span class="material-icons text-primary fs-6 align-middle me-1">location_on</span> 
                            <?php echo h($v['location']); ?>
                            <?php if (!empty($v['city'])): echo '<br><span class="ms-4">'.h($v['city']).(!empty($v['state']) ? ', '.h($v['state']) : '').'</span>'; endif; ?>
                        </p>
                        
                        <div class="mt-auto d-flex justify-content-between align-items-end pt-3 border-top">
                            <div>
                                <span class="badge bg-info text-dark rounded-pill fw-600 mb-1"><?php echo h($v['sport_type']); ?></span>
                                <div class="fw-700 text-success fs-5">₹ <?php echo number_format($v['price_per_slot']); ?><span class="text-muted fw-normal small fs-6">/slot</span></div>
                            </div>
                            <a href="venue_detail.php?id=<?php echo $v['id']; ?>" class="btn btn-primary px-3 shadow-0 fw-600" style="border-radius:8px;">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.venue-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; }
</style>

<?php layoutFooter(); ?>
