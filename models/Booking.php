<?php
// models/Booking.php
require_once __DIR__ . '/../config/db.php';

class Booking {
    private ?PDO $db;
    public function __construct() { $this->db = getPDO(); }

    public function getBookedSlots(int $venueId, string $date): array {
        $stmt = $this->db->prepare("SELECT slot_start, slot_end FROM bookings WHERE venue_id = ? AND slot_date = ? AND status = 'confirmed'");
        $stmt->execute([$venueId, $date]);
        return $stmt->fetchAll();
    }

    public function isSlotTaken(int $venueId, string $date, string $start, string $end): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings 
            WHERE venue_id = ? AND slot_date = ? AND status = 'confirmed' 
            AND slot_start < ? AND slot_end > ?");
        // Overlap logic: Existing Start < New End AND Existing End > New Start
        $stmt->execute([$venueId, $date, $end, $start]);
        return $stmt->fetchColumn() > 0;
    }

    public function create(array $d): int {
        $ref = 'SPT-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $this->db->prepare("INSERT INTO bookings (reference,customer_id,venue_id,slot_date,slot_start,slot_end) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$ref, $d['customer_id'], $d['venue_id'], $d['slot_date'], $d['slot_start'], $d['slot_end']]);
        $bookingId = (int)$this->db->lastInsertId();
        
        $amount = $d['total_price'] ?? 0;
        if ($amount > 0) {
            $this->db->prepare("INSERT INTO revenue_log (seller_id,booking_id,amount) SELECT seller_id,?,? FROM venues WHERE id = ?")->execute([$bookingId, $amount, $d['venue_id']]);
        } else {
            $this->db->prepare("INSERT INTO revenue_log (seller_id,booking_id,amount) SELECT seller_id,?,price_per_slot FROM venues WHERE id = ?")->execute([$bookingId, $d['venue_id']]);
        }
        return $bookingId;
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT b.*, v.name AS venue_name, v.location, v.price_per_slot, u.name AS customer_name, u.email AS customer_email, sel.name AS seller_name, sel.phone AS seller_phone, sel.email AS seller_email FROM bookings b JOIN venues v ON v.id = b.venue_id JOIN users u ON u.id = b.customer_id JOIN users sel ON sel.id = v.seller_id WHERE b.id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByCustomer(int $customerId): array {
        $stmt = $this->db->prepare("SELECT b.*, v.name AS venue_name, v.location, (SELECT id FROM reviews WHERE booking_id = b.id LIMIT 1) AS review_id FROM bookings b JOIN venues v ON v.id = b.venue_id WHERE b.customer_id = ? ORDER BY b.created_at DESC");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function getAll(array $filters = []): array {
        $where = ['1 = 1']; $params = [];
        if (!empty($filters['status'])) { $where[] = "b.status = ?"; $params[] = $filters['status']; }
        if (!empty($filters['date_from'])) { $where[] = "b.slot_date >= ?"; $params[] = $filters['date_from']; }
        if (!empty($filters['date_to'])) { $where[] = "b.slot_date <= ?"; $params[] = $filters['date_to']; }
        $stmt = $this->db->prepare("SELECT b.*, v.name AS venue_name, cu.name AS customer_name, sel.name AS seller_name FROM bookings b JOIN venues v ON v.id = b.venue_id JOIN users cu ON cu.id = b.customer_id JOIN users sel ON sel.id = v.seller_id WHERE " . implode(' AND ', $where) . " ORDER BY b.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function dismiss(int $id): void {
        $this->db->prepare("UPDATE bookings SET status = 'dismissed' WHERE id = ?")->execute([$id]);
    }

    public function cancel(int $id, int $customerId): string {
        $booking = $this->getById($id);
        if (!$booking || $booking['customer_id'] != $customerId) {
            return "Booking not found.";
        }
        if ($booking['status'] === 'cancelled') {
            return "Booking is already cancelled.";
        }
        if ($booking['status'] === 'dismissed') {
            return "Dismissed bookings cannot be cancelled.";
        }

        // 12-hour rule: Slot start date/time compared to now
        $slotDateTime = $booking['slot_date'] . ' ' . $booking['slot_start'];
        $slotTs = strtotime($slotDateTime);
        $diffSeconds = $slotTs - time();
        $diffHours = $diffSeconds / 3600;

        if ($diffHours < 12) {
            return "Cancellations must be made at least 12 hours before the slot begins.";
        }

        $this->db->beginTransaction();
        try {
            // Update booking status
            $this->db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);

            // Deduct from revenue log by adding a negative entry
            $stmt = $this->db->prepare("SELECT amount, seller_id FROM revenue_log WHERE booking_id = ? AND amount > 0 LIMIT 1");
            $stmt->execute([$id]);
            $rev = $stmt->fetch();
            
            if ($rev) {
                $this->db->prepare("INSERT INTO revenue_log (seller_id, booking_id, amount) VALUES (?, ?, ?)")
                         ->execute([$rev['seller_id'], $id, -$rev['amount']]);
            }

            $this->db->commit();
            return "success";
        } catch (Exception $e) {
            $this->db->rollBack();
            return "Err: " . $e->getMessage();
        }
    }
}
