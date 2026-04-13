<?php
// dashboard/customer/tournament_detail.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Tournament.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin();

$user = currentUser();
$id = (int)($_GET['id'] ?? 0);
$tournamentModel = new Tournament();

$tournament = $tournamentModel->getById($id);
if (!$tournament || !$tournament['is_active']) {
    header('Location: ' . BASE_URL . '/dashboard/customer/browse_tournaments.php');
    exit;
}

$photos = $tournamentModel->getPhotos($id);
$isRegistered = false;

if ($user['role'] === 'customer') {
    $isRegistered = $tournamentModel->isRegistered($id, $user['id']);
}

$registrationError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    verifyCsrf();
    
    // Admins and sellers cannot register
    if ($user['role'] !== 'customer') {
        $registrationError = "Only customers are permitted to register for tournaments.";
    } elseif ($isRegistered) {
        $registrationError = "You are already registered for this tournament.";
    } else {
        // Save phone to user profile if missing
        $phone = trim($_POST['phone'] ?? '');
        if ($phone) {
            $db = getPDO();
            $db->prepare("UPDATE users SET phone = ? WHERE id = ? AND (phone IS NULL OR phone = '')")->execute([$phone, $user['id']]);
        }

        // Stability Fix: Loop through dynamic player names/ages to form a single string
        $playerNames = $_POST['player_names'] ?? [];
        $playerAges  = $_POST['player_ages'] ?? [];
        $detailsArr = [];
        for($i = 0; $i < count($playerNames); $i++) {
            if(!empty($playerNames[$i])) {
                $detailsArr[] = "Player " . ($i+1) . ": " . trim($playerNames[$i]) . " (Age: " . (trim($playerAges[$i]) ?: 'N/A') . ")";
            }
        }
        $playerDetails = implode("\n", $detailsArr);

        $regId = $tournamentModel->registerCustomer($id, $user['id'], $playerDetails);
        header("Location: " . BASE_URL . "/dashboard/customer/tournament_confirm.php?id=$regId");
        exit;
    }
}

layoutHead($tournament['name']);
layoutNavbar($user['role'], $user['name']);
layoutSidebar($user['role'], 'Browse Tournaments');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
    <?php if ($user['role'] === 'customer'): ?>
      <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/dashboard/customer/browse_tournaments.php" class="text-primary text-decoration-none fw-600">Events</a></li>
    <?php else: ?>
      <li class="breadcrumb-item text-muted fw-600"><?php echo ucfirst($user['role']); ?> Preview</li>
    <?php endif; ?>
    <li class="breadcrumb-item active" aria-current="page"><?php echo h($tournament['name']); ?></li>
  </ol>
</nav>

