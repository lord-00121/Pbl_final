<?php
// dashboard/seller/edit_venue.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Venue.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('seller');

$user = currentUser();
$venueModel = new Venue();
$id = (int)($_GET['id'] ?? 0);
$venue = $venueModel->getById($id);

if (!$venue || $venue['seller_id'] != $user['id']) {
    header('Location: ' . BASE_URL . '/dashboard/seller/venues.php');
    exit;
}

$existingPhotos = $venueModel->getPhotos($id);
$error = '';
$sports = ['Cricket','Football','Badminton','Basketball','Tennis','Swimming','Others'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'sport_type' => $_POST['sport_type'] ?? '',
        'location' => trim($_POST['location'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'pincode' => trim($_POST['pincode'] ?? ''),
        'latitude' => trim($_POST['latitude'] ?? '') !== '' ? (float)$_POST['latitude'] : null,
        'longitude' => trim($_POST['longitude'] ?? '') !== '' ? (float)$_POST['longitude'] : null,
        'description' => trim($_POST['description'] ?? ''),
        'price_per_slot' => (float)($_POST['price_per_slot'] ?? 0),
        'slot_duration' => '60', // Defaulted to 60 minutes
        'operating_hours_start' => $_POST['operating_hours_start'] ?? '06:00',
        'operating_hours_end' => $_POST['operating_hours_end'] ?? '22:00',
    ];

    if (!$data['name'] || !$data['sport_type'] || !$data['location'] || $data['price_per_slot'] == 0) {
        $error = 'Please fill all required fields.';
    } else {
        $venueModel->update($id, $data, $user['id']);

        // Handle new photos
        if (!empty($_FILES['photos']['name'][0])) {
            $uploadDir = __DIR__ . '/../../assets/uploads/venues/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!empty($_POST['replace_photos'])) $venueModel->deletePhotos($id);
            $order = count($existingPhotos);
            $files = $_FILES['photos'];
            for ($i = 0; $i < min(count($files['name']), 10); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png'])) continue;
                if ($files['size'][$i] > 5 * 1024 * 1024) continue;
                $fname = 'venue_' . $id . '_' . time() . '_' . $i . '.' . $ext;
                move_uploaded_file($files['tmp_name'][$i], $uploadDir . $fname);
                $venueModel->addPhoto($id, 'assets/uploads/venues/' . $fname, $order++);
            }
        }
        header('Location: ' . BASE_URL . '/dashboard/seller/venues.php?msg=saved');
        exit;
    }
}

layoutHead('Edit Venue');
layoutNavbar('seller', $user['name']);
layoutSidebar('seller', 'My Venues');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-700 m-0">Edit Venue</h4>
    <p class="text-muted small"><?php echo h($venue['name']); ?></p>
  </div>
  <a href="<?php echo BASE_URL; ?>/dashboard/seller/venues.php" class="btn btn-light shadow-0">← Back</a>
</div>

<?php if ($error): ?><div class="alert alert-danger py-2 small fw-600"><span class="material-icons align-middle fs-6 me-1">error</span> <?php echo h($error); ?></div><?php endif; ?>

<div class="card p-4">
  <form method="POST" enctype="multipart/form-data">
    <?php echo csrfInput(); ?>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label fw-600 small">Venue Name *</label>
        <input type="text" name="name" class="form-control" value="<?php echo h($venue['name']); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">Sport Type *</label>
        <select name="sport_type" class="form-select bg-light" required>
          <?php foreach ($sports as $s): ?>
          <option value="<?php echo h($s); ?>" <?php echo $venue['sport_type'] === $s ? 'selected' : ''; ?>><?php echo h($s); ?></option>
          <?php endforeach; ?>
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
        <input type="hidden" name="latitude" id="vlat" value="<?php echo h($venue['latitude'] ?? ''); ?>">
        <input type="hidden" name="longitude" id="vlng" value="<?php echo h($venue['longitude'] ?? ''); ?>">
      </div>

      <div class="col-12">
        <label class="form-label fw-600 small">Full Location / Address *</label>
        <input type="text" id="vloc" name="location" class="form-control" value="<?php echo h($venue['location']); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">City *</label>
        <input type="text" id="vcity" name="city" class="form-control" value="<?php echo h($venue['city'] ?? ''); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">State *</label>
        <input type="text" id="vstate" name="state" class="form-control" value="<?php echo h($venue['state'] ?? ''); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">Pincode / Zip *</label>
        <input type="text" id="vpincode" name="pincode" class="form-control" value="<?php echo h($venue['pincode'] ?? ''); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">Price per Slot (₹) *</label>
        <input type="number" name="price_per_slot" class="form-control" value="<?php echo h($venue['price_per_slot']); ?>" min="1" required>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-600 small">Operating Start</label>
        <input type="time" name="operating_hours_start" class="form-control" value="<?php echo h(substr($venue['operating_hours_start'], 0, 5)); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-600 small">Operating End</label>
        <input type="time" name="operating_hours_end" class="form-control" value="<?php echo h(substr($venue['operating_hours_end'], 0, 5)); ?>">
      </div>
      <div class="col-12">
        <label class="form-label fw-600 small">Description</label>
        <textarea name="description" class="form-control bg-light" rows="4"><?php echo h($venue['description']); ?></textarea>
      </div>

      <!-- Existing Photos -->
      <?php if (!empty($existingPhotos)): ?>
      <div class="col-12 border-top pt-3 mt-3">
        <label class="form-label fw-600 small">Current Photos</label>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($existingPhotos as $p): ?>
            <img src="<?php echo BASE_URL . '/' . h($p['photo_url']); ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;" alt="Venue photo">
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="col-12">
        <label class="form-label fw-600 small">Add New Photos (optional, max 5 MB each) <span class="text-muted fw-normal">(Hold Ctrl/Cmd to select multiple files)</span></label>
        <input type="file" name="photos[]" class="form-control" multiple="multiple" accept=".jpg,.jpeg,.png">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="replace_photos" id="replacePhotos">
          <label class="form-check-label small" for="replacePhotos">Replace all existing photos instead of adding to them</label>
        </div>
      </div>
    </div>
    
    <div class="d-flex gap-3 mt-4 pt-3 border-top">
      <button type="submit" class="btn btn-primary px-4 fw-600 shadow-0"><span class="material-icons align-middle fs-6 me-1">save</span> Update Venue</button>
      <a href="<?php echo BASE_URL; ?>/dashboard/seller/venues.php" class="btn btn-light shadow-0 fw-600">Cancel</a>
    </div>
  </form>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let initialLat = document.getElementById('vlat').value ? parseFloat(document.getElementById('vlat').value) : 20.5937;
    let initialLng = document.getElementById('vlng').value ? parseFloat(document.getElementById('vlng').value) : 78.9629;
    let zoomLevel = document.getElementById('vlat').value ? 15 : 4;
    const map = L.map('venueMap').setView([initialLat, initialLng], zoomLevel);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);
    let marker = L.marker([initialLat, initialLng], {draggable: true}).addTo(map);

    // Try finding the existing venue location strings if geo API returns something
    let existingQuery = document.getElementById('vloc').value;
    if (existingQuery && !document.getElementById('vlat').value) {
        fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(existingQuery)}&limit=1`)
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                map.setView([data[0].lat, data[0].lon], 14);
                marker.setLatLng([data[0].lat, data[0].lon]);
            } else if (navigator.geolocation) {
                // fallback to user location
                navigator.geolocation.getCurrentPosition(function(pos) {
                    map.setView([pos.coords.latitude, pos.coords.longitude], 13);
                    marker.setLatLng([pos.coords.latitude, pos.coords.longitude]);
                });
            }
        });
    }

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
                document.getElementById('vlat').value = lat;
                document.getElementById('vlng').value = lng;
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
</script>

<?php layoutFooter(); ?>
