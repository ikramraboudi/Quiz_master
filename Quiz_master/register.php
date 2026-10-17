<?php
// register.php
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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } else {
        if ($auth->register($username, $email, $password)) {
            $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        } else {
            $error = "Nom d'utilisateur ou email déjà utilisé.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="form-wrapper">
    <h1>Créer un compte</h1>

    <?php if (!empty($error)): ?>
        <div class="message error"><?php echo ($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="message success"><?php echo ($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="input-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="input-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="input-group">
            <label for="confirm">Confirmer le mot de passe</label>
            <input type="password" id="confirm" name="confirm" required>
        </div>

        <button type="submit" class="btn-submit">Créer le compte</button>

        <div class="form-separator"></div>

        <p>
            Déjà inscrit ?
            <a href="login.php">Connexion</a>
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