<div class="row g-4 mb-5">
  <!-- Left: Event info -->
  <div class="col-lg-8">
    <div id="eventCarousel" class="carousel slide mb-4 shadow-sm" data-mdb-ride="carousel" style="border-radius:12px; overflow:hidden;">
      <div class="carousel-inner">
        <?php if (!empty($photos)): foreach ($photos as $i => $p): ?>
        <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
          <img src="<?php echo imgUrl($p['photo_url']); ?>" class="d-block w-100" style="object-fit:cover; height:400px; background:#f0f0f0;" alt="Tournament banner">
        </div>
        <?php endforeach; else: ?>
        <div class="d-flex align-items-center justify-content-center bg-light" style="height:400px;">
            <span class="material-icons text-muted" style="font-size:6rem; opacity:0.2;">emoji_events</span>
        </div>
        <?php endif; ?>
      </div>
      <?php if (count($photos) > 1): ?>
      <button class="carousel-control-prev" type="button" style="opacity:1; z-index:100; border:none; background:none;">
        <span class="material-icons bg-dark text-white rounded-circle p-2 shadow-sm" style="pointer-events:none;" aria-hidden="true">chevron_left</span>
      </button>
      <button class="carousel-control-next" type="button" style="opacity:1; z-index:100; border:none; background:none;">
        <span class="material-icons bg-dark text-white rounded-circle p-2 shadow-sm" style="pointer-events:none;" aria-hidden="true">chevron_right</span>
      </button>
      <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="card p-4 mb-4 shadow-none border">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
        <div>
          <h2 class="fw-700 mb-2"><?php echo h($tournament['name']); ?></h2>
          <span class="badge rounded-pill bg-info text-dark px-3 shadow-sm"><?php echo h($tournament['sport_type']); ?></span>
        </div>
      </div>
      
      <div class="d-flex flex-wrap gap-4 mb-4">
        <div class="d-flex align-items-center gap-2 text-muted fw-500">
            <span class="material-icons text-primary" style="font-size:1.5rem;">location_on</span> 
            <div>
                <span class="d-block fw-700 text-dark" style="font-size:0.8rem;">Location</span>
                <?php echo h($tournament['location']); ?>
                <span id="distanceDisplay" class="ms-2 badge bg-success text-white shadow-sm" style="font-size: 0.75rem;"></span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 text-muted fw-500">
          <span class="material-icons text-primary" style="font-size:1.5rem;">event</span> 
          <div>
              <span class="d-block fw-700 text-dark" style="font-size:0.8rem;">Schedule</span>
              <?php echo date('M d', strtotime($tournament['start_date'])); ?> – <?php echo date('M d, Y', strtotime($tournament['end_date'])); ?>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 text-muted fw-500">
          <span class="material-icons text-primary" style="font-size:1.5rem;">timer</span> 
          <div>
              <span class="d-block fw-700 text-dark" style="font-size:0.8rem;">Registration Deadline</span>
              <span class="<?php echo strtotime($tournament['registration_deadline'] ?: $tournament['start_date']) < time() ? 'text-danger fw-700' : ''; ?>">
                  <?php echo date('M d, Y', strtotime($tournament['registration_deadline'] ?: $tournament['start_date'])); ?>
              </span>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 text-muted fw-500">
          <span class="material-icons text-primary" style="font-size:1.5rem;">payments</span> 
          <div>
              <span class="d-block fw-700 text-dark" style="font-size:0.8rem;">Registration Fee</span>
              <span class="text-success fw-700">₹<?php echo number_format($tournament['registration_fee'], 2); ?></span>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 text-muted fw-500">
          <span class="material-icons text-primary" style="font-size:1.5rem;">groups</span> 
          <div>
              <span class="d-block fw-700 text-dark" style="font-size:0.8rem;">Team Size</span>
              <?php echo h($tournament['team_size']); ?> Players
          </div>
        </div>
      </div>
      
      <?php if ($tournament['description']): ?>
        <h5 class="fw-700 mb-2 mt-4">Description & Rules</h5>
        <div class="bg-light p-3 rounded text-muted mb-4" style="line-height:1.6;"><?php echo nl2br(h($tournament['description'])); ?></div>
      <?php endif; ?>

      <!-- Map Component -->
      <h5 class="fw-700 mb-3 mt-4"><span class="material-icons text-primary align-middle me-2">map</span> Find Us</h5>
      <div style="border-radius:12px; overflow:hidden; border:1px solid #eee;">
        <?php if (!empty($tournament['latitude']) && !empty($tournament['longitude'])): ?>
        <iframe src="https://maps.google.com/maps?q=<?php echo h($tournament['latitude']); ?>,<?php echo h($tournament['longitude']); ?>&output=embed&z=15" width="100%" height="280" style="border:0;" allowfullscreen loading="lazy"></iframe>
        <?php else: ?>
        <iframe src="https://maps.google.com/maps?q=<?php echo urlencode($tournament['name'] . ' ' . $tournament['location']); ?>&output=embed&z=14" width="100%" height="280" style="border:0;" allowfullscreen loading="lazy"></iframe>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: Registration Form -->
  <div class="col-lg-4">
    <div class="card p-4 sticky-top border border-primary shadow-sm" style="top:90px; border-top: 5px solid var(--primary) !important;">
      
      <?php if ($user['role'] !== 'customer'): ?>
          <div class="text-center py-4">
              <span class="material-icons text-primary d-block mx-auto mb-2" style="font-size:3rem">visibility</span>
              <h5 class="fw-700 text-primary">Preview Mode</h5>
              <p class="text-muted small mt-2">You are viewing this tournament exactly as a customer sees it. The registration form is hidden.</p>
          </div>
      <?php elseif ($isRegistered): ?>
          <div class="text-center py-4">
              <span class="material-icons text-success d-block mx-auto mb-2" style="font-size:3rem">check_circle</span>
              <h5 class="fw-700 text-success">You're In!</h5>
              <p class="text-muted small mt-2">You have successfully registered for this tournament. Check your Dashboard for updates.</p>
              <a href="<?php echo BASE_URL; ?>/dashboard/customer/index.php" class="btn btn-outline-success border-2 shadow-0 mt-2 fw-600">Go to Dashboard</a>
          </div>
      <?php elseif (strtotime($tournament['registration_deadline'] ?: $tournament['start_date']) < time()): ?>
          <div class="text-center py-4">
              <span class="material-icons text-danger d-block mx-auto mb-2" style="font-size:3rem">error</span>
              <h5 class="fw-700 text-danger">Registration Closed</h5>
              <p class="text-muted small mt-2">The deadline to sign up for this tournament has passed.</p>
          </div>
      <?php else: ?>
          <h4 class="fw-700 mb-2 text-center text-primary">Join Tournament</h4>
          <p class="text-muted text-center small mb-4">Submit your details to secure your spot in the bracket.</p>
          
          <?php if ($registrationError): ?><div class="alert alert-danger py-2 small fw-600"><span class="material-icons align-middle fs-6 me-1">error</span><?php echo h($registrationError); ?></div><?php endif; ?>

          <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="register" value="1">

            <div class="mb-3">
                <label class="form-label small fw-600">Athlete / Team Leader Name</label>
                <input type="text" class="form-control bg-light" value="<?php echo h($user['name']); ?>" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-600">Contact Email</label>
                <input type="email" class="form-control bg-light" value="<?php echo h($user['email']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-600">Contact Phone <span class="text-danger">*</span></label>
                <input type="tel" name="phone" class="form-control" placeholder="We will text you updates" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label small fw-700 text-uppercase letter-spacing-1 mb-3">Team / Player Roster (<?php echo (int)$tournament['team_size']; ?> Required)</label>
                <div class="p-3 border rounded bg-light">
                    <?php for($i = 1; $i <= (int)$tournament['team_size']; $i++): ?>
                    <div class="row g-2 mb-3 align-items-center">
                        <div class="col-1 text-center small text-muted"><?php echo $i; ?>.</div>
                        <div class="col-7">
                            <input type="text" name="player_names[]" class="form-control form-control-sm" placeholder="Player Name" required>
                        </div>
                        <div class="col-4">
                            <input type="number" name="player_ages[]" class="form-control form-control-sm" placeholder="Age" required>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="form-text x-small mt-2">Please ensure all player details are accurate for verification.</div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="termsCheck" required>
                <label class="form-check-label small text-muted" for="termsCheck">
                    I agree to follow the tournament rules and referee decisions.
                </label>
            </div>

            <button type="submit" class="btn btn-primary d-block w-100 fw-700 shadow-0 py-3" style="font-size:1.1rem; border-radius:8px;">
                <span class="material-icons align-middle fs-5 me-1">emoji_events</span> Register Now
            </button>
          </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
