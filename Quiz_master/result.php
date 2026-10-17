<?php
// result.php
require_once 'config.php';
require_once 'auth.php';
require_once 'quiz_functions.php';
requireLogin();

$quizManager = new QuizManager($pdo);
$result = $_SESSION['quiz_result'] ?? null;
$message = $_SESSION['message'] ?? null;

if (!$result) redirect('dashboard.php');

// On récupère le quiz
$quiz = $quizManager->getQuizById($result['quiz_id']);
$username = $_SESSION['username'];

// On récupère le badge pour affichage MAIS sans le regénérer
$stmt = $pdo->prepare("SELECT badge_name FROM user_badges WHERE user_id = ? AND category_id = ?");
$stmt->execute([$_SESSION['user_id'], $quiz['category_id']]);
$earnedBadge = $stmt->fetchColumn();

// Lire le flag du popup
$newBadgeUnlocked = !empty($_SESSION['new_badge']);
unset($_SESSION['quiz_result'], $_SESSION['message'], $_SESSION['new_badge']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Résultats du quiz</title>
<link rel="stylesheet" href="style.css">

<!-- Styles minimaux pour le popup (ajoutés pour fiabiliser l'affichage) -->
<style>
/* Overlay popup */
#badge-popup {
  position: fixed;
  inset: 0;
  display: none; /* caché par défaut */
  align-items: center;
  justify-content: center;
  z-index: 9999;
  background: rgba(0,0,0,0.4);
  padding: 1rem;
}

/* visible state */
#badge-popup.open {
  display: flex;
}

/* Contenu du popup */
.badge-popup-content {
  background: #fff;
  border-radius: 8px;
  padding: 1.5rem;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 10px 30px rgba(0,0,0,0.12);
  text-align: center;
  position: relative;
}

/* Image du badge */
.badge-animation {
  max-width: 160px;
  width: 50%;
  height: auto;
  display: block;
  margin: 0.8rem auto;
  transform-origin: center;
  animation: pop 0.8s ease;
}

/* Simple animation */
@keyframes pop {
  0% { transform: scale(0.4) rotate(-10deg); opacity: 0; }
  60% { transform: scale(1.08) rotate(5deg); opacity: 1; }
  100% { transform: scale(1) rotate(0deg); }
}

/* Bouton fermer */
#badge-ok {
  margin-top: 0.6rem;
  padding: 0.5rem 1rem;
  border: none;
  background: #2d89ef;
  color: #fff;
  border-radius: 6px;
  cursor: pointer;
}

/* Small accessibility focus */
#badge-ok:focus {
  outline: 3px solid rgba(45,137,239,0.25);
}

/* Prevent page scroll when popup open */
body.popup-open {
  overflow: hidden;
}
</style>
</head>
<body>

<header>
    <div class="container">
        <h1>Résultats</h1>
        <div class="user-info">
            <span>Bonjour, <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="dashboard.php" class="btn btn-outline">Tableau de bord</a>
        </div>
    </div>
</header>

<main class="container">

    <?php if ($message): ?>
        <div class="message warning"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="results-container">
        <h2><?php echo htmlspecialchars($quiz['title'], ENT_QUOTES, 'UTF-8'); ?></h2>

        <div class="score-display">
            <div class="score-value-large"><?php echo round($result['percentage']); ?>%</div>

            <div class="score-details">
                <p><strong>Réponses correctes :</strong> <?php echo (int)$result['correct'] . " / " . (int)$result['total']; ?></p>
                <p><strong>Pourcentage :</strong> <?php echo round($result['percentage']); ?>%</p>
                <p><strong>Score requis :</strong> <?php echo (int)($quiz['min_score'] ?? 70); ?>%</p>
            </div>
        </div>

        <!-- Message badge -->
        <?php if ($earnedBadge): ?>
            <div class="message success">
                🎖️ <strong>Bravo !</strong> Nouveau badge obtenu :
                <span class="badge"><?php echo htmlspecialchars($earnedBadge, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <!-- Succès ou échec -->
        <?php if ($result['percentage'] >= ($quiz['min_score'] ?? 70)): ?>
            <div class="message success">
                <h3>Félicitations !</h3>
                <p>Vous avez réussi ce quiz.</p>
            </div>
        <?php else: ?>
            <div class="message error">
                <h3>Échec</h3>
                <p>Vous pouvez réessayer ce quiz.</p>
            </div>
        <?php endif; ?>

        <div class="results-actions">
            <a href="category.php?id=<?php echo (int)$quiz['category_id']; ?>" class="btn">Retour aux quiz</a>
            <a href="quiz.php?id=<?php echo (int)$quiz['id']; ?>&retry=1" class="btn btn-primary">Repasser le quiz</a>
            <a href="dashboard.php" class="btn btn-outline">Tableau de bord</a>
        </div>
    </div>
</main>

</body>
</html>
