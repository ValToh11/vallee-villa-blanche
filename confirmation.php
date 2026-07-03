<?php
session_start();

if (!isset($_SESSION["client_id"])) {
    header("Location: connexion.php");
    exit;
}

require "includes/db.php";

$id = (int) ($_GET["id"] ?? 0);

// SÉCURITÉ : on ne charge la réservation QUE si elle appartient au client connecté
$req = $pdo->prepare(
    "SELECT r.*, v.nom AS villa_nom
     FROM reservations r
     JOIN villas v ON v.id = r.villa_id
     WHERE r.id = ? AND r.client_id = ?"
);
$req->execute([$id, $_SESSION["client_id"]]);
$resa = $req->fetch();

include "includes/header.php";
?>

  <main>
    <div class="form-card">
      <?php if (!$resa): ?>

        <h1>Réservation introuvable</h1>
        <p class="erreur">Cette réservation n'existe pas ou ne vous appartient pas.</p>
        <a href="index.php" class="btn">Retour à l'accueil</a>

      <?php else: ?>

        <h1>Réservation enregistrée</h1>
        <p class="succes">Votre demande de réservation a bien été enregistrée.</p>

        <p><strong><?= htmlspecialchars($resa["villa_nom"]) ?></strong></p>
        <p>Du <?= date("d/m/Y", strtotime($resa["date_arrivee"])) ?>
           au <?= date("d/m/Y", strtotime($resa["date_depart"])) ?></p>
        <p><?= (int) $resa["nb_nuits"] ?> nuit<?= $resa["nb_nuits"] > 1 ? "s" : "" ?> ·
           <?= (int) $resa["nb_adultes"] ?> adulte<?= $resa["nb_adultes"] > 1 ? "s" : "" ?><?= $resa["nb_enfants"] ? ", " . (int) $resa["nb_enfants"] . " enfant" . ($resa["nb_enfants"] > 1 ? "s" : "") : "" ?></p>
        <p class="prix" style="font-size:24px"><?= number_format($resa["prix_total"], 2, ',', ' ') ?> €</p>

        <p class="form-lien">Statut : en attente de paiement. Le règlement en ligne arrivera bientôt.</p>
        <a href="index.php" class="btn">Retour à l'accueil</a>

      <?php endif; ?>
    </div>
  </main>

<?php include "includes/footer.php"; ?>
