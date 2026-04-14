<?php
// dashboard/customer/past_bookings.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Booking.php';
require_once __DIR__ . '/../../models/Review.php';
require_once __DIR__ . '/../../includes/layout.php';
requireLogin('customer');

$user = currentUser();
$bookingModel = new Booking();
$reviewModel = new Review();
$bookings = $bookingModel->getByCustomer($user['id']);

// Handle review submission
$reviewMsg = '';
$reviewType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    verifyCsrf();
    $bId = (int)$_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim(substr($_POST['comment'] ?? '', 0, 500));
    $booking = $bookingModel->getById($bId);
    
    if ($booking && $booking['customer_id'] == $user['id'] && $reviewModel->canReview($user['id'], $bId)) {
        $reviewModel->create([
            'customer_id' => $user['id'],
            'venue_id' => $booking['venue_id'],
            'booking_id' => $bId,
            'rating' => max(1, min(5, $rating)),
            'comment' => $comment,
        ]);
        // Recalculate venue rating
        require_once __DIR__ . '/../../models/Venue.php';
        (new Venue())->recalcRating($booking['venue_id']);
        $reviewMsg = 'Review submitted successfully!';
        $bookings = $bookingModel->getByCustomer($user['id']); // Refresh data
    }
}

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    verifyCsrf();
    $bId = (int)$_POST['booking_id'];
    $res = $bookingModel->cancel($bId, $user['id']);
    if ($res === 'success') {
        $reviewMsg = 'Booking cancelled successfully. The slot is now available for others.';
        $reviewType = 'warning';
        $bookings = $bookingModel->getByCustomer($user['id']); // Refresh
    } else {
        $reviewMsg = $res;
        $reviewType = 'danger';
    }
}

layoutHead('My Bookings');
layoutNavbar('customer', $user['name']);
layoutSidebar('customer', 'My Bookings');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-700 m-0">My Bookings</h4>
    <p class="text-muted small">All your upcoming and past reservations</p>
  </div>
</div>

<?php if ($reviewMsg): ?>
  <div class="alert alert-<?php echo $reviewType; ?> py-2 shadow-0 small fw-600"><span class="material-icons align-middle fs-6 me-1"><?php echo $reviewType === 'danger' ? 'error' : 'check_circle'; ?></span> <?php echo h($reviewMsg); ?></div>
<?php endif; ?>

<?php if (empty($bookings)): ?>
  <div class="card p-5 text-center shadow-sm">
    <span class="material-icons text-muted mb-3 d-block mx-auto" style="font-size:4rem; opacity:0.3">event_busy</span>
    <h5 class="fw-700">No bookings yet</h5>
    <p class="text-muted mb-4">You haven't made any reservations. Find an amazing venue to start playing!</p>
    <div>
        <a href="<?php echo BASE_URL; ?>/dashboard/customer/browse.php" class="btn btn-primary px-4 fw-600 shadow-0">Browse Venues</a>
    </div>
  </div>
