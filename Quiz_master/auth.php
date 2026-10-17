<?php
// auth.php
require_once 'config.php';

class Auth {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }
/*Vérifie si le nom d’utilisateur ou l’email existe déjà.
Si non, il crypte le mot de passe et l’enregistre dans la base.*/
    public function register($username, $email, $password) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) return false;
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $email, $hashed]);
    }
/*Cherche l’utilisateur par nom d’utilisateur.
Vérifie si le mot de passe correspond au mot de passe crypté.
Si oui, il crée une session pour l’utilisateur.*/
    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }

    public function logout() {
        // Supprime toutes les informations de session pour déconnecter l’utilisateur.
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        redirect('login.php');
    }
}
?>
