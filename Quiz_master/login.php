<?php
// login.php
require_once 'config.php';
require_once 'auth.php';

// Fonction de redirection simple si elle n'existe pas
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

$auth = new Auth($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($auth->login($username, $password)) {
        redirect('dashboard.php');
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>

<div class="form-wrapper">
    <h1>Connexion</h1>

    <?php if (!empty($error)): ?>
        <div class="message error"><?php echo ($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="input-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" name="login" class="btn-submit">Se connecter</button>

        <div class="form-separator"></div>

        <p>
            Pas encore inscrit ?
            <a href="register.php">Créer un compte</a>
        </p>
    </form>
</div>

</body>
</html>
<style>
body {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
  padding: 20px;
}

.form-wrapper {
  width: 100%;
  max-width: 440px;
}
</style>