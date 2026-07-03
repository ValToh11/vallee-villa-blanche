<?php
session_start();

// ---- Réservé aux clients connectés ----
if (!isset($_SESSION["client_id"])) {
    header("Location: connexion.php");
    exit;
}

require "includes/db.php";

// La villa en location (publique) — seule réservable côté client
$req = $pdo->prepare("SELECT * FROM villas WHERE statut = 'publie' LIMIT 1");
$req->execute();
$villa = $req->fetch();

$erreurs = [];
$recap   = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $arrivee = $_POST["date_arrivee"] ?? "";
    $depart  = $_POST["date_depart"] ?? "";

    // Conversion en objets date pour pouvoir les comparer et les calculer
    $dArrivee   = DateTime::createFromFormat("Y-m-d", $arrivee);
    $dDepart    = DateTime::createFromFormat("Y-m-d", $depart);
    $aujourdhui = new DateTime("today");

    // Validation des dates
    if (!$dArrivee || !$dDepart) {
        $erreurs[] = "Veuillez choisir une date d'arrivée et une date de départ.";
    } elseif ($dArrivee < $aujourdhui) {
        $erreurs[] = "La date d'arrivée ne peut pas être dans le passé.";
    } elseif ($dDepart <= $dArrivee) {
        $erreurs[] = "La date de départ doit être postérieure à la date d'arrivée.";
    }

    // Calcul du prix — TOUJOURS côté serveur, à partir du tarif lu en base
    if (!$erreurs) {
        $nbNuits   = (int) $dArrivee->diff($dDepart)->days;
        $prixTotal = $nbNuits * (float) $villa["prix_nuit"];
        $recap = [
            "arrivee" => $dArrivee->format("d/m/Y"),
            "depart"  => $dDepart->format("d/m/Y"),
            "nuits"   => $nbNuits,
            "total"   => $prixTotal,
        ];
    }
}

include "includes/header.php";
?>

  <main>
    <div class="form-card">
      <h1>Réserver</h1>
      <p class="meta"><strong><?= htmlspecialchars($villa["nom"]) ?></strong></p>
      <p class="meta"><?= (int) $villa["capacite"] ?> voyageurs · <?= htmlspecialchars($villa["couchage"]) ?></p>

      <?php foreach ($erreurs as $e): ?>
        <p class="erreur"><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>

      <form method="post">
        <label>Date d'arrivée
          <input type="date" name="date_arrivee" value="<?= htmlspecialchars($_POST['date_arrivee'] ?? '') ?>" required>
        </label>
        <label>Date de départ
          <input type="date" name="date_depart" value="<?= htmlspecialchars($_POST['date_depart'] ?? '') ?>" required>
        </label>
        <button type="submit" class="btn">Voir le prix</button>
      </form>

      <?php if ($recap): ?>
        <div class="recap">
          <h2>Récapitulatif</h2>
          <p>Du <strong><?= $recap["arrivee"] ?></strong> au <strong><?= $recap["depart"] ?></strong></p>
          <p>
            <?= $recap["nuits"] ?> nuit<?= $recap["nuits"] > 1 ? "s" : "" ?>
            × <?= number_format($villa["prix_nuit"], 0, ',', ' ') ?> €
            = <strong class="prix"><?= number_format($recap["total"], 2, ',', ' ') ?> €</strong>
          </p>
          <p class="form-lien">La vérification de disponibilité et la confirmation arrivent à l'étape suivante.</p>
        </div>
      <?php endif; ?>

    </div>
  </main>

<?php include "includes/footer.php"; ?>
