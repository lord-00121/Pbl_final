<?php
// models/Venue.php
require_once __DIR__ . '/../config/db.php';

class Venue {
    private ?PDO $db;
    public function __construct() { $this->db = getPDO(); }

    public function search(array $filters): array {
        $sql = "SELECT v.*, u.name as seller_name, 
                (SELECT photo_url FROM venue_photos WHERE venue_id = v.id ORDER BY sort_order ASC LIMIT 1) as primary_photo
                FROM venues v 
                JOIN users u ON u.id = v.seller_id
                WHERE v.is_active = 1 AND v.is_deleted = 0";
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (v.name LIKE ? OR v.location LIKE ? OR v.city LIKE ?)";
            $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%";
        }
        if (!empty($filters['sport'])) {
            $sql .= " AND v.sport_type IN (" . implode(',', array_fill(0, count($filters['sport']), '?')) . ")";
            foreach ($filters['sport'] as $s) $params[] = $s;
        }
        if (!empty($filters['start_time'])) {
            $sql .= " AND v.operating_hours_start <= ? AND v.operating_hours_end > ?";
            $params[] = $filters['start_time'];
            $params[] = $filters['start_time'];
        }

        if (!empty($filters['state'])) {
            $sql .= " AND v.state = ?";
            $params[] = $filters['state'];
        }
        if (!empty($filters['city'])) {
            $sql .= " AND v.city = ?";
            $params[] = $filters['city'];
        }

        // Sorting
        $sort = $filters['sort'] ?? 'rating';
        if ($sort === 'price_asc') {
            $sql .= " ORDER BY v.price_per_slot ASC";
        } elseif ($sort === 'price_desc') {
            $sql .= " ORDER BY v.price_per_slot DESC";
        } else {
            $sql .= " ORDER BY v.rating_avg DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT v.*, u.name as seller_name FROM venues v JOIN users u ON u.id = v.seller_id WHERE v.id = ? AND v.is_deleted = 0 LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getPhotos(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM venue_photos WHERE venue_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function getBySeller(int $sellerId): array {
        $stmt = $this->db->prepare("SELECT * FROM venues WHERE seller_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO venues (seller_id, name, sport_type, location, city, state, pincode, latitude, longitude, description, price_per_slot, slot_duration, operating_hours_start, operating_hours_end) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $data['seller_id'], $data['name'], $data['sport_type'], $data['location'],
            $data['city'] ?? '', $data['state'] ?? '', $data['pincode'] ?? '',
            $data['latitude'] ?? null, $data['longitude'] ?? null,
            $data['description'], $data['price_per_slot'], $data['slot_duration'],
            $data['operating_hours_start'], $data['operating_hours_end']
        ]);
        return (int)$this->db->lastInsertId();
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data, int $sellerId): void {
        $stmt = $this->db->prepare("UPDATE venues SET name=?, sport_type=?, location=?, city=?, state=?, pincode=?, latitude=?, longitude=?, description=?, price_per_slot=?, slot_duration=?, operating_hours_start=?, operating_hours_end=? WHERE id=? AND seller_id=?");
        $stmt->execute([
            $data['name'], $data['sport_type'], $data['location'],
            $data['city'] ?? '', $data['state'] ?? '', $data['pincode'] ?? '',
            $data['latitude'] ?? null, $data['longitude'] ?? null,
            $data['description'], $data['price_per_slot'], $data['slot_duration'], $data['operating_hours_start'],
            $data['operating_hours_end'], $id, $sellerId
        ]);
    }

    public function toggle(int $id, int $sellerId, int $active): void {
        $this->db->prepare("UPDATE venues SET is_active = ? WHERE id = ? AND seller_id = ?")->execute([$active, $id, $sellerId]);
    }

    public function addPhoto(int $venueId, string $url, int $order): void {
        $this->db->prepare("INSERT INTO venue_photos (venue_id, photo_url, sort_order) VALUES (?,?,?)")->execute([$venueId, $url, $order]);
    }

    public function deletePhotos(int $venueId): void {
        $this->db->prepare("DELETE FROM venue_photos WHERE venue_id = ?")->execute([$venueId]);
    }

    public function getAll(array $filters = []): array {
        $where = ["v.is_deleted = 0"];
        $params = [];
        
        if (!empty($filters['q'])) {
            $where[] = "(v.name LIKE ? OR v.location LIKE ?)";
            $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%";
        }
        if (!empty($filters['sport'])) {
            $where[] = "v.sport_type = ?";
            $params[] = $filters['sport'];
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = "v.is_active = ?";
            $params[] = (int)$filters['active'];
        }
        if (!empty($filters['seller_id'])) {
            $where[] = "v.seller_id = ?";
            $params[] = (int)$filters['seller_id'];
        }
        if (!empty($filters['state'])) {
            $where[] = "v.state = ?";
            $params[] = $filters['state'];
        }
        if (!empty($filters['city'])) {
            $where[] = "v.city = ?";
            $params[] = $filters['city'];
        }

        $sql = "SELECT v.*, u.name as seller_name, 
                (SELECT photo_url FROM venue_photos WHERE venue_id = v.id ORDER BY sort_order ASC LIMIT 1) as primary_photo
                FROM venues v 
                JOIN users u ON u.id = v.seller_id
                WHERE " . implode(" AND ", $where) . " 
                ORDER BY v.rating_avg DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function adminDismiss(int $id): void {
        $this->db->prepare("UPDATE venues SET is_deleted = 1 WHERE id = ?")->execute([$id]);
    }

    public function getUniqueCities(): array {
        $stmt = $this->db->query("SELECT DISTINCT city FROM venues WHERE city IS NOT NULL AND city != '' AND is_deleted = 0 ORDER BY city ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