<?php else: ?>
  <div class="card shadow-sm border-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
          <tr>
            <th class="ps-4 fw-600 small text-uppercase text-muted">Ref</th>
            <th class="fw-600 small text-uppercase text-muted">Venue</th>
            <th class="fw-600 small text-uppercase text-muted">Date</th>
            <th class="fw-600 small text-uppercase text-muted">Slot Time</th>
            <th class="fw-600 small text-uppercase text-muted">Status</th>
            <th class="pe-4 text-end fw-600 small text-uppercase text-muted">Review</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td class="ps-4 fw-700 text-primary"><?php echo h($b['reference']); ?></td>
            <td class="fw-600"><?php echo h($b['venue_name']); ?></td>
            <td class="text-muted"><?php echo date('d M Y', strtotime($b['slot_date'])); ?></td>
            <td>
                <span class="bg-light px-2 py-1 rounded small fw-600 text-dark border">
                    <?php echo date('h:i A', strtotime($b['slot_start'])); ?> – <?php echo date('h:i A', strtotime($b['slot_end'])); ?>
                </span>
            </td>
            <td>
              <?php if($b['status'] === 'confirmed'): ?>
                <span class="badge rounded-pill bg-success fw-600 px-3 shadow-0">Confirmed</span>
              <?php elseif($b['status'] === 'cancelled'): ?>
                <span class="badge rounded-pill bg-warning text-dark fw-600 px-3 shadow-0">Cancelled</span>
              <?php else: ?>
                <span class="badge rounded-pill bg-danger fw-600 px-3 shadow-0">Dismissed</span>
              <?php endif; ?>
            </td>
            <td class="pe-4 text-end">
              <?php 
                $slotDateTime = $b['slot_date'].' '.$b['slot_start'];
                $canCancel = ($b['status'] === 'confirmed' && (strtotime($slotDateTime) - time()) > 43200); // 12h
              ?>
              
              <?php if ($canCancel): ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Cancel this booking? You can only cancel at least 12 hours before the start.')">
                    <?php echo csrfInput(); ?>
                    <input type="hidden" name="cancel_booking" value="1">
                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm shadow-0 fw-600 py-1">Cancel</button>
                </form>
              <?php endif; ?>

              <?php if ($b['status'] === 'confirmed' && !$b['review_id'] && strtotime($b['slot_date'].' '.$b['slot_end']) < time()): ?>
                <button class="btn btn-warning btn-sm shadow-0 fw-600" data-mdb-modal-init data-mdb-target="#reviewModal" onclick="setReviewBooking(<?php echo $b['id']; ?>, '<?php echo addslashes(h($b['venue_name'])); ?>')">
                  Leave Review
                </button>
              <?php elseif ($b['review_id']): ?>
                <span class="text-success small fw-600"><span class="material-icons align-middle fs-6">task_alt</span> Reviewed</span>
              <?php elseif ($b['status'] === 'cancelled' || $b['status'] === 'dismissed'): ?>
                 <span class="text-muted small">—</span>
              <?php elseif (!$canCancel && $b['status'] === 'confirmed' && strtotime($slotDateTime) > time()): ?>
                <span class="text-muted small" title="Cancellations allowed until 12h before start">Upcoming</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Review Modal -->
  <div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-700">Rate Your Experience</h5>
          <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"></button>
        </div>
        <form method="POST">
          <?php echo csrfInput(); ?>
          <input type="hidden" name="submit_review" value="1">
          <input type="hidden" name="booking_id" id="reviewBookingId">
          <div class="modal-body p-4">
            <h6 class="mb-4 text-primary fw-600" id="reviewVenueName"></h6>
            
            <div class="mb-4 text-center">
              <label class="form-label fw-600 mb-2">How was the venue? <span class="text-danger">*</span></label>
              <div class="d-flex justify-content-center gap-2 flex-row-reverse" style="direction: rtl;">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="star<?php echo $i;?>" value="<?php echo $i;?>" class="d-none" <?php echo $i === 5 ? 'required' : ''; ?>>
                <label for="star<?php echo $i;?>" class="star-label" style="cursor:pointer; font-size:2rem; color:#ddd;">★</label>
                <?php endfor; ?>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-600" for="reviewComment">Additional Comments</label>
              <textarea id="reviewComment" name="comment" class="form-control bg-light" rows="4" maxlength="500" placeholder="Was the turf good? Were the amenities clean?"></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-link link-secondary fw-600" data-mdb-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary fw-600 px-4 shadow-0">Submit Review</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <style>
  .star-label:hover, .star-label:hover ~ .star-label, input[type="radio"]:checked ~ .star-label {
      color: #ffc107 !important;
  }
  </style>

  <script>
  function setReviewBooking(id, name) {
    document.getElementById('reviewBookingId').value = id;
    document.getElementById('reviewVenueName').textContent = name;
  }
  </script>
<?php endif; ?>

<?php layoutFooter(); ?>
