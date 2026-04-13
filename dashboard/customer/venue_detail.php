<?php
// dashboard/customer/venue_detail.php
ob_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Venue.php';
require_once __DIR__ . '/../../models/Booking.php';
require_once __DIR__ . '/../../models/Review.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin();

$user = currentUser();
$id = (int)($_GET['id'] ?? 0);
$venueModel = new Venue();
$venue = $venueModel->getById($id);
if (!$venue || !$venue['is_active']) {
    header('Location: ' . BASE_URL . '/dashboard/customer/browse.php');
    exit;
}
$photos = $venueModel->getPhotos($id);
$reviewModel = new Review();
$reviews = $reviewModel->getByVenue($id);

// Handle booking submission
$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    verifyCsrf();
    $bookingModel = new Booking();
    $slotDate = $_POST['slot_date'] ?? '';
    $slotStart = $_POST['slot_start'] ?? '';
    $slotEnd = $_POST['slot_end'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    // Save phone to user profile if missing
    if ($phone) {
        $db = getPDO();
        $db->prepare("UPDATE users SET phone = ? WHERE id = ? AND (phone IS NULL OR phone = '')")->execute([$phone, $user['id']]);
    }

    if (!$slotDate || !$slotStart || !$slotEnd) {
        $bookingError = 'Please select a date, duration, and time slot.';
    } elseif ($bookingModel->isSlotTaken($id, $slotDate, $slotStart, $slotEnd)) {
        $bookingError = 'Sorry, that time block overlaps with an existing booking. Please choose another.';
    } else {
        // Calculate duration difference in hours to correctly scale the price
        $startSec = strtotime("1970-01-01 $slotStart UTC");
        $endSec = strtotime("1970-01-01 $slotEnd UTC");
        $durationHours = ($endSec - $startSec) / 3600;
        if ($durationHours <= 0) $durationHours = 1; // Fallback
        
        $bookingId = $bookingModel->create([
            'customer_id' => $user['id'],
            'venue_id' => $id,
            'slot_date' => $slotDate,
            'slot_start' => $slotStart,
            'slot_end' => $slotEnd,
            'total_price' => $venue['price_per_slot'] * $durationHours
        ]);
        header("Location: " . BASE_URL . "/dashboard/customer/booking_confirm.php?id=$bookingId");
        exit;
    }
}

layoutHead($venue['name']);
layoutNavbar($user['role'], $user['name']);
layoutSidebar($user['role'], 'Browse Venues');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
    <?php if ($user['role'] === 'customer'): ?>
      <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/dashboard/customer/browse.php" class="text-primary text-decoration-none fw-600">Browse</a></li>
    <?php else: ?>
      <li class="breadcrumb-item text-muted fw-600"><?php echo ucfirst($user['role']); ?> Preview</li>
    <?php endif; ?>
    <li class="breadcrumb-item active" aria-current="page"><?php echo h($venue['name']); ?></li>
  </ol>
</nav>

