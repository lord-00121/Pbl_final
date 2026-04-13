<?php
// models/Revenue.php
require_once __DIR__ . '/../config/db.php';

class Revenue {
    private ?PDO $db;
    public function __construct() { $this->db = getPDO(); }

    public function getSummary(int $sellerId): array {
        $sql = "SELECT 
            SUM(CASE WHEN DATE(r.recorded_at) = CURDATE() THEN r.amount ELSE 0 END) as today,
            SUM(CASE WHEN YEARWEEK(r.recorded_at, 1) = YEARWEEK(CURDATE(), 1) THEN r.amount ELSE 0 END) as week,
            SUM(CASE WHEN MONTH(r.recorded_at) = MONTH(CURDATE()) AND YEAR(r.recorded_at) = YEAR(CURDATE()) THEN r.amount ELSE 0 END) as month,
            SUM(r.amount) as alltime
            FROM revenue_log r
            WHERE r.seller_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId]);
        return $stmt->fetch() ?: ['today'=>0, 'week'=>0, 'month'=>0, 'alltime'=>0];
    }

    public function getDailyLast30(int $sellerId): array {
        $sql = "SELECT DATE(r.recorded_at) as day, SUM(r.amount) as total 
                FROM revenue_log r
                WHERE r.seller_id = ? AND r.recorded_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(r.recorded_at) ORDER BY day ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll();
    }

    public function getRecentTransactions(int $sellerId, int $limit = 10): array {
        $sql = "SELECT r.*, 
                COALESCE(b.reference, 'TOURNAMENT') as reference, 
                COALESCE(v.name, 'Tournament Event') as venue_name, 
                COALESCE(u.name, 'Multiple Players') as customer_name,
                r.recorded_at as slot_date
                FROM revenue_log r
                LEFT JOIN bookings b ON b.id = r.booking_id
                LEFT JOIN venues v ON v.id = b.venue_id
                LEFT JOIN users u ON u.id = b.customer_id
                WHERE r.seller_id = ?
                ORDER BY r.recorded_at DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId, $limit]);
        return $stmt->fetchAll();
    }
}