<?php if (!empty($tournament['latitude']) && !empty($tournament['longitude'])): ?>
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(pos) {
        const tLat = <?php echo floatval($tournament['latitude']); ?>;
        const tLng = <?php echo floatval($tournament['longitude']); ?>;
        const uLat = pos.coords.latitude;
        const uLng = pos.coords.longitude;
        
        const R = 6371; // km
        const dLat = (tLat - uLat) * Math.PI / 180;
        const dLng = (tLng - uLng) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(uLat * Math.PI / 180) * Math.cos(tLat * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c;
        
        const dElem = document.getElementById('distanceDisplay');
        if (dElem) {
            dElem.innerHTML = `<span class="material-icons align-middle" style="font-size:12px;">navigation</span> ${distance.toFixed(1)} km away`;
        }
    });
}
<?php endif; ?>
</script>

<style>
#zoomImg { max-width:100%; max-height:100%; object-fit: contain; }
/* Amazon-style Theater Gallery */
#imageZoomModal { display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); align-items:center; justify-content:center; }
.gallery-close { position:absolute; top:20px; right:20px; color:white; font-size:40px; cursor:pointer; z-index:10001; }
.gallery-nav { position:absolute; top:50%; transform:translateY(-50%); color:white; font-size:50px; cursor:pointer; z-index:10001; background:rgba(0,0,0,0.3); border-radius:50%; width:60px; height:60px; display:flex; align-items:center; justify-content:center; border:none; }
.gallery-nav:hover { background:rgba(255,255,255,0.2); }
.gallery-prev { left:20px; }
.gallery-next { right:20px; }
.gallery-counter { position:absolute; bottom:20px; color:white; font-size:16px; font-weight:600; }
</style>

