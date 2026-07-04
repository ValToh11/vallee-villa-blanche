<?php
session_start();

// ---- Cadenas : espace réservé aux membres de la famille connectés ----
if (!isset($_SESSION["famille_id"])) {
    header("Location: connexion-famille.php");
    exit;
}

require "includes/db.php";
$villas = $pdo->query("SELECT * FROM villas ORDER BY statut = 'publie' DESC, nom")->fetchAll();
include "includes/header.php";
?>

  <main>

    <h1>Espace famille — nos lieux de vacances</h1>
    <p>Vue d'ensemble des quatre lieux de vacances de la famille Vallée. Seul celui « en location » est visible sur le site public.</p>

    <div class="grille">

      <?php foreach ($villas as $villa): ?>
        <article class="villa">
          <div class="photo"></div>
          <div class="corps">
            <?php if ($villa['statut'] === 'publie'): ?>
              <span class="badge location">En location</span>
            <?php else: ?>
              <span class="badge">Usage familial</span>
            <?php endif; ?>
            <h3><?= htmlspecialchars($villa['nom']) ?></h3>
            <p class="meta"><?= (int) $villa['capacite'] ?> voyageurs · <?= htmlspecialchars($villa['couchage']) ?></p>
            <div class="tarif">
              <span class="gratuit">Gratuit pour la famille</span>
              <?php if ($villa['statut'] === 'publie'): ?>
                <span class="public">Au public : <?= number_format($villa['prix_nuit'], 0, ',', ' ') ?> € / nuit</span>
              <?php endif; ?>
            </div>
            <a href="reservation-famille.php?villa=<?= (int) $villa['id'] ?>" class="btn">Réserver</a>
          </div>
        </article>
      <?php endforeach; ?>

    </div>

  </main>

<?php include "includes/footer.php"; ?>
