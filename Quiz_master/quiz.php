<?php
// quiz.php
require_once 'config.php';
require_once 'auth.php';
require_once 'quiz_functions.php';
requireLogin();

$quizId = (int)($_GET['id'] ?? 0);
$quizManager = new QuizManager($pdo);
$username = $_SESSION['username'];

// vérifier quiz
$quiz = $quizManager->getQuizById($quizId);
if (!$quiz) {
    $_SESSION['error'] = "Quiz introuvable.";
    redirect('dashboard.php');
}

// vérifier accès
$quizzesInCategory = $quizManager->getQuizzesByCategory($quiz['category_id'], $_SESSION['user_id']);
$canAccess = false;
foreach ($quizzesInCategory as $q) {
    if ($q['id'] == $quizId && $q['unlocked']) { $canAccess = true; break; }
}
if (!$canAccess) {
    $_SESSION['error'] = "Quiz verrouillé ou inaccessible.";
    redirect('category.php?id=' . $quiz['category_id']);
}

// questions
$questions = $quizManager->getQuizQuestions($quizId);
if (empty($questions)) {
    $_SESSION['error'] = "Aucune question pour ce quiz.";
    redirect('category.php?id=' . $quiz['category_id']);
}

// TIMER : 1 minutes = 60s
$time_limit = 60; // secondes
$session_key = "quiz_start_time_{$quizId}";

// si retry → redémarrer timer
if (isset($_GET['retry']) && $_GET['retry'] == '1') {
    $_SESSION[$session_key] = time();
    redirect("quiz.php?id={$quizId}");
}

// premier accès → créer timer
if (!isset($_SESSION[$session_key])) {
    $_SESSION[$session_key] = time();
}

$started_at = (int)$_SESSION[$session_key];
$elapsed = time() - $started_at;
$remaining = max(0, $time_limit - $elapsed);

// soumission
$timeout_occurred = false;
if (isset($_POST['submit_quiz'])) {
    $current_elapsed = time() - $started_at;
    if ($current_elapsed > $time_limit) {
        $timeout_occurred = true;
    }
    //recuper les reponse
    $answers = [];
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'question_') === 0) {
            $qid = (int)str_replace('question_', '', $k);
            $answers[$qid] = $v;
        }
    }

    $result = $quizManager->submitQuiz($quizId, $_SESSION['user_id'], $answers, $timeout_occurred);
    unset($_SESSION[$session_key]);
    $_SESSION['quiz_result'] = $result;

    if ($timeout_occurred) {
        $_SESSION['message'] = "Le temps est écoulé. Vos réponses envoyées ont été prises en compte.";
    }

    redirect('result.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?php echo ($quiz['title']); ?></title>
<link rel="stylesheet" href="style.css">

<style>
/* ANIMATION FIN TIMER */
#timeout-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255,0,0,0.85);
    color: white;
    font-size: 3rem;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.5s ease;
}

#timeout-overlay.show {
    opacity: 1;
    pointer-events: all;
}
</style>

</head>
<body>
<header><div class="container">
    <h1><?php echo ($quiz['title']); ?></h1>
    <div class="user-info">
        <span>Bonjour, <?php echo ($username); ?></span>
        <a href="category.php?id=<?php echo $quiz['category_id']; ?>" class="btn btn-outline">Retour</a>
    </div>
</div></header>

<!-- OVERLAY FIN DE TIMER -->
<div id="timeout-overlay">⏰ Temps écoulé !</div>

<main class="container">
    <div class="quiz-header">
        <p><?php echo ($quiz['description'] ?? ''); ?></p>
        <p><strong>Temps restant :</strong>
            <span id="timer-display"><?php echo gmdate("i:s", $remaining); ?></span>
            (<?php echo $time_limit; ?>s total)
        </p>
        <p><strong>Nombre de questions :</strong> <?php echo count($questions); ?></p>
    </div>

    <form method="post" class="quiz-form">
        <?php foreach ($questions as $index => $question): ?>
            <div class="question-card">
                <h3>Question <?php echo $index + 1; ?></h3>
                <p class="question-text">
                    <?php echo htmlspecialchars($question['question_text']); ?>
                </p>
                <div class="answers">
                    <?php foreach ($question['answers'] as $answer): ?>
                        <label class="answer-option">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="<?php echo $answer['id']; ?>">
                            <span class="answer-text"><?php echo htmlspecialchars ($answer['answer_text'] ?? $answer['text'] ?? ''); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="quiz-actions">
            <button type="submit" name="submit_quiz" class="btn btn-primary">Soumettre le quiz</button>
            <a href="quiz.php?id=<?php echo $quizId; ?>&retry=1" class="btn btn-outline">Réinitialiser le timer / Repasser</a>
            <a href="category.php?id=<?php echo $quiz['category_id']; ?>" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</main>

<script>
let remaining = <?php echo $remaining; ?>;

// BIP ALERTE (Web Audio API)
function playBeep() {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = "square";
    osc.frequency.value = 800;

    gain.gain.setValueAtTime(0.2, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.start();
    osc.stop(ctx.currentTime + 0.3);
}

document.addEventListener("DOMContentLoaded", () => {
    const timerDisplay = document.getElementById("timer-display");
    const overlay = document.getElementById("timeout-overlay");

    const interval = setInterval(() => {
        remaining--;

        // BIP 5 dernières secondes
        if (remaining <= 5 && remaining > 0) {
            playBeep();
        }

        // FIN TIMER
        if (remaining <= 0) {
            clearInterval(interval);

            overlay.classList.add("show");

            // Soumettre automatiquement
            document.querySelector(".quiz-form").submit();

            // Sécurité : redirection forcée
            setTimeout(() => {
                window.location.href = "result.php";
            }, 1500);

            return;
        }

        const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
        const seconds = String(remaining % 60).padStart(2, '0');
        timerDisplay.textContent = `${minutes}:${seconds}`;
    }, 1000);
});
</script>

</body>
</html>
