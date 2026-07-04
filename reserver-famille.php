<?php
session_start();
if (!isset($_SESSION["famille_id"])) {
    header("Location: connexion-famille.php");
    exit;
}
require "includes/db.php";

// Villa choisie (la famille peut réserver n'importe laquelle des 4)
$villaId = (int) ($_GET["villa"] ?? 0);
$req = $pdo->prepare("SELECT * FROM villas WHERE id = ?");
$req->execute([$villaId]);
$villa = $req->fetch();

if (!$villa) {
    include "includes/header.php";
    echo '<main><div class="form-card"><p class="erreur">Villa introuvable.</p><a href="intranet.php" class="btn">Retour à l\'intranet</a></div></main>';
    include "includes/footer.php";
    exit;
}

$capacite = (int) $villa["capacite"];
$erreurs  = [];
$succes   = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $arrivee = $_POST["date_arrivee"] ?? "";
    $depart  = $_POST["date_depart"] ?? "";
    $adultes = (int) ($_POST["adultes"] ?? 1);
    $enfants = (int) ($_POST["enfants"] ?? 0);

    $dArrivee   = DateTime::createFromFormat("Y-m-d", $arrivee);
    $dDepart    = DateTime::createFromFormat("Y-m-d", $depart);
    $aujourdhui = new DateTime("today");

    if (!$dArrivee || !$dDepart) {
        $erreurs[] = "Veuillez sélectionner vos dates de séjour.";
    } elseif ($dArrivee < $aujourdhui) {
        $erreurs[] = "La date d'arrivée ne peut pas être dans le passé.";
    } elseif ($dDepart <= $dArrivee) {
        $erreurs[] = "La date de départ doit être postérieure à la date d'arrivée.";
    }
    if ($adultes < 1) {
        $erreurs[] = "Il faut au moins un adulte.";
    }
    if (($adultes + $enfants) > $capacite) {
        $erreurs[] = "Cette villa accueille au maximum $capacite voyageurs.";
    }

    // Anti-chevauchement : on regarde TOUTES les réservations actives (clients + famille)
    if (!$erreurs) {
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM reservations
             WHERE villa_id = ? AND statut IN ('confirmee','en_attente')
               AND date_arrivee < ? AND date_depart > ?"
        );
        $check->execute([$villa["id"], $dDepart->format("Y-m-d"), $dArrivee->format("Y-m-d")]);
        if ($check->fetchColumn() > 0) {
            $erreurs[] = "Ces dates sont déjà réservées pour cette villa.";
        }
    }

    // Enregistrement : réservation famille, gratuite, confirmée d'emblée (pas de paiement)
    if (!$erreurs) {
        $nbNuits = (int) $dArrivee->diff($dDepart)->days;
        $ins = $pdo->prepare(
            "INSERT INTO reservations
             (villa_id, client_id, famille_id, date_arrivee, date_depart, nb_nuits, prix_total, nb_adultes, nb_enfants, statut)
             VALUES (?, NULL, ?, ?, ?, ?, 0, ?, ?, 'confirmee')"
        );
        $ins->execute([
            $villa["id"], $_SESSION["famille_id"],
            $dArrivee->format("Y-m-d"), $dDepart->format("Y-m-d"),
            $nbNuits, $adultes, $enfants,
        ]);
        $succes = [
            "arrivee" => $dArrivee->format("d/m/Y"),
            "depart"  => $dDepart->format("d/m/Y"),
            "nuits"   => $nbNuits,
        ];
    }
}

// Dates déjà occupées, pour les griser
$reqOcc = $pdo->prepare(
    "SELECT date_arrivee, date_depart FROM reservations
     WHERE villa_id = ? AND statut IN ('en_attente','confirmee')"
);
$reqOcc->execute([$villa["id"]]);
$plagesOccupees = [];
foreach ($reqOcc->fetchAll() as $o) {
    $fin = (new DateTime($o["date_depart"]))->modify("-1 day")->format("Y-m-d");
    $plagesOccupees[] = ["from" => $o["date_arrivee"], "to" => $fin];
}

include "includes/header.php";
?>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <main>
    <div class="reservation-layout">
      <div class="form-card">
        <h1>Réserver — famille</h1>
        <p class="meta"><strong><?= htmlspecialchars($villa["nom"]) ?></strong></p>
        <p class="meta"><?= $capacite ?> voyageurs max · <?= htmlspecialchars($villa["couchage"]) ?></p>

        <?php if ($succes): ?>

          <p class="succes">Séjour réservé pour la famille, du <strong><?= $succes["arrivee"] ?></strong>
             au <strong><?= $succes["depart"] ?></strong> (<?= $succes["nuits"] ?> nuit<?= $succes["nuits"] > 1 ? "s" : "" ?>).</p>
          <a href="intranet.php" class="btn">Retour à l'intranet</a>

        <?php else: ?>

          <?php foreach ($erreurs as $e): ?>
            <p class="erreur"><?= htmlspecialchars($e) ?></p>
          <?php endforeach; ?>

          <form method="post">
            <label>Vos dates de séjour
              <input type="text" id="calendrier" placeholder="Sélectionnez vos dates" required>
            </label>
            <input type="hidden" name="date_arrivee" id="date_arrivee">
            <input type="hidden" name="date_depart"  id="date_depart">

            <div class="voyageurs">
              <div class="stepper">
                <span>Adultes</span>
                <div class="stepper-ctrl">
                  <button type="button" onclick="modifier('adultes', -1)">−</button>
                  <input type="text" name="adultes" id="adultes" value="1" readonly>
                  <button type="button" onclick="modifier('adultes', 1)">+</button>
                </div>
              </div>
              <div class="stepper">
                <span>Enfants</span>
                <div class="stepper-ctrl">
                  <button type="button" onclick="modifier('enfants', -1)">−</button>
                  <input type="text" name="enfants" id="enfants" value="0" readonly>
                  <button type="button" onclick="modifier('enfants', 1)">+</button>
                </div>
              </div>
            </div>

            <div id="prix-live" class="prix-live"></div>

            <button type="submit" class="btn">Réserver gratuitement</button>
          </form>

        <?php endif; ?>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
  <script>
    const CAPACITE = <?= $capacite ?>;
    const OCCUPEES = <?= json_encode($plagesOccupees) ?>;

    flatpickr("#calendrier", {
      mode: "range", showMonths: 2, minDate: "today", locale: "fr",
      dateFormat: "Y-m-d", altInput: true, altFormat: "d/m/Y",
      disable: OCCUPEES,
      onChange: function(dates) {
        if (dates.length === 2) {
          document.getElementById("date_arrivee").value = fmt(dates[0]);
          document.getElementById("date_depart").value  = fmt(dates[1]);
          const nuits = Math.round((dates[1] - dates[0]) / 86400000);
          document.getElementById("prix-live").innerHTML =
            nuits + " nuit" + (nuits > 1 ? "s" : "") + " · <strong>Gratuit pour la famille</strong>";
        }
      }
    });

    function fmt(d) {
      return d.getFullYear() + "-" + String(d.getMonth()+1).padStart(2,"0") + "-" + String(d.getDate()).padStart(2,"0");
    }
    function modifier(champ, delta) {
      const input = document.getElementById(champ);
      let val = parseInt(input.value) + delta;
      const min = (champ === "adultes") ? 1 : 0;
      if (val < min) val = min;
      const autre = parseInt(document.getElementById(champ === "adultes" ? "enfants" : "adultes").value);
      if (val + autre > CAPACITE) return;
      input.value = val;
    }
  </script>

<?php include "includes/footer.php"; ?>
