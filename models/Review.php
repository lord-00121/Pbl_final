<?php
// models/Review.php
require_once __DIR__ . '/../config/db.php';

class Review {
    private PDO $db;
    public function __construct() { $this->db = getPDO(); }

    public function create(array $d): void {
        $this->db->prepare(
            "INSERT INTO reviews (customer_id,venue_id,booking_id,rating,comment) VALUES (?,?,?,?,?)"
        )->execute([$d['customer_id'],$d['venue_id'],$d['booking_id'],$d['rating'],$d['comment']]);
    }

    public function getByVenue(int $venueId): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.name AS reviewer_name
             FROM reviews r JOIN users u ON u.id = r.customer_id
             WHERE r.venue_id = ? AND r.is_deleted = 0 ORDER BY r.created_at DESC"
        );
        $stmt->execute([$venueId]);
        return $stmt->fetchAll();
    }

    public function getByBooking(int $bookingId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM reviews WHERE booking_id = ? LIMIT 1");
        $stmt->execute([$bookingId]);
        return $stmt->fetch() ?: null;
    }

    public function adminDelete(int $id): void {
        $this->db->prepare("UPDATE reviews SET is_deleted = 1 WHERE id = ?")->execute([$id]);
    }

    public function canReview(int $customerId, int $bookingId): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM bookings WHERE id = ? AND customer_id = ? AND status = 'confirmed'"
        );
        $stmt->execute([$bookingId,$customerId]);
        if (!$stmt->fetchColumn()) return false;
        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM reviews WHERE booking_id = ?");
        $stmt2->execute([$bookingId]);
        return (int)$stmt2->fetchColumn() ===  0;
    }
}
