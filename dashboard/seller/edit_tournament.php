<?php
// dashboard/seller/edit_tournament.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Tournament.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('seller');

$user = currentUser();
$tournamentModel = new Tournament();
$id = (int)($_GET['id'] ?? 0);
$tournament = $tournamentModel->getById($id);

if (!$tournament || $tournament['seller_id'] != $user['id']) {
    header('Location: ' . BASE_URL . '/dashboard/seller/tournaments.php');
    exit;
}

// Handle single photo deletion
if (isset($_GET['delete_photo'])) {
    $photoId = (int)$_GET['delete_photo'];
    $tournamentModel->deletePhoto($photoId, $user['id']);
    header('Location: ' . BASE_URL . '/dashboard/seller/edit_tournament.php?id=' . $id . '&deleted=1');
    exit;
}

$existingPhotos = $tournamentModel->getPhotos($id);
$error = '';
$sports = ['Cricket','Football','Badminton','Basketball','Tennis','Swimming','Others'];

require_once __DIR__ . '/../../models/Venue.php';
$venueModelObj = new Venue();
$sellerVenues = $venueModelObj->getBySeller($user['id']);

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
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? '',
        'registration_deadline' => !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : null,
        'registration_fee' => (float)($_POST['registration_fee'] ?? 0),
        'team_size' => (int)($_POST['team_size'] ?? 1),
    ];

    if (!$data['name'] || !$data['sport_type'] || !$data['location'] || !$data['start_date'] || !$data['end_date']) {
        $error = 'Please fill all required fields.';
    } elseif (strtotime($data['end_date']) < strtotime($data['start_date'])) {
        $error = 'End Date cannot be before the Start Date.';
    } elseif ($data['registration_deadline'] && strtotime($data['registration_deadline']) > strtotime($data['start_date'])) {
        $error = 'Registration Deadline cannot be after the Start Date.';
    } else {
        $tournamentModel->update($id, $data, $user['id']);

        if (!empty($_FILES['photos']['name'][0])) {
            require_once __DIR__ . '/../../includes/cloudinary_helper.php';
            if (!empty($_POST['replace_photos'])) $tournamentModel->deletePhotos($id);
            $order = count($existingPhotos);
            $files = $_FILES['photos'];
            for ($i = 0; $i < min(count($files['name']), 10); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                
                $fileData = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'name' => $files['name'][$i],
                    'size' => $files['size'][$i],
                    'error' => $files['error'][$i]
                ];

                $secureUrl = uploadToCloudinary($fileData, 'sportify/tournaments');
                if ($secureUrl) {
                    $tournamentModel->addPhoto($id, $secureUrl, $order++);
                }
            }
        }
        header('Location: ' . BASE_URL . '/dashboard/seller/tournaments.php?msg=saved');
        exit;
    }
}

layoutHead('Edit Tournament');
layoutNavbar('seller', $user['name']);
layoutSidebar('seller', 'Tournaments');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-700 m-0">Edit Tournament</h4>
    <p class="text-muted small"><?php echo h($tournament['name']); ?></p>
  </div>
  <a href="<?php echo BASE_URL; ?>/dashboard/seller/tournaments.php" class="btn btn-light shadow-0">← Back</a>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger py-2 small fw-600"><span class="material-icons align-middle fs-6 me-1">error</span> <?php echo h($error); ?></div>
<?php endif; ?>

