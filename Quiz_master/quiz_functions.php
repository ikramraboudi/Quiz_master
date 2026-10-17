<?php
require_once 'config.php';

class QuizManager {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    //   CATEGORIES
    //récupère toutes les caté
    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //récupère une catégorie spécifique.
    public function getCategory($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //   QUIZZES
    public function getQuizById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    //liste les quiz d’une catégorie et indique si l’utilisateur peut y accéder (déverrouillé).
    public function getQuizzesByCategory($categoryId, $userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT q.*, 
                   up.score AS user_score,
                   up.completed AS user_completed
            FROM quizzes q
            LEFT JOIN user_progress up
            ON up.quiz_id = q.id AND up.user_id = ?
            WHERE q.category_id = ?
            ORDER BY q.id ASC
        ");
        $stmt->execute([$userId, $categoryId]);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($quizzes as $i => &$quiz) {
            $quiz['unlocked'] = false;
        }
        //le premier toujour unlocked 
        if (!empty($quizzes)) {
            $quizzes[0]['unlocked'] = true;
        }

        foreach ($quizzes as $i => &$quiz) {
            if ($i > 0) {
                $prev = $quizzes[$i - 1];
                $min = $prev['min_score'] ?? 70;

                $quiz['unlocked'] = (
                    !empty($prev['user_completed']) &&
                    $prev['user_score'] >= $min
                );
            }
        }

        return $quizzes;
    }

    //   QUESTIONS + ANSWERS
    //récupère les questions et leurs réponses.
    public function getQuizQuestions($quizId) {
        $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
        $stmt->execute([$quizId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($questions as &$q) {
            $stmt2 = $this->pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
            $stmt2->execute([$q['id']]);
            $q['answers'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        return $questions;
    }

    //   SUBMIT QUIZ (avec timeout)
    //enregistre le score de l’utilisateur, vérifie si le badge est gagné.
    public function submitQuiz($quizId, $userId, $answers, $timeout_occurred = false)
    {
        $questions = $this->getQuizQuestions($quizId);
        $correct = 0;

        foreach ($questions as $q) {
            $answer = $answers[$q['id']] ?? null;

            foreach ($q['answers'] as $a) {
                if ($a['id'] == $answer && $a['is_correct']) {
                    $correct++;
                }
            }
        }
        //calcull score 
        $total = count($questions);
        $percentage = ($total > 0) ? round(($correct / $total) * 100) : 0;
        //progression 
        $stmt = $this->pdo->prepare("
            INSERT INTO user_progress (user_id, quiz_id, completed, score, completed_at, timeout)
            VALUES (?, ?, 1, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
                completed = 1,
                score = VALUES(score),
                timeout = VALUES(timeout),
                completed_at = NOW()
        ");
        $stmt->execute([$userId, $quizId, $percentage, $timeout_occurred ? 1 : 0]);

        $quiz = $this->getQuizById($quizId);

        // Vérifier et attribuer badge
        $this->checkAndGiveBadge($userId, $quiz['category_id']);

        return [
            'quiz_id' => $quizId,
            'correct' => $correct,
            'total' => $total,
            'percentage' => $percentage,
            'timeout' => $timeout_occurred
        ];
    }

    //   BADGES
    //vérifie si l’utilisateur a déjà le badge.
    public function userHasBadgeForCategory($userId, $categoryId)
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM user_badges 
            WHERE user_id = ? AND category_id = ?
        ");
        $stmt->execute([$userId, $categoryId]);
        return $stmt->rowCount() > 0;
    }
    //attribue le badge si toutes les conditions sont remplies.
    public function checkAndGiveBadge($userId, $categoryId)
    {
        if ($this->userHasBadgeForCategory($userId, $categoryId)) {
            return false;
        }

        $quizzes = $this->getQuizzesByCategory($categoryId, $userId);

        foreach ($quizzes as $q) {
            if (empty($q['user_score']) || $q['user_score'] < $q['min_score']) {
                return false;
            }
        }

        $badgeName = "Badge Catégorie " . $categoryId;

        $stmt = $this->pdo->prepare("
            INSERT INTO user_badges (user_id, category_id, badge_name, earned_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $categoryId, $badgeName]);

        return true;
    }

    //   CERTIFICAT FINAL
    // vérifie si l’utilisateur a tous les badges pour générer le certificat.
    public function userHasAllBadges($userId)
    {
        $categories = $this->getCategories();

        foreach ($categories as $cat) {
            if (!$this->userHasBadgeForCategory($userId, $cat['id'])) {
                return false;
            }
        }

        return true;
    }
}
?>
