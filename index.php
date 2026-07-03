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

<?php
require "includes/db.php";

// Le site public n'affiche QUE la villa en location (statut publie).
$req = $pdo->prepare("SELECT * FROM villas WHERE statut = 'publie' LIMIT 1");
$req->execute();
$villa = $req->fetch();

include "includes/header.php";
?>

  <main>

    <!-- Accroche -->
    <section class="hero">
      <h1>Une villa d'exception, au cœur de la vallée</h1>
      <p>Un séjour raffiné, dans un lieu choisi avec soin.</p>
      <a href="#villa" class="btn">Découvrir la villa</a>
    </section>

    <?php if ($villa): ?>
    <!-- location, lue depuis la base -->
    <h2 id="villa">Notre villa en location</h2>
    <article class="vedette">
      <div class="photo"></div>
      <div class="infos">
        <span class="badge">Disponible</span>
        <h3><?= htmlspecialchars($villa['nom']) ?></h3>
        <p><?= htmlspecialchars($villa['description']) ?></p>
        <p class="meta"><?= (int) $villa['capacite'] ?> voyageurs · <?= htmlspecialchars($villa['couchage']) ?></p>
        <p class="prix"><?= number_format($villa['prix_nuit'], 0, ',', ' ') ?> € <span style="font-weight:400;font-size:14px;color:var(--anthracite)">/ nuit</span></p>
        <a href="#" class="btn">Réserver ce séjour</a>
      </div>
    </article>
    <?php else: ?>
    <p>Aucune villa n'est disponible à la location pour le moment.</p>
    <?php endif; ?>

  </main>

<?php include "includes/footer.php"; ?>
  
</body>
</html>