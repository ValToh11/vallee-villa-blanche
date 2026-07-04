<?php
session_start();

// ---- Réservé aux clients connectés ----
if (!isset($_SESSION["client_id"])) {
    header("Location: connexion.php");
    exit;
}

require "includes/db.php";

// La villa en location (publique)
$req = $pdo->prepare("SELECT * FROM villas WHERE statut = 'publie' LIMIT 1");
$req->execute();
$villa = $req->fetch();
$capacite = (int) $villa["capacite"];

$erreurs = [];

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

    // Vérification de disponibilité (LA sécurité : le dernier mot revient au serveur)
    if (!$erreurs) {
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM reservations
             WHERE villa_id = ?
               AND statut IN ('confirmee','en_attente')
               AND date_arrivee < ?
               AND date_depart > ?"
        );
        $check->execute([
            $villa["id"],
            $dDepart->format("Y-m-d"),
            $dArrivee->format("Y-m-d"),
        ]);
        if ($check->fetchColumn() > 0) {
            $erreurs[] = "Ces dates ne sont plus disponibles. Merci d'en choisir d'autres.";
        }
    }

    if (!$erreurs) {
        $nbNuits   = (int) $dArrivee->diff($dDepart)->days;
        $prixTotal = $nbNuits * (float) $villa["prix_nuit"];

        $ins = $pdo->prepare(
            "INSERT INTO reservations
             (villa_id, client_id, date_arrivee, date_depart, nb_nuits, prix_total, nb_adultes, nb_enfants, statut)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')"
        );
        $ins->execute([
            $villa["id"], $_SESSION["client_id"],
            $dArrivee->format("Y-m-d"), $dDepart->format("Y-m-d"),
            $nbNuits, $prixTotal, $adultes, $enfants,
        ]);

        $reservationId = $pdo->lastInsertId();
        header("Location: confirmation.php?id=" . $reservationId);
        exit;
    }
}

// ---- Dates déjà occupées, pour les griser dans le calendrier ----
$reqOcc = $pdo->prepare(
    "SELECT date_arrivee, date_depart FROM reservations
     WHERE villa_id = ? AND statut IN ('en_attente','confirmee')"
);
$reqOcc->execute([$villa["id"]]);
$occupees = $reqOcc->fetchAll();

// On construit les plages à bloquer. Le jour de départ reste libre
// (on peut arriver le jour où un autre séjour se termine), donc on va
// de la date d'arrivée jusqu'au départ MOINS un jour.
$plagesOccupees = [];
foreach ($occupees as $o) {
    $fin = (new DateTime($o["date_depart"]))->modify("-1 day")->format("Y-m-d");
    $plagesOccupees[] = ["from" => $o["date_arrivee"], "to" => $fin];
}

include "includes/header.php";
?>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <main>
    <div class="reservation-layout">

      <div class="form-card">
        <h1>Réserver</h1>
        <p class="meta"><strong><?= htmlspecialchars($villa["nom"]) ?></strong></p>
        <p class="meta"><?= $capacite ?> voyageurs max · <?= htmlspecialchars($villa["couchage"]) ?></p>

        <?php foreach ($erreurs as $e): ?>
          <p class="erreur"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>

        <form method="post" id="form-resa">
          <label>Vos dates de séjour
            <input type="text" id="calendrier" placeholder="Sélectionnez vos dates" required>
          </label>
          <input type="hidden" name="date_arrivee" id="date_arrivee" value="<?= htmlspecialchars($_POST['date_arrivee'] ?? '') ?>">
          <input type="hidden" name="date_depart"  id="date_depart"  value="<?= htmlspecialchars($_POST['date_depart'] ?? '') ?>">

          <div class="voyageurs">
            <div class="stepper">
              <span>Adultes</span>
              <div class="stepper-ctrl">
                <button type="button" onclick="modifier('adultes', -1)">−</button>
                <input type="text" name="adultes" id="adultes" value="<?= (int)($_POST['adultes'] ?? 1) ?>" readonly>
                <button type="button" onclick="modifier('adultes', 1)">+</button>
              </div>
            </div>
            <div class="stepper">
              <span>Enfants</span>
              <div class="stepper-ctrl">
                <button type="button" onclick="modifier('enfants', -1)">−</button>
                <input type="text" name="enfants" id="enfants" value="<?= (int)($_POST['enfants'] ?? 0) ?>" readonly>
                <button type="button" onclick="modifier('enfants', 1)">+</button>
              </div>
            </div>
          </div>

          <div id="prix-live" class="prix-live"></div>

          <button type="submit" class="btn">Réserver</button>
        </form>
      </div>

    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
  <script>
    const PRIX_NUIT = <?= (float) $villa["prix_nuit"] ?>;
    const CAPACITE  = <?= $capacite ?>;
    const OCCUPEES  = <?= json_encode($plagesOccupees) ?>;   // dates déjà prises, transmises de PHP au JS

    flatpickr("#calendrier", {
      mode: "range",
      showMonths: 2,
      minDate: "today",
      locale: "fr",
      dateFormat: "Y-m-d",
      altInput: true,
      altFormat: "d/m/Y",
      disable: OCCUPEES,          // Flatpickr grise ces plages
      onChange: function(dates) {
        if (dates.length === 2) {
          document.getElementById("date_arrivee").value = fmt(dates[0]);
          document.getElementById("date_depart").value  = fmt(dates[1]);
          majPrix();
        }
      }
    });

    function fmt(d) {
      return d.getFullYear() + "-" +
             String(d.getMonth() + 1).padStart(2, "0") + "-" +
             String(d.getDate()).padStart(2, "0");
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

    function majPrix() {
      const a = document.getElementById("date_arrivee").value;
      const d = document.getElementById("date_depart").value;
      if (!a || !d) return;
      const nuits = Math.round((new Date(d) - new Date(a)) / 86400000);
      if (nuits > 0) {
        const total = nuits * PRIX_NUIT;
        document.getElementById("prix-live").innerHTML =
          nuits + " nuit" + (nuits > 1 ? "s" : "") + " × " + PRIX_NUIT + " € = <strong>" +
          total.toLocaleString("fr-FR") + " € au total</strong>";
      }
    }
  </script>

<?php include "includes/footer.php"; ?>
