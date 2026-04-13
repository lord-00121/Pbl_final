<?php
// api/slots.php â€” Returns booked slots for a venue on a date
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Booking.php';

header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error' => 'Unauthorized']); exit; }

$venueId = (int)($_GET['venue_id'] ?? 0);
$date = $_GET['date'] ?? '';

if (!$venueId || !$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid parameters']); exit;
}
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    echo json_encode(['booked' => [], 'note' => 'Past date']); exit;
}

try {
    $bookingModel = new Booking();
    $booked = $bookingModel->getBookedSlots($venueId, $date);
    echo json_encode(['booked' => $booked]);
} catch (Exception $e) {
    echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
}







