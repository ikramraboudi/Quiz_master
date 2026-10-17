<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'quiz_functions.php';
requireLogin();

$quizManager = new QuizManager($pdo);
$categories = $quizManager->getCategories();//récupere les cat depuis la base
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="container">
    <h1>Quiz Manager</h1>
    <div class="user-info">
      <span>Bonjour, <?php echo ($username); ?></span>
      <a href="profil.php" class="btn btn-outline">Profil</a>
      <a href="logout.php" class="btn btn-outline">Déconnexion</a>
    </div>
  </div>
</header>

<main class="container">
  <h2>Choisissez une catégorie</h2>

  <?php if (empty($categories)): ?>
    <div class="message error">Aucune catégorie disponible pour le moment.</div>

  <?php else: ?>
    <div class="categories-grid">
      <?php foreach ($categories as $category): ?>
        <div class="category-card">
          <h3><?php echo ($category['name']); ?></h3>
          <p class="category-description">
            <?php echo ($category['description'] ?? ''); ?>
          </p>
          <a href="category.php?id=<?php echo $category['id']; ?>" class="btn">
            Voir les quiz
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
