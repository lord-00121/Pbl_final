<?php
// dashboard/seller/add_venue.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Venue.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('seller');

$user = currentUser();
$venueModel = new Venue();
$error = '';
$sports = ['Cricket','Football','Badminton','Basketball','Tennis','Swimming','Others'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $sport = $_POST['sport_type'] ?? '';
    $loc = trim($_POST['location'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price_per_slot'] ?? 0);
    $dur = '60'; // Defaulted to 60 minutes
    $opStart = $_POST['operating_hours_start'] ?? '06:00';
    $opEnd = $_POST['operating_hours_end'] ?? '22:00';

    if (!$name || !$sport || !$loc || $price == 0) {
        $error = 'Please fill all required fields with valid values.';
    } elseif (!in_array($sport, $sports)) {
        $error = 'Invalid sport type.';
    } elseif (empty($_FILES['photos']['name'][0])) {
        $error = 'Please upload at least one venue photo.';
    } else {
        $venueId = $venueModel->create([
            'seller_id' => $user['id'],
            'name' => $name, 'sport_type' => $sport,
            'location' => $loc, 'city' => $city, 'state' => $state, 'pincode' => $pincode,
            'description' => $desc,
            'price_per_slot' => $price, 'slot_duration' => $dur,
            'operating_hours_start' => $opStart, 'operating_hours_end' => $opEnd,
        ]);

        // Photos upload
        $uploadDir = __DIR__ . '/../../assets/uploads/venues/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $files = $_FILES['photos'];
        $count = 0;
        for ($i = 0; $i < min(count($files['name']), 10); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) continue;
            if ($files['size'][$i] > 5 * 1024 * 1024) continue;
            $fname = 'venue_' . $venueId . '_' . time() . '_' . $i . '.' . $ext;
            move_uploaded_file($files['tmp_name'][$i], $uploadDir . $fname);
            $venueModel->addPhoto($venueId, 'assets/uploads/venues/' . $fname, $count++);
        }

        header('Location: ' . BASE_URL . '/dashboard/seller/venues.php?msg=saved');
        exit;
    }
}

layoutHead('Add Venue');
layoutNavbar('seller', $user['name']);
layoutSidebar('seller', 'My Venues');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-700 m-0">Add New Venue</h4>
    <p class="text-muted small">Fill in your venue details to start receiving bookings</p>
  </div>
  <a href="<?php echo BASE_URL; ?>/dashboard/seller/venues.php" class="btn btn-light shadow-0">← Back</a>
</div>

<?php if ($error): ?><div class="alert alert-danger py-2 small fw-600"><span class="material-icons align-middle fs-6 me-1">error</span> <?php echo h($error); ?></div><?php endif; ?>

<div class="card p-4">
  <form method="POST" enctype="multipart/form-data">
    <?php echo csrfInput(); ?>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label fw-600 small" for="vname">Venue Name <span class="text-danger">*</span></label>
        <input type="text" id="vname" name="name" class="form-control" placeholder="e.g. Green Turf Cricket Ground" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small" for="vsport">Sport Type <span class="text-danger">*</span></label>
        <select id="vsport" name="sport_type" class="form-select bg-light" required>
          <option value="">Select sport...</option>
          <?php foreach ($sports as $s): ?><option value="<?php echo h($s); ?>"><?php echo h($s); ?></option><?php endforeach; ?>
        </select>
      </div>
      <!-- Map Section -->
      <div class="col-12 mb-3 mt-4">
        <label class="form-label fw-600 small text-primary"><span class="material-icons align-middle fs-6 me-1">pin_drop</span>Pinpoint on Map</label>
        <p class="text-muted small mb-2">Search for a landmark or your city, then drag the marker to your venue's exact location to automatically fill the fields.</p>
        
        <div class="input-group mb-2 shadow-sm border rounded">
            <input type="text" id="mapSearchInput" class="form-control border-0 bg-light" placeholder="Search a landmark, area, or city (e.g., BKC Mumbai)">
            <button class="btn btn-dark shadow-0 px-4" type="button" id="mapSearchBtn"><span class="material-icons align-middle fs-6">search</span></button>
        </div>

        <div id="venueMap" style="height: 350px; width: 100%; border-radius: 8px; border: 1px solid #ddd; z-index: 1;"></div>
      </div>

      <div class="col-12">
        <label class="form-label fw-600 small" for="vloc">Full Location / Address <span class="text-danger">*</span></label>
        <input type="text" id="vloc" name="location" class="form-control" placeholder="123 Stadium Road, Near Park" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small" for="vcity">City <span class="text-danger">*</span></label>
        <input type="text" id="vcity" name="city" class="form-control" placeholder="e.g. Mumbai" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small" for="vstate">State <span class="text-danger">*</span></label>
        <input type="text" id="vstate" name="state" class="form-control" placeholder="e.g. Maharashtra" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small" for="vpincode">Pincode / Zip <span class="text-danger">*</span></label>
        <input type="text" id="vpincode" name="pincode" class="form-control" placeholder="e.g. 400053" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small" for="vprice">Price per Slot (₹) <span class="text-danger">*</span></label>
        <input type="number" id="vprice" name="price_per_slot" class="form-control" placeholder="500" min="1" step="1" required>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-600 small" for="vopstart">Operating Hours: Start</label>
        <input type="time" id="vopstart" name="operating_hours_start" class="form-control" value="06:00">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-600 small" for="vopend">Operating Hours: End</label>
        <input type="time" id="vopend" name="operating_hours_end" class="form-control" value="22:00">
      </div>
      <div class="col-12">
        <label class="form-label fw-600 small" for="vdesc">Description / Caption</label>
        <textarea id="vdesc" name="description" class="form-control bg-light" rows="4" placeholder="Describe your venue, amenities, rules, etc."></textarea>
      </div>
      <div class="col-12">
        <label class="form-label fw-600 small" for="vphotos">Venue Photos (1–10, JPG/PNG, max 5 MB each) <span class="text-danger">*</span> <span class="text-muted fw-normal">(Hold Ctrl/Cmd to select multiple files)</span></label>
        <input type="file" id="vphotos" name="photos[]" class="form-control" multiple="multiple" accept=".jpg,.jpeg,.png" required>
        <div class="d-flex flex-wrap gap-2 mt-3" id="photoPreview"></div>
      </div>
    </div>
    <div class="d-flex gap-3 mt-4">
      <button type="submit" class="btn btn-primary px-4 fw-600 shadow-0">
        <span class="material-icons align-middle fs-6 me-1">save</span> Save Venue
      </button>
      <a href="<?php echo BASE_URL; ?>/dashboard/seller/venues.php" class="btn btn-light shadow-0 fw-600">Cancel</a>
    </div>
  </form>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Map Setup
    let initialLat = 20.5937, initialLng = 78.9629, zoomLevel = 4;
    const map = L.map('venueMap').setView([initialLat, initialLng], zoomLevel);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);
    let marker = L.marker([initialLat, initialLng], {draggable: true}).addTo(map);

    // Auto-locate
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            map.setView([lat, lng], 13);
            marker.setLatLng([lat, lng]);
        });
    }

    // Geocoding logic
    function updateAddress(lat, lng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
        .then(res => res.json())
        .then(data => {
            if(data && data.address) {
                const addr = data.address;
                const locStr = data.display_name || '';
                const city = addr.city || addr.town || addr.village || addr.county || addr.state_district || '';
                const state = addr.state || '';
                const pincode = addr.postcode || '';

                document.getElementById('vloc').value = locStr.substring(0, 200);
                document.getElementById('vcity').value = city;
                document.getElementById('vstate').value = state;
                document.getElementById('vpincode').value = pincode;
            }
        }).catch(err => console.error("Geocoding failed", err));
    }

    marker.on('dragend', function() {
        const pos = marker.getLatLng();
        updateAddress(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateAddress(e.latlng.lat, e.latlng.lng);
    });

    // Handle landmark map search
    const searchBtn = document.getElementById('mapSearchBtn');
    const searchInput = document.getElementById('mapSearchInput');

    function performSearch() {
        const query = searchInput.value.trim();
        if (!query) return;
        
        const btnIcon = searchBtn.innerHTML;
        searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        
        fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(query)}&limit=1`)
        .then(res => res.json())
        .then(data => {
            searchBtn.innerHTML = btnIcon;
            if (data && data.length > 0) {
                const lat = data[0].lat;
                const lon = data[0].lon;
                map.setView([lat, lon], 15);
                marker.setLatLng([lat, lon]);
                updateAddress(lat, lon);
            } else {
                alert("Location not found. Try a broader search term.");
            }
        }).catch(err => {
            console.error("Search failed", err);
            searchBtn.innerHTML = btnIcon;
        });
    }

    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
});

document.getElementById('vphotos').addEventListener('change', function() {
  const preview = document.getElementById('photoPreview');
  preview.innerHTML = '';
  [...this.files].slice(0,10).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.style.cssText = 'width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;';
      preview.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
});
</script>

<?php layoutFooter(); ?>
