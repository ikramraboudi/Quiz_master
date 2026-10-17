<?php
// config.php

// Démarrage de session sécurisé
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=quiz_manager', 'root','', 
        [        
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
//rediriger vers une autre page
function redirect($url) {
    header("Location: $url");
    exit;
}
//savoir si l’utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
//bloque l’accès aux pages si l’utilisateur n’est pas connecté
function requireLogin() {
    if (!isLoggedIn()) redirect('login.php');
}
?>