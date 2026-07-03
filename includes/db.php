<?php
// Connexion à la base via PDO, réutilisable partout par un simple require.
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // les erreurs deviennent des exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // résultats en tableaux simples
        ]
    );
} catch (PDOException $e) {
    // En développement : on affiche l'erreur pour comprendre.
    // En production : on remplacera par un message neutre (étape sécurité).
    die("Connexion à la base impossible : " . $e->getMessage());
}
