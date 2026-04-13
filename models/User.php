<?php
// models/User.php
require_once __DIR__ . '/../config/db.php';

class User {
    private ?PDO $db;
    public function __construct() { $this->db = getPDO(); }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password, role, status, phone, city, address) VALUES (?, ?, ?, ?, 'active', ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'], 
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'],
            $data['phone'] ?? null,
            $data['city'] ?? null,
            $data['address'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getAll(array $filters = []): array {
        $where = ["1=1"];
        $params = [];
        if (!empty($filters['q'])) {
            $where[] = "(name LIKE ? OR email LIKE ? OR business_name LIKE ?)";
            $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%";
        }
        if (!empty($filters['role'])) {
            $where[] = "role = ?";
            $params[] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        $stmt = $this->db->prepare("SELECT * FROM users WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function setStatus(int $id, string $status): void {
        $this->db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$status, $id]);
    }

    public function updateStatus(int $id, string $status): void {
        $this->setStatus($id, $status);
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
}
