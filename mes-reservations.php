<?php
session_start();
if (!isset($_SESSION["client_id"])) {
    header("Location: connexion.php");
    exit;
}
require "includes/db.php";

$clientId = $_SESSION["client_id"];

// --- Barème de remboursement (modéré) ---
//  > 20 jours avant l'arrivée : 100 %
//  de 20 à 4 jours            :  80 %
//  < 4 jours                  :   0 %
define("SEUIL_TOTAL",   20);
define("SEUIL_PARTIEL",  4);

$messageSucces = "";
$messageErreur = "";

// Une réservation est annulable tant qu'elle est active
function estAnnulable($resa) {
    return in_array($resa["statut"], ["en_attente", "confirmee"]);
}

// Pourcentage remboursé selon le nombre de jours avant l'arrivée
function pourcentageRemboursement($dateArrivee) {
    $arrivee    = new DateTime($dateArrivee);
    $aujourdhui = new DateTime("today");
    if ($arrivee < $aujourdhui) {
        return 0;   // séjour déjà commencé ou passé
    }
    $jours = (int) $aujourdhui->diff($arrivee)->days;
    if ($jours > SEUIL_TOTAL)   return 100;
    if ($jours >= SEUIL_PARTIEL) return 90;
    return 0;
}

// ---- Traitement de l'annulation ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["annuler_id"])) {
    $resaId = (int) $_POST["annuler_id"];

    $req = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND client_id = ?");
    $req->execute([$resaId, $clientId]);
    $resa = $req->fetch();

    if (!$resa) {
        $messageErreur = "Réservation introuvable.";
    } elseif ($resa["statut"] === "annulee") {
        $messageErreur = "Cette réservation est déjà annulée.";
    } elseif (!estAnnulable($resa)) {
        $messageErreur = "Cette réservation ne peut pas être annulée.";
    } else {
        // Calcul du remboursement côté serveur, au moment de l'annulation
        $pct     = pourcentageRemboursement($resa["date_arrivee"]);
        $montant = $resa["prix_total"] * $pct / 100;

        $upd = $pdo->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ? AND client_id = ?");
        $upd->execute([$resaId, $clientId]);

        $montantFmt = number_format($montant, 2, ',', ' ');
        if ($pct > 0) {
            $messageSucces = "Réservation annulée. Un remboursement de $pct % ($montantFmt €) vous sera effectué une fois le paiement en ligne mis en place.";
        } else {
            $messageSucces = "Réservation annulée. Selon le délai, aucun remboursement n'est prévu.";
        }
    }
}

// ---- Chargement des réservations ----
$reqResa = $pdo->prepare(
    "SELECT r.*, v.nom AS villa_nom
     FROM reservations r
     JOIN villas v ON v.id = r.villa_id
     WHERE r.client_id = ?
     ORDER BY r.date_arrivee DESC"
);
$reqResa->execute([$clientId]);
$reservations = $reqResa->fetchAll();

$pageActive = "reservations";
include "includes/header.php";
?>

  <main>
    <?php include "includes/menu-compte.php"; ?>

    <div class="form-card">
      <h1>Mes réservations</h1>

      <?php if ($messageSucces): ?>
        <p class="succes"><?= htmlspecialchars($messageSucces) ?></p>
      <?php endif; ?>
      <?php if ($messageErreur): ?>
        <p class="erreur"><?= htmlspecialchars($messageErreur) ?></p>
      <?php endif; ?>

      <?php if (!$reservations): ?>
        <p class="meta">Vous n'avez pas encore de réservation.</p>
        <a href="reservation.php" class="btn" style="margin-top:12px">Réserver un séjour</a>
      <?php else: ?>
        <?php foreach ($reservations as $r): ?>
          <div class="resa-item">
            <div class="resa-head">
              <strong><?= htmlspecialchars($r["villa_nom"]) ?></strong>
              <span class="badge-statut statut-<?= htmlspecialchars($r["statut"]) ?>">
                <?= htmlspecialchars(ucfirst(str_replace("_", " ", $r["statut"]))) ?>
              </span>
            </div>
            <p class="meta">
              Du <?= date("d/m/Y", strtotime($r["date_arrivee"])) ?>
              au <?= date("d/m/Y", strtotime($r["date_depart"])) ?>
              · <?= (int) $r["nb_nuits"] ?> nuit<?= $r["nb_nuits"] > 1 ? "s" : "" ?>
              · <?= (int) $r["nb_adultes"] ?> adulte<?= $r["nb_adultes"] > 1 ? "s" : "" ?><?= $r["nb_enfants"] ? ", " . (int) $r["nb_enfants"] . " enfant" . ($r["nb_enfants"] > 1 ? "s" : "") : "" ?>
            </p>
            <p class="prix"><?= number_format($r["prix_total"], 2, ',', ' ') ?> €</p>

            <?php if (estAnnulable($r)): ?>
              <?php
                $pct     = pourcentageRemboursement($r["date_arrivee"]);
                $montant = $r["prix_total"] * $pct / 100;
                $montantFmt = number_format($montant, 2, ',', ' ');
              ?>
              <p class="resa-limite">Si vous annulez aujourd'hui : remboursement de <?= $pct ?> % (<?= $montantFmt ?> €)</p>
              <form method="post" onsubmit="return confirm('Annuler cette réservation ? Remboursement prévu : <?= $montantFmt ?> €');">
                <input type="hidden" name="annuler_id" value="<?= (int) $r["id"] ?>">
                <button type="submit" class="btn-annuler">Annuler la réservation</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>

<?php include "includes/footer.php"; ?>