<div class="row g-4 mb-5">
  <!-- Left: venue info -->
  <div class="col-lg-8">
    <div id="venueCarousel" class="carousel slide mb-4 shadow-sm" data-mdb-ride="carousel" style="border-radius:12px; overflow:hidden;">
      <div class="carousel-inner">
        <?php if (!empty($photos)): foreach ($photos as $i => $p): ?>
        <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
          <img src="<?php echo imgUrl($p['photo_url']); ?>" class="d-block w-100" style="object-fit:cover; height:400px; background:#f0f0f0;" alt="Venue photo">
        </div>
        <?php endforeach; else: ?>
        <div class="d-flex align-items-center justify-content-center bg-light" style="height:400px;">
            <span class="material-icons text-muted" style="font-size:6rem; opacity:0.2;">stadium</span>
        </div>
        <?php endif; ?>
      </div>
      <?php if (count($photos) > 1): ?>
      <button class="carousel-control-prev" type="button" data-mdb-target="#venueCarousel" data-mdb-slide="prev"><span class="carousel-control-prev-icon bg-dark rounded-circle p-2"></span></button>
      <button class="carousel-control-next" type="button" data-mdb-target="#venueCarousel" data-mdb-slide="next"><span class="carousel-control-next-icon bg-dark rounded-circle p-2"></span></button>
      <?php endif; ?>
    </div>

    <!-- Venue Info Details -->
    <div class="card p-4 mb-4 shadow-none border">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
          <h2 class="fw-700 mb-2"><?php echo h($venue['name']); ?></h2>
          <div class="d-flex align-items-center gap-3">
            <span class="badge rounded-pill bg-info text-dark px-3 shadow-sm"><?php echo h($venue['sport_type']); ?></span>
            <div class="text-warning fw-600"><span class="material-icons align-top" style="font-size:1.1rem">star</span> <?php echo number_format($venue['rating_avg'], 1); ?></div>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2 mb-2 text-muted fw-500">
          <span class="material-icons text-primary" style="font-size:1.2rem;">location_on</span> 
          <?php echo h($venue['location']); ?>
          <span id="distanceDisplay" class="ms-2 badge bg-success text-white shadow-sm" style="font-size: 0.75rem;"></span>
      </div>
      <div class="d-flex align-items-center gap-2 mb-4 text-muted fw-500">
        <span class="material-icons text-primary" style="font-size:1.2rem;">schedule</span> Open: <?php echo date('h:i A', strtotime($venue['operating_hours_start'])); ?> – <?php echo date('h:i A', strtotime($venue['operating_hours_end'])); ?>
      </div>
      <?php if ($venue['description']): ?><div class="bg-light p-3 rounded text-muted" style="line-height:1.6;"><?php echo nl2br(h($venue['description'])); ?></div><?php endif; ?>
    </div>

    <!-- Map Component -->
    <div class="card p-4 mb-4 shadow-none border">
      <h5 class="fw-700 mb-3"><span class="material-icons text-primary align-middle me-2">map</span> Find Us</h5>
      <div style="border-radius:12px; overflow:hidden; border:1px solid #eee;">
        <?php if (!empty($venue['latitude']) && !empty($venue['longitude'])): ?>
        <iframe src="https://maps.google.com/maps?q=<?php echo h($venue['latitude']); ?>,<?php echo h($venue['longitude']); ?>&output=embed&z=15" width="100%" height="280" style="border:0;" allowfullscreen loading="lazy"></iframe>
        <?php else: ?>
        <iframe src="https://maps.google.com/maps?q=<?php echo urlencode($venue['name'] . ' ' . $venue['location']); ?>&output=embed&z=14" width="100%" height="280" style="border:0;" allowfullscreen loading="lazy"></iframe>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: Booking Form / Preview Notice -->
  <div class="col-lg-4">
    <?php if ($user['role'] === 'customer'): ?>
        <div class="card p-4 sticky-top border border-primary shadow-sm" style="top:90px; border-top: 5px solid var(--primary) !important;">
          <h4 class="fw-700 mb-2 text-center text-primary">Booking Form</h4>
          <p class="text-muted text-center small mb-4">Complete your reservation below</p>
          
          <?php if ($bookingError): ?><div class="alert alert-danger py-2 small fw-600"><span class="material-icons align-middle fs-6 me-1">error</span><?php echo h($bookingError); ?></div><?php endif; ?>

          <form method="POST" id="bookingForm">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="book" value="1">
            <input type="hidden" name="slot_start" id="hiddenSlotStart">
            <input type="hidden" name="slot_end" id="hiddenSlotEnd">

            <!-- User Details Section -->
            <h6 class="fw-700 small text-uppercase text-muted border-bottom pb-2 mb-3">1. Your Details</h6>
            <div class="mb-3">
                <label class="form-label small fw-600">Full Name</label>
                <input type="text" class="form-control bg-light" value="<?php echo h($user['name']); ?>" readonly>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-600">Phone Number <span class="text-danger">*</span></label>
                <input type="tel" name="phone" class="form-control" placeholder="For booking updates" required>
            </div>

            <!-- Date & Duration Section -->
            <h6 class="fw-700 small text-uppercase text-muted border-bottom pb-2 mb-3">2. Time & Duration</h6>
            <div class="mb-3">
              <label class="form-label small fw-600">Play Date</label>
              <input type="date" id="slotDate" name="slot_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="mb-4">
              <label class="form-label small fw-600">Duration Needed</label>
              <select id="slotDuration" class="form-select">
                  <option value="1.0">1 Hour</option>
                  <option value="1.5">1.5 Hours</option>
                  <option value="2.0">2 Hours</option>
                  <option value="2.5">2.5 Hours</option>
                  <option value="3.0">3 Hours</option>
              </select>
            </div>

            <div class="mb-4" id="slotsWrapper" style="display:none">
              <label class="form-label small fw-600">Available Timeslots</label>
              <div id="slotChips" class="d-flex flex-wrap gap-2"></div>
            </div>

            <!-- Summary & Confirmation -->
            <div id="bookingSummary" style="display:none">
                <h6 class="fw-700 small text-uppercase text-muted border-bottom pb-2 mb-3">3. Confirmation</h6>
                <div class="p-3 bg-light rounded border mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small fw-600">Selected Slot:</span>
                        <span id="summarySlot" class="fw-700 text-dark small">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small fw-600">Duration:</span>
                        <span id="summaryDuration" class="fw-700 text-dark small">—</span>
                    </div>
                    <div class="d-flex justify-content-between border-top mt-2 pt-2">
                        <span class="fw-700 text-dark">Total Price:</span>
                        <span class="fw-700 text-success fs-5">₹ <span id="summaryPrice"></span></span>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">(₹ <?php echo number_format($venue['price_per_slot']); ?> per hour)</small>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="termsCheck" required>
                    <label class="form-check-label small text-muted" for="termsCheck">
                        I agree to the booking terms and confirm these dates.
                    </label>
                </div>

                <button type="submit" id="confirmBtn" class="btn btn-primary d-block w-100 fw-700 shadow-0 py-3" style="font-size:1.1rem; border-radius:8px;">
                    <span class="material-icons align-middle fs-5 me-1">check_circle</span> Book Now
                </button>
            </div>
          </form>
        </div>
    <?php else: ?>
        <div class="card p-4 border shadow-sm rounded-4 text-center mt-3" style="background:var(--light);">
            <span class="material-icons text-primary d-block mx-auto mb-2" style="font-size:3rem">visibility</span>
            <h5 class="fw-700 text-primary">Preview Mode</h5>
            <p class="text-muted small mt-2">You are viewing this venue listing exactly as a regular customer would see it. The interactive booking form is disabled since you are an administrator.</p>
            <button class="btn btn-outline-primary btn-sm mt-3" onclick="window.close()">Close Preview</button>
        </div>
    <?php endif; ?>
  </div>
