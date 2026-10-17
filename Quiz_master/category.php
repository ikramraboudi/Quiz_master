<?php
// category.php
require_once 'config.php';
require_once 'auth.php';
require_once 'quiz_functions.php';
requireLogin();

$quizManager = new QuizManager($pdo);
$categoryId = $_GET['id'] ?? 0;
$category = $quizManager->getCategory($categoryId);
if (!$category) redirect('dashboard.php');

$quizzes = $quizManager->getQuizzesByCategory($categoryId, $_SESSION['user_id']);
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?php echo ($category['name']); ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="container">
    <h1><?php echo ($category['name']); ?></h1>
    <div class="user-info">
      <span>Bonjour, <?php echo ($username); ?></span>
      <a href="dashboard.php" class="btn btn-outline">Retour</a>
    </div>
  </div>
</header>

<main class="container">
  <p class="category-description"><?php echo ($category['description'] ?? ''); ?></p>

  <div class="quizzes-grid">
     <!--afichage de quiz -->
    <?php foreach ($quizzes as $quiz): ?> 
      <div class="quiz-card <?php echo $quiz['unlocked'] ? 'unlocked' : 'locked'; ?>">
        <h3><?php echo ($quiz['title']); ?></h3>
        <p><?php echo ($quiz['description'] ?? ''); ?></p>

        <!-- Statut du quiz -->
        <?php 
          $statusClass = 'locked';
          $statusText = 'Verrouillé';
          $userScore = intval($quiz['user_score'] ?? 0);
          $minScore = intval($quiz['min_score'] ?? 70);

          if ($quiz['unlocked']) {
              if (!empty($quiz['user_completed'])) {
                  $statusClass = $userScore >= $minScore ? 'completed' : 'warning';
                  $statusText = $userScore >= $minScore ? 'Réussi' : 'À améliorer';
              } else {
                  $statusClass = 'available';
                  $statusText = 'Disponible';
              }
          }
        ?>
        <div class="quiz-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>

        <!-- Score et boutons -->
        <?php if ($quiz['unlocked']): ?>
            <?php if (!empty($quiz['user_completed'])): ?>
                <div class="score">Score : <?php echo $userScore; ?>%</div>
                <?php if ($userScore < $minScore): ?>
                    <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="btn">Repasser le quiz</a>
                <?php else: ?>
                    <span class="btn btn-outline">Quiz réussi</span>
                <?php endif; ?>
            <?php else: ?>
                <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="btn">Commencer</a>
            <?php endif; ?>
        <?php else: ?>
            <button class="btn" disabled>Quiz verrouillé</button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</main>
</body>
</html>
