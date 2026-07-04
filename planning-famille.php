<?php
session_start();
if (!isset($_SESSION["famille_id"])) {
    header("Location: connexion-famille.php");
    exit;
}
require "includes/db.php";

$membreId = $_SESSION["famille_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["annuler_id"])) {
    $resaId = (int) $_POST["annuler_id"];
    $upd = $pdo->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ? AND famille_id = ?");
    $upd->execute([$resaId, $membreId]);
    $message = "Réservation annulée. Les dates sont de nouveau disponibles.";
}

$villas = $pdo->query("SELECT id, nom FROM villas ORDER BY statut='publie' DESC, nom")->fetchAll();

$req = $pdo->query(
    "SELECT r.id, r.villa_id, r.date_arrivee, r.date_depart, r.famille_id,
            f.prenom AS f_prenom
     FROM reservations r
     LEFT JOIN famille f ON f.id = r.famille_id
     WHERE r.statut IN ('confirmee','en_attente')
       AND r.date_depart >= CURDATE()
     ORDER BY r.date_arrivee"
);
$toutes = $req->fetchAll();

$parVilla = [];
$occupeesParVilla = [];
foreach ($toutes as $r) {
    $parVilla[$r["villa_id"]][] = $r;
    $fin = (new DateTime($r["date_depart"]))->modify("-1 day")->format("Y-m-d");
    $occupeesParVilla[$r["villa_id"]][] = ["from" => $r["date_arrivee"], "to" => $fin];
}

include "includes/header.php";
?>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <main class="planning-main">
    <h1>Planning des réservations</h1>
    <p>Réservations à venir de tous les lieux de la famille.</p>

    <?php if ($message): ?>
      <p class="succes"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php foreach ($villas as $v): ?>
      <div class="villa-row">

        <div class="form-card creneaux-card">
          <h2><?= htmlspecialchars($v["nom"]) ?></h2>
          <div class="creneaux-scroll">
            <?php if (empty($parVilla[$v["id"]])): ?>
              <p class="meta">Aucune réservation à venir.</p>
            <?php else: ?>
              <?php foreach ($parVilla[$v["id"]] as $r): ?>
                <div class="resa-item">
                  <div class="resa-head">
                    <span>
                      Du <strong><?= date("d/m/Y", strtotime($r["date_arrivee"])) ?></strong>
                      au <strong><?= date("d/m/Y", strtotime($r["date_depart"])) ?></strong>
                    </span>
                    <?php if ($r["famille_id"]): ?>
                      <span class="badge-statut statut-confirmee"><?= htmlspecialchars($r["f_prenom"]) ?></span>
                    <?php else: ?>
                      <span class="badge-statut statut-en_attente">Loué au public</span>
                    <?php endif; ?>
                  </div>
                  <?php if ($r["famille_id"] == $membreId): ?>
                    <form method="post" onsubmit="return confirm('Annuler cette réservation ?');">
                      <input type="hidden" name="annuler_id" value="<?= (int) $r["id"] ?>">
                      <button type="submit" class="btn-annuler">Annuler ma réservation</button>
                    </form>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="calendrier-inline">
          <input class="cal-cible" type="text" data-villa="<?= (int) $v["id"] ?>" style="display:none">
        </div>

      </div>
    <?php endforeach; ?>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
  <script>
    const OCCUPEES_PAR_VILLA = <?= json_encode((object) $occupeesParVilla) ?>;

    document.querySelectorAll(".cal-cible").forEach(function(input) {
      const villaId = input.dataset.villa;
      const plages  = OCCUPEES_PAR_VILLA[villaId] || [];
      const ranges  = plages.map(function(p) {
        return { from: new Date(p.from + "T00:00:00"), to: new Date(p.to + "T00:00:00") };
      });

      flatpickr(input, {
        inline: true,
        showMonths: 1,
        locale: "fr",
        onDayCreate: function(dObj, dStr, fp, dayElem) {
          const d = dayElem.dateObj;
          for (const r of ranges) {
            if (d >= r.from && d <= r.to) {
              dayElem.classList.add("jour-occupe");
              break;
            }
          }
        }
      });
    });

    function syncHauteurs() {
      document.querySelectorAll(".villa-row").forEach(function(row) {
        const cal  = row.querySelector(".flatpickr-calendar.inline");
        const card = row.querySelector(".creneaux-card");
        if (!cal || !card) return;
        card.style.height = (window.innerWidth > 780) ? cal.offsetHeight + "px" : "auto";
      });
    }
    requestAnimationFrame(syncHauteurs);
    window.addEventListener("resize", syncHauteurs);
  </script>

<?php include "includes/footer.php"; ?>