</div>

<style>
.slot-chip { flex: 1 1 calc(50% - 0.5rem); padding: 8px; text-align: center; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: 0.2s; font-size: 0.85rem; font-weight: 600; background: white; }
.slot-chip:not(.booked):hover { border-color: var(--primary); background: #f0fdf4; color: var(--primary); transform: translateY(-2px); }
.slot-chip.selected { border-color: var(--primary); background: var(--primary); color: white; border-width: 2px; }
.slot-chip.booked { opacity: 0.4; background: #e9ecef; cursor: not-allowed; text-decoration: line-through; border-style: dotted; }
</style>

<script>
const venueId = <?php echo $id; ?>;
const opStart = '<?php echo $venue['operating_hours_start']; ?>';
const opEnd = '<?php echo $venue['operating_hours_end']; ?>';
const pricePerHour = <?php echo $venue['price_per_slot']; ?>;

<?php if (!empty($venue['latitude']) && !empty($venue['longitude'])): ?>
// Calculate distance to venue dynamically
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(pos) {
        const vLat = <?php echo floatval($venue['latitude']); ?>;
        const vLng = <?php echo floatval($venue['longitude']); ?>;
        const uLat = pos.coords.latitude;
        const uLng = pos.coords.longitude;
        
        const R = 6371; // Earth radius in km
        const dLat = (vLat - uLat) * Math.PI / 180;
        const dLng = (vLng - uLng) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(uLat * Math.PI / 180) * Math.cos(vLat * Math.PI / 180) *
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

let currentBooked = [];

const dateInput = document.getElementById('slotDate');
const durationSelect = document.getElementById('slotDuration');

dateInput.addEventListener('change', async function() {
  const date = this.value;
  durationSelect.disabled = false;
  
  try {
      const res = await fetch(`<?php echo BASE_URL; ?>/api/slots.php?venue_id=${venueId}&date=${date}`);
      const data = await res.json();
      currentBooked = data.booked || [];
      renderSlots();
  } catch(e) { console.error('Failed to fetch slots.'); }
});

durationSelect.addEventListener('change', () => {
    // Re-render when duration changes
    if(dateInput.value) renderSlots();
});

function timeStrToInt(timeStr) {
    if (!timeStr) return 0;
    // Handle both HH:MM:SS and HH:MM
    let parts = timeStr.split(':');
    let h = parseInt(parts[0], 10) || 0;
    let m = parseInt(parts[1], 10) || 0;
    return h * 60 + m;
}

function renderSlots() {
  document.getElementById('slotsWrapper').style.display = 'block';
  document.getElementById('bookingSummary').style.display = 'none';
  document.getElementById('hiddenSlotStart').value = '';
  document.getElementById('hiddenSlotEnd').value = '';

  const durationHrs = parseFloat(durationSelect.value);
  const durationMins = durationHrs * 60;
  
  const chips = document.getElementById('slotChips');
  chips.innerHTML = '';
  
  let cur = timeStrToInt(opStart);
  const end = timeStrToInt(opEnd);
  
  const stepMins = 30; // generate a slot every 30 minutes

  while (cur + durationMins <= end) {
    const startMins = cur;
    const curEndMins = cur + durationMins;
    
    const startStr = `${String(Math.floor(startMins/60)).padStart(2,'0')}:${String(startMins%60).padStart(2,'0')}:00`;
    const endStr = `${String(Math.floor(curEndMins/60)).padStart(2,'0')}:${String(curEndMins%60).padStart(2,'0')}:00`;
    
    // Check overlap with ANY existing booking
    let isBooked = false;
    for(let b of currentBooked) {
        let bStart = timeStrToInt(b.slot_start);
        let bEnd = timeStrToInt(b.slot_end);
        // Overlap condition: start < bEnd AND end > bStart
        if(startMins < bEnd && curEndMins > bStart) {
            isBooked = true;
            break;
        }
    }

    const chip = document.createElement('div');
    chip.className = 'slot-chip' + (isBooked ? ' booked' : '');
    
    const formatTime = (mins) => {
        let h = Math.floor(mins/60);
        let m = String(mins%60).padStart(2,'0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h}:${m} ${ampm}`;
    };
    
    chip.textContent = `${formatTime(startMins)} – ${formatTime(curEndMins)}`;
    
    if (!isBooked) {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.slot-chip').forEach(c => c.classList.remove('selected'));
        chip.classList.add('selected');
        document.getElementById('hiddenSlotStart').value = startStr;
        document.getElementById('hiddenSlotEnd').value = endStr;
        
        document.getElementById('summarySlot').textContent = chip.textContent;
        document.getElementById('summaryDuration').textContent = `${durationHrs} ${parseFloat(durationHrs) === 1.0 ? 'Hour' : 'Hours'}`;
        document.getElementById('summaryPrice').textContent = (pricePerHour * durationHrs).toLocaleString();
        
        document.getElementById('bookingSummary').style.display = 'block';
      });
    }
    chips.appendChild(chip);
    cur += stepMins;
  }
  
  if(chips.innerHTML === '') {
      chips.innerHTML = '<span class="text-muted small">No slots long enough.</span>';
  }
}
</script>

<?php layoutFooter(); ?>
