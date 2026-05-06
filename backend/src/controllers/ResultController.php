<?php

class ResultController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function list(): void {
        $userId = $this->authUserId();
        $sql = 'SELECT s.*, q.title as quiz_title, c.name as category_name, c.color, c.icon
                FROM sessions s
                LEFT JOIN quizzes q ON q.id = s.quiz_id
                LEFT JOIN categories c ON c.id = q.category_id
                WHERE s.finished_at IS NOT NULL';

        if ($userId) {
            $stmt = $this->db->prepare($sql . ' AND s.user_id = ? ORDER BY s.finished_at DESC LIMIT 20');
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->query($sql . ' ORDER BY s.finished_at DESC LIMIT 20');
        }
        echo json_encode(['results' => $stmt->fetchAll()]);
    }

    public function detail(?string $id): void {
        if (!$id) { $this->error('ID gerekli'); return; }
        $stmt = $this->db->prepare(
            'SELECT s.*, q.title as quiz_title, c.name as category_name
             FROM sessions s
             LEFT JOIN quizzes q ON q.id = s.quiz_id
             LEFT JOIN categories c ON c.id = q.category_id
             WHERE s.id = ?'
        );
        $stmt->execute([(int)$id]);
        $result = $stmt->fetch();
        if (!$result) { $this->error('Sonuç bulunamadı', 404); return; }

        // Fetch answers with question details
        $qStmt = $this->db->prepare(
            'SELECT id, body, option_a, option_b, option_c, option_d, option_e, correct, explanation
             FROM questions WHERE quiz_id = ? ORDER BY sort_order'
        );
        $qStmt->execute([$result['quiz_id']]);
        $questions = $qStmt->fetchAll();

        $answers = json_decode($result['answers'], true) ?? [];
        foreach ($questions as &$q) {
            $q['user_answer'] = $answers[$q['id']] ?? null;
            $q['is_correct']  = ($q['user_answer'] === $q['correct']);
        }

        echo json_encode(['result' => $result, 'questions' => $questions]);
    }

    private function authUserId(): ?int {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!$token) return null;
        $row = $this->db->prepare('SELECT id FROM users WHERE password_hash = ?');
        $row->execute([$token]);
        $u = $row->fetch();
        return $u ? (int)$u['id'] : null;
    }

    private function error(string $msg, int $code = 400): void {
        http_response_code($code);
        echo json_encode(['error' => $msg]);
    }
}