<div class="card p-4 shadow-sm border-0">
  <form method="POST" enctype="multipart/form-data">
    <?php echo csrfInput(); ?>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label fw-600 small">Tournament Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="<?php echo h($tournament['name']); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">Sport Type <span class="text-danger">*</span></label>
        <select name="sport_type" class="form-select bg-light" required>
          <?php foreach ($sports as $s): ?>
            <option value="<?php echo h($s); ?>" <?php echo $tournament['sport_type'] === $s ? 'selected' : ''; ?>><?php echo h($s); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <!-- Address Section with Auto-fill option -->
      <div class="col-12 mt-4">
          <h6 class="fw-700 text-uppercase small text-muted border-bottom pb-2 mb-3">Event Location</h6>
          <?php if (!empty($sellerVenues)): ?>
          <div class="mb-3 bg-light p-3 rounded border">
              <label class="form-label fw-600 small text-primary">Autofill from your venues?</label>
              <select id="venueAutoFill" class="form-select border-primary" style="background:#fff;">
                  <option value="">-- Choose a venue to copy its address --</option>
                  <?php foreach($sellerVenues as $sv): ?>
                      <option value="<?php echo h($sv['id']); ?>" data-loc="<?php echo h($sv['location']); ?>" data-city="<?php echo h($sv['city'] ?? ''); ?>" data-state="<?php echo h($sv['state'] ?? ''); ?>" data-pin="<?php echo h($sv['pincode'] ?? ''); ?>" data-lat="<?php echo h($sv['latitude'] ?? ''); ?>" data-lng="<?php echo h($sv['longitude'] ?? ''); ?>">
                          <?php echo h($sv['name'] . ' - ' . $sv['location']); ?>
                      </option>
                  <?php endforeach; ?>
              </select>
          </div>
          <?php endif; ?>
      </div>

      <div class="col-12">
        <label class="form-label fw-600 small">Location / Venue Name & Address <span class="text-danger">*</span></label>
        <input type="text" id="tloc" name="location" class="form-control" value="<?php echo h($tournament['location']); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">City <span class="text-danger">*</span></label>
        <input type="text" id="tcity" name="city" class="form-control" value="<?php echo h($tournament['city'] ?? ''); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">State <span class="text-danger">*</span></label>
        <input type="text" id="tstate" name="state" class="form-control" value="<?php echo h($tournament['state'] ?? ''); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">Pincode <span class="text-danger">*</span></label>
        <input type="text" id="tpin" name="pincode" class="form-control" value="<?php echo h($tournament['pincode'] ?? ''); ?>" required>
        <input type="hidden" id="tlat" name="latitude" value="<?php echo h($tournament['latitude'] ?? ''); ?>">
        <input type="hidden" id="tlng" name="longitude" value="<?php echo h($tournament['longitude'] ?? ''); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">Start Date <span class="text-danger">*</span></label>
        <input type="date" name="start_date" class="form-control bg-light" value="<?php echo h($tournament['start_date']); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">End Date <span class="text-danger">*</span></label>
        <input type="date" name="end_date" class="form-control bg-light" value="<?php echo h($tournament['end_date']); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-600 small">Registration Deadline</label>
        <input type="date" name="registration_deadline" class="form-control bg-light" value="<?php echo h($tournament['registration_deadline'] ?? ''); ?>">
      </div>
      <div class="col-md-6 mt-3">
        <label class="form-label fw-600 small">Registration Fee (₹) <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0 text-muted small">₹</span>
          <input type="number" name="registration_fee" class="form-control border-start-0 ps-0" value="<?php echo h($tournament['registration_fee'] ?? '0.00'); ?>" step="0.01" min="0" required>
        </div>
      </div>
      <div class="col-md-6 mt-3">
        <label class="form-label fw-600 small">Team Size (Players) <span class="text-danger">*</span></label>
        <input type="number" name="team_size" class="form-control" value="<?php echo h($tournament['team_size'] ?? '1'); ?>" min="1" required>
      </div>
      <div class="col-12">
        <label class="form-label fw-600 small">Description & Rules</label>
        <textarea name="description" class="form-control bg-light" rows="4"><?php echo h($tournament['description'] ?? ''); ?></textarea>
      </div>

      <?php if (!empty($existingPhotos)): ?>
      <div class="col-12 border-top pt-3 mt-3">
        <label class="form-label fw-600 small">Current Promotional Photos</label>
        <div class="d-flex flex-wrap gap-3">
          <?php foreach ($existingPhotos as $p): ?>
            <div class="position-relative">
              <img src="<?php echo imgUrl($p['photo_url']); ?>" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;" alt="Tournament photo">
              <a href="?id=<?php echo $id; ?>&delete_photo=<?php echo $p['id']; ?>" 
                 class="btn btn-danger btn-floating btn-sm position-absolute top-0 end-0 m-1 shadow-sm" 
                 onclick="return confirm('Delete this photo?')"
                 title="Delete this photo">
                <span class="material-icons" style="font-size:16px; line-height:30px;">close</span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="col-12">
        <label class="form-label fw-600 small">Add / Replace Photos (JPG/PNG, max 5 MB each) <span class="text-muted fw-normal">(Hold Ctrl/Cmd to select multiple)</span></label>
        <input type="file" name="photos[]" class="form-control" multiple="multiple" accept=".jpg,.jpeg,.png">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="replace_photos" id="replacePhotos">
          <label class="form-check-label small" for="replacePhotos">Replace all existing photos</label>
        </div>
      </div>
    </div>

    <div class="d-flex gap-3 mt-4 pt-3 border-top">
      <button type="submit" class="btn btn-primary px-4 fw-600 shadow-0">
        <span class="material-icons align-middle fs-6 me-1">save</span> Update Tournament
      </button>
      <a href="<?php echo BASE_URL; ?>/dashboard/seller/tournaments.php" class="btn btn-light shadow-0 fw-600">Cancel</a>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const startInput = document.querySelector('input[name="start_date"]');
  const endInput = document.querySelector('input[name="end_date"]');
  const regInput = document.querySelector('input[name="registration_deadline"]');
  
  const updateLimits = () => {
    endInput.min = startInput.value;
    regInput.max = startInput.value;
  };
  
  startInput.addEventListener('change', updateLimits);
  updateLimits(); // run on load

  const autofill = document.getElementById('venueAutoFill');
  if (autofill) {
      autofill.addEventListener('change', function() {
          const selected = this.options[this.selectedIndex];
          if (!selected.value) return;
          document.getElementById('tloc').value = selected.getAttribute('data-loc');
          document.getElementById('tcity').value = selected.getAttribute('data-city');
          document.getElementById('tstate').value = selected.getAttribute('data-state');
          document.getElementById('tpin').value = selected.getAttribute('data-pin');
          document.getElementById('tlat').value = selected.getAttribute('data-lat');
          document.getElementById('tlng').value = selected.getAttribute('data-lng');
      });
  }
});
</script>

<?php layoutFooter(); ?>
