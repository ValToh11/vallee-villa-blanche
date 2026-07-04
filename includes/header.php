<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vallée Villa Blanche</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Work+Sans&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <header class="entete">
    <strong>Vallée Villa Blanche</strong>
    <nav>
      <a href="index.php">Accueil</a>
      <?php if (isset($_SESSION["client_id"])): ?>
        <span class="salut">Bonjour <?= htmlspecialchars($_SESSION["client_prenom"]) ?></span>
        <a href="profil.php">Mon compte</a>
        <a href="deconnexion.php">Déconnexion</a>
      <?php elseif (isset($_SESSION["famille_id"])): ?>
        <span class="salut">Famille · <?= htmlspecialchars($_SESSION["famille_prenom"]) ?></span>
        <a href="intranet.php">Les villas</a>
        <a href="planning-famille.php">Réservations</a>
        <a href="profil-famille.php">Mon profil</a>
        <a href="deconnexion-famille.php">Déconnexion</a>
      <?php else: ?>
        <a href="connexion.php">Connexion</a>
        <a href="inscription.php">Inscription</a>
        <a href="connexion-famille.php" class="lien-famille">Espace famille</a>
      <?php endif; ?>
    </nav>
  </header>