<!-- Amazon-style Theater Modal -->
<div id="imageZoomModal">
    <span class="gallery-close" onclick="closeGallery()">&times;</span>
    <button class="gallery-nav gallery-prev" onclick="changeGalleryImage(-1)">&#10094;</button>
    <img id="zoomImg" src="">
    <button class="gallery-nav gallery-next" onclick="changeGalleryImage(1)">&#10095;</button>
    <div class="gallery-counter" id="galleryCounter"></div>
</div>

<script>
const eventPhotos = <?php echo json_encode(array_map(fn($p) => imgUrl($p['photo_url']), $photos)); ?>;
let currentGalleryIndex = 0;

function openGallery(index) {
    currentGalleryIndex = index;
    updateGallery();
    document.getElementById('imageZoomModal').style.display = 'flex';
}

function updateGallery() {
    document.getElementById('zoomImg').src = eventPhotos[currentGalleryIndex];
    document.getElementById('galleryCounter').textContent = `${currentGalleryIndex + 1} / ${eventPhotos.length}`;
}

function changeGalleryImage(step) {
    currentGalleryIndex += step;
    if (currentGalleryIndex >= eventPhotos.length) currentGalleryIndex = 0;
    if (currentGalleryIndex < 0) currentGalleryIndex = eventPhotos.length - 1;
    updateGallery();
}

function closeGallery() {
    document.getElementById('imageZoomModal').style.display = 'none';
}

function initLightbox() {
    document.querySelectorAll('.carousel-item img').forEach((img, idx) => {
        img.style.cursor = 'zoom-in';
        img.onclick = () => openGallery(idx);
    });
}
document.addEventListener('DOMContentLoaded', () => {
    initLightbox();
    // Manual arrow fix
    const prevBtn = document.querySelector('.carousel-control-prev');
    const nextBtn = document.querySelector('.carousel-control-next');
    const carouselEl = document.querySelector('#eventCarousel');
    if(prevBtn && nextBtn && carouselEl) {
        const carousel = new mdb.Carousel(carouselEl);
        prevBtn.onclick = (e) => { e.preventDefault(); carousel.prev(); };
        nextBtn.onclick = (e) => { e.preventDefault(); carousel.next(); };
    }
});
</script>

<?php layoutFooter(); ?>
