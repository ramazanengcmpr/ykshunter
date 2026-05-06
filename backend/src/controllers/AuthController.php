<?php

class AuthController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function register(): void {
        $body = $this->json();
        $name     = trim($body['name'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$name || !$email || !$password) {
            $this->error('Tüm alanları doldurun'); return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Geçersiz e-posta'); return;
        }
        if (strlen($password) < 6) {
            $this->error('Şifre en az 6 karakter olmalı'); return;
        }

        $check = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) { $this->error('Bu e-posta zaten kayıtlı'); return; }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $stmt = $this->db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?,?,?) RETURNING id');
            $stmt->execute([$name, $email, $hash]);
            $id = (int)$stmt->fetchColumn();
        } else {
            $stmt = $this->db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?,?,?)');
            $stmt->execute([$name, $email, $hash]);
            $id = (int)$this->db->lastInsertId();
        }

        echo json_encode(['user' => ['id' => $id, 'name' => $name, 'email' => $email], 'token' => $hash]);
    }

    public function login(): void {
        $body     = $this->json();
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$email || !$password) { $this->error('E-posta ve şifre gerekli'); return; }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->error('Hatalı e-posta veya şifre', 401); return;
        }

        unset($user['password_hash']);
        echo json_encode(['user' => $user, 'token' => $user['password_hash'] ?? '']);
    }

    public function me(): void {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!$token) { $this->error('Yetkisiz', 401); return; }

        $stmt = $this->db->prepare('SELECT id, name, email, created_at FROM users WHERE password_hash = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) { $this->error('Geçersiz token', 401); return; }
        echo json_encode(['user' => $user]);
    }

    public function users(): void {
        $stmt = $this->db->query('SELECT id, name, email, created_at FROM users ORDER BY id DESC');
        $users = $stmt->fetchAll();
        echo json_encode(['users' => $users, 'count' => count($users)]);
    }

    private function json(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    private function error(string $msg, int $code = 400): void {
        http_response_code($code);
        echo json_encode(['error' => $msg]);
    }
}
