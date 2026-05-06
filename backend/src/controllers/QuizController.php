<?php

class QuizController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function categories(): void {
        $rows = $this->db->query('SELECT * FROM categories ORDER BY id')->fetchAll();
        // Attach quiz count
        foreach ($rows as &$row) {
            $row['quiz_count'] = (int)$this->db
                ->query("SELECT COUNT(*) FROM quizzes WHERE category_id = {$row['id']}")
                ->fetchColumn();
        }
        echo json_encode(['categories' => $rows]);
    }

    public function list(): void {
        $cat = $_GET['category'] ?? null;
        $sql = 'SELECT q.*, c.name as category_name, c.color, c.icon,
                    (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                FROM quizzes q LEFT JOIN categories c ON c.id = q.category_id';
        if ($cat) {
            $stmt = $this->db->prepare($sql . ' WHERE c.slug = ? ORDER BY q.id DESC');
            $stmt->execute([$cat]);
        } else {
            $stmt = $this->db->query($sql . ' ORDER BY q.id DESC');
        }
        echo json_encode(['quizzes' => $stmt->fetchAll()]);
    }

    public function start(): void {
        $body = $this->json();
        $quizId = (int)($body['quiz_id'] ?? 0);
        if (!$quizId) { $this->error('quiz_id gerekli'); return; }

        $quiz = $this->db->prepare('SELECT * FROM quizzes WHERE id = ?');
        $quiz->execute([$quizId]);
        $q = $quiz->fetch();
        if (!$q) { $this->error('Quiz bulunamadı', 404); return; }

        $userId = $this->authUserId();

        // Shuffle the quiz's question IDs so each session sees a fresh order
        $qIdsStmt = $this->db->prepare('SELECT id FROM questions WHERE quiz_id = ? ORDER BY sort_order');
        $qIdsStmt->execute([$quizId]);
        $qIds = array_map('intval', $qIdsStmt->fetchAll(PDO::FETCH_COLUMN));
        shuffle($qIds);
        $order = json_encode($qIds);

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $stmt = $this->db->prepare('INSERT INTO sessions (user_id, quiz_id, answers, question_order) VALUES (?,?,?,?) RETURNING id');
            $stmt->execute([$userId, $quizId, '{}', $order]);
            $sessionId = (int)$stmt->fetchColumn();
        } else {
            $stmt = $this->db->prepare('INSERT INTO sessions (user_id, quiz_id, answers, question_order) VALUES (?,?,?,?)');
            $stmt->execute([$userId, $quizId, '{}', $order]);
            $sessionId = (int)$this->db->lastInsertId();
        }

        echo json_encode(['session_id' => $sessionId, 'quiz' => $q]);
    }

    public function question(?string $id): void {
        if (!$id) { $this->error('Soru ID gerekli'); return; }

        // id is the session_id; question index comes from ?index= (legacy: id can be "session:index")
        if (str_contains($id, ':')) {
            [$sessionId, $index] = array_pad(explode(':', $id), 2, 0);
        } else {
            $sessionId = $id;
            $index = $_GET['index'] ?? 0;
        }
        $index = (int)$index;

        $sess = $this->db->prepare('SELECT * FROM sessions WHERE id = ?');
        $sess->execute([(int)$sessionId]);
        $session = $sess->fetch();
        if (!$session) { $this->error('Oturum bulunamadı', 404); return; }

        $order = !empty($session['question_order']) ? json_decode($session['question_order'], true) : null;

        if (is_array($order) && count($order) > 0) {
            if (!isset($order[$index])) { $this->error('Soru bulunamadı', 404); return; }
            $stmt = $this->db->prepare(
                'SELECT id, body, option_a, option_b, option_c, option_d, option_e, sort_order
                 FROM questions WHERE id = ?'
            );
            $stmt->execute([(int)$order[$index]]);
            $q = $stmt->fetch();
            $total = count($order);
        } else {
            // Legacy sessions created before shuffling was added
            $stmt = $this->db->prepare(
                'SELECT id, body, option_a, option_b, option_c, option_d, option_e, sort_order
                 FROM questions WHERE quiz_id = ? ORDER BY sort_order LIMIT 1 OFFSET ?'
            );
            $stmt->execute([$session['quiz_id'], $index]);
            $q = $stmt->fetch();
            $total = (int)$this->db
                ->query("SELECT COUNT(*) FROM questions WHERE quiz_id = {$session['quiz_id']}")
                ->fetchColumn();
        }
        if (!$q) { $this->error('Soru bulunamadı', 404); return; }

        $quizStmt = $this->db->prepare('SELECT id, title, time_limit FROM quizzes WHERE id = ?');
        $quizStmt->execute([$session['quiz_id']]);
        $quiz = $quizStmt->fetch();

        $answers = json_decode($session['answers'], true) ?? [];

        echo json_encode([
            'question'   => $q,
            'index'      => $index,
            'total'      => $total,
            'answered'   => $answers[$q['id']] ?? null,
            'session_id' => $session['id'],
            'quiz'       => $quiz,
        ]);
    }

    public function submit(): void {
        $body = $this->json();
        $sessionId  = (int)($body['session_id'] ?? 0);
        $questionId = (int)($body['question_id'] ?? 0);
        $answer     = strtolower(trim($body['answer'] ?? ''));

        if (!$sessionId || !$questionId || !$answer) {
            $this->error('Eksik parametre'); return;
        }

        $sess = $this->db->prepare('SELECT * FROM sessions WHERE id = ?');
        $sess->execute([$sessionId]);
        $session = $sess->fetch();
        if (!$session) { $this->error('Oturum bulunamadı', 404); return; }

        $answers = json_decode($session['answers'], true) ?? [];
        $answers[$questionId] = $answer;

        $upd = $this->db->prepare('UPDATE sessions SET answers = ? WHERE id = ?');
        $upd->execute([json_encode($answers), $sessionId]);

        // Check correctness
        $q = $this->db->prepare('SELECT correct, explanation FROM questions WHERE id = ?');
        $q->execute([$questionId]);
        $row = $q->fetch();

        echo json_encode([
            'correct'     => $row['correct'] === $answer,
            'correct_ans' => $row['correct'],
            'explanation' => $row['explanation'],
        ]);
    }

    public function finish(): void {
        $body = $this->json();
        $sessionId = (int)($body['session_id'] ?? 0);
        if (!$sessionId) { $this->error('session_id gerekli'); return; }

        $sess = $this->db->prepare('SELECT * FROM sessions WHERE id = ?');
        $sess->execute([$sessionId]);
        $session = $sess->fetch();
        if (!$session) { $this->error('Oturum bulunamadı', 404); return; }

        $answers = json_decode($session['answers'], true) ?? [];

        // Fetch all questions with correct answers
        $stmtQ = $this->db->prepare('SELECT id, correct FROM questions WHERE quiz_id = ?');
        $stmtQ->execute([$session['quiz_id']]);
        $questions = $stmtQ->fetchAll();

        $total   = count($questions);
        $correct = 0;
        foreach ($questions as $q) {
            if (($answers[$q['id']] ?? '') === $q['correct']) $correct++;
        }
        $score = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

        $upd = $this->db->prepare(
            'UPDATE sessions SET finished_at = datetime("now"), score = ?, total = ?, correct_count = ? WHERE id = ?'
        );
        $upd->execute([$score, $total, $correct, $sessionId]);

        echo json_encode([
            'score'   => $score,
            'correct' => $correct,
            'total'   => $total,
            'wrong'   => $total - $correct,
        ]);
    }

    // ---- helpers ----
    private function json(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    private function error(string $msg, int $code = 400): void {
        http_response_code($code);
        echo json_encode(['error' => $msg]);
    }

    private function authUserId(): ?int {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$token) return null;
        $token = str_replace('Bearer ', '', $token);
        $row = $this->db->prepare('SELECT id FROM users WHERE password_hash = ?');
        $row->execute([$token]); // simplistic token = stored hash for demo
        $u = $row->fetch();
        return $u ? (int)$u['id'] : null;
    }
}
