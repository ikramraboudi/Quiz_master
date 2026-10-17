<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'quiz_functions.php';

requireLogin();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

$quizManager = new QuizManager($pdo);

$categories = $quizManager->getCategories();

$badges = [];
foreach ($categories as $cat) {
    $badges[$cat['id']] = $quizManager->userHasBadgeForCategory($userId, $cat['id']);
}

$allBadges = $quizManager->userHasAllBadges($userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Profil - <?php echo htmlspecialchars($username); ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
 <div class="container">
   <h1>Profil</h1>
   <span><?php echo htmlspecialchars($username); ?></span>
   <a href="dashboard.php" class="btn btn-outline">Retour</a>
 </div>
</header>

<main class="container">

<h2>Vos badges</h2>

<div class="categories-grid">
<?php foreach ($categories as $cat): ?>
    <div class="category-card">
        <h3><?php echo htmlspecialchars($cat['name']); ?></h3>

        <?php if ($badges[$cat['id']]): ?>
            <div class="message success">🎖️ Badge obtenu</div>
        <?php else: ?>
            <div class="message warning">Badge non obtenu</div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<h2>Certificat</h2>

<?php if ($allBadges): ?>
    <div class="message success">
        Vous avez obtenu tous les badges !  
        <a href="certificate.php" class="btn">Télécharger Certificat</a>
    </div>
<?php else: ?>
    <div class="message warning">
        Terminez toutes les catégories pour recevoir le certificat.
    </div>
<?php endif; ?>

</main>
</body>
</html>
