<?php
// certificate.php
require_once 'config.php';
require_once 'auth.php';
require_once 'quiz_functions.php';
requireLogin();

$quizManager = new QuizManager($pdo);
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

if (!$quizManager->userHasAllBadges($userId)) {
    $_SESSION['error'] = "Vous n'avez pas encore tous les badges.";
    redirect('profil.php');
}

// simple certificat HTML (l'utilisateur peut utiliser Imprimer -> PDF)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Certificat - <?php echo htmlspecialchars($username); ?></title>
<link rel="stylesheet" href="style.css">
<style>
/* style simple pour impression */
.certificate {
  width: 800px; margin: 50px auto; padding: 40px; text-align: center;
  border: 8px solid #444; background: #fff;
}
h1 { font-size: 32px; color: #333; }
p { font-size: 18px; }
</style>
</head>
<body>
<div class="certificate">
  <h1>Certificat de réussite</h1>
  <p>Ce certificat atteste que</p>
  <h2><?php echo htmlspecialchars($username); ?></h2>
  <p>a complété avec succès tous les quizzes et obtenu tous les badges.</p>
  <p>Date : <?php echo date('d/m/Y'); ?></p>
  <p><a href="profil.php" class="btn btn-outline">Retour au profil</a></p>
</div>
</body>
</html>
