<?php
// models/Tournament.php
require_once __DIR__ . '/../config/db.php';

class Tournament {
    private PDO $db;
    public function __construct() { $this->db = getPDO(); }

    public function create(array $d): int {
        $stmt = $this->db->prepare(
            "INSERT INTO tournaments (seller_id,name,sport_type,location,city,state,pincode,latitude,longitude,description,start_date,end_date,registration_deadline,registration_fee,team_size)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $d['seller_id'],$d['name'],$d['sport_type'],$d['location'],
            $d['city']??'',$d['state']??'',$d['pincode']??'',
            $d['latitude']??null, $d['longitude']??null,
            $d['description'],$d['start_date'],$d['end_date'],$d['registration_deadline']??null,
            $d['registration_fee']??0, $d['team_size']??1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d, int $sellerId): void {
        $this->db->prepare(
            "UPDATE tournaments SET name = ?,sport_type = ?,location = ?,city = ?,state = ?,pincode = ?,latitude = ?,longitude = ?,description = ?,start_date = ?,end_date = ?,registration_deadline = ?,registration_fee = ?,team_size = ?
             WHERE id = ? AND seller_id = ?"
        )->execute([
            $d['name'],$d['sport_type'],$d['location'],$d['city']??'',$d['state']??'',$d['pincode']??'',
            $d['latitude']??null, $d['longitude']??null, $d['description'],
            $d['start_date'],$d['end_date'],$d['registration_deadline']??null,
            $d['registration_fee']??0, $d['team_size']??1,
            $id,$sellerId
        ]);
    }

    public function toggle(int $id, int $sellerId, int $active): void {
        $this->db->prepare("UPDATE tournaments SET is_active = ? WHERE id = ? AND seller_id = ?")->execute([$active,$id,$sellerId]);
    }

    public function softDelete(int $id, int $sellerId): void {
        $this->db->prepare("UPDATE tournaments SET is_deleted = 1,is_active = 0 WHERE id = ? AND seller_id = ?")->execute([$id,$sellerId]);
    }

    public function adminDismiss(int $id): void {
        $this->db->prepare("UPDATE tournaments SET is_deleted = 1,is_active = 0 WHERE id = ?")->execute([$id]);
    }

    public function getBySeller(int $sellerId): array {
        $stmt = $this->db->prepare("SELECT * FROM tournaments WHERE seller_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT t.*, u.name AS seller_name, u.business_name AS seller_business, u.phone AS seller_phone
             FROM tournaments t
             JOIN users u ON u.id = t.seller_id
             WHERE t.id = ? AND t.is_deleted = 0 LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(array $filters = []): array {
        $where = ["t.is_deleted = 0"];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(t.name LIKE ? OR t.location LIKE ? OR t.city LIKE ?)";
            $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%";
        }
        if (!empty($filters['sport'])) {
            $where[] = "t.sport_type = ?";
            $params[] = $filters['sport'];
        }
        if (!empty($filters['state'])) {
            $where[] = "t.state = ?";
            $params[] = $filters['state'];
        }
        if (!empty($filters['city'])) {
            $where[] = "t.city = ?";
            $params[] = $filters['city'];
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = "t.is_active = ?";
            $params[] = (int)$filters['active'];
        }
        if (!empty($filters['seller_id'])) {
            $where[] = "t.seller_id = ?";
            $params[] = (int)$filters['seller_id'];
        }

        $sql = "SELECT t.*, u.name AS seller_name FROM tournaments t 
                JOIN users u ON u.id = t.seller_id
                WHERE " . implode(" AND ", $where) . " 
                ORDER BY t.start_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function registerCustomer(int $tournamentId, int $customerId, string $playerDetails = ''): int {
        $ref = 'TRN-' . strtoupper(bin2hex(random_bytes(4)));
        $db = $this->db;
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO tournament_registrations (reference, tournament_id, customer_id, player_details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ref, $tournamentId, $customerId, $playerDetails]);
            $registrationId = (int)$db->lastInsertId();

            // Record revenue if there is a fee
            $stmt = $db->prepare("SELECT seller_id, registration_fee FROM tournaments WHERE id = ?");
            $stmt->execute([$tournamentId]);
            $t = $stmt->fetch();
            
            if ($t && $t['registration_fee'] > 0) {
                $db->prepare("INSERT INTO revenue_log (seller_id, booking_id, amount) VALUES (?, NULL, ?)")
                   ->execute([$t['seller_id'], $t['registration_fee']]);
                // Note: booking_id is NULL for tournament registrations, or we might need a separate tournament_reg_id column
                // For now, let's keep it simple or use a comment mapping.
            }

            $db->commit();
            return $registrationId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function isRegistered(int $tournamentId, int $customerId): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tournament_registrations WHERE tournament_id = ? AND customer_id = ? AND status = 'registered'");
        $stmt->execute([$tournamentId, $customerId]);
        return $stmt->fetchColumn() > 0;
    }

    public function getCustomerRegistrations(int $customerId): array {
        $stmt = $this->db->prepare(
            "SELECT tr.*, t.name as tournament_name, t.sport_type, t.location, t.start_date, t.end_date 
             FROM tournament_registrations tr 
             JOIN tournaments t ON t.id = tr.tournament_id 
             WHERE tr.customer_id = ? ORDER BY tr.created_at DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
    
    public function getRegistration(int $registrationId): ?array {
        $stmt = $this->db->prepare(
            "SELECT tr.*, t.name as tournament_name, t.sport_type, t.location, t.start_date, t.end_date, t.description, u.name as customer_name, u.email as customer_email, sel.business_name as seller_name, sel.phone as seller_phone
             FROM tournament_registrations tr 
             JOIN tournaments t ON t.id = tr.tournament_id 
             JOIN users u ON u.id = tr.customer_id
             JOIN users sel ON sel.id = t.seller_id
             WHERE tr.id = ? LIMIT 1"
        );
        $stmt->execute([$registrationId]);
        return $stmt->fetch() ?: null;
    }

    public function addPhoto(int $tournamentId, string $url, int $order): void {
        $this->db->prepare("INSERT INTO tournament_photos (tournament_id,photo_url,sort_order) VALUES (?,?,?)")->execute([$tournamentId,$url,$order]);
    }
    public function deletePhotos(int $tournamentId): void {
        $this->db->prepare("DELETE FROM tournament_photos WHERE tournament_id = ?")->execute([$tournamentId]);
    }
    public function getPhotos(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM tournament_photos WHERE tournament_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function deletePhoto(int $photoId, int $sellerId): bool {
        // First verify ownership
        $stmt = $this->db->prepare("
            SELECT tp.id FROM tournament_photos tp 
            JOIN tournaments t ON t.id = tp.tournament_id 
            WHERE tp.id = ? AND t.seller_id = ?
        ");
        $stmt->execute([$photoId, $sellerId]);
        if (!$stmt->fetch()) return false;

        $stmt = $this->db->prepare("DELETE FROM tournament_photos WHERE id = ?");
        return $stmt->execute([$photoId]);
    }

    public function cleanupExpired(): void {
        $today = date('Y-m-d');
        // We set is_active to 0 so they don't show up for customers, 
        // but we keep is_deleted = 0 so the Seller/Admin can still see them.
        $stmt = $this->db->prepare("UPDATE tournaments SET is_active = 0 WHERE end_date < ? AND is_active = 1 AND is_deleted = 0");
        $stmt->execute([$today]);
    }

    public function getUniqueCities(): array {
        $stmt = $this->db->query("SELECT DISTINCT city FROM tournaments WHERE city IS NOT NULL AND city != '' AND is_deleted = 0 ORDER BY city ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}






