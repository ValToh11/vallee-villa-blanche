<?php
session_start();

// ---- LE CADENAS : page réservée aux clients connectés ----
if (!isset($_SESSION["client_id"])) {
    header("Location: connexion.php");
    exit;
}

require "includes/db.php";

$clientId = $_SESSION["client_id"];
$erreurs  = [];
$succes   = false;

// ---- Traitement du formulaire de modification ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $prenom    = trim($_POST["prenom"] ?? "");
    $nom       = trim($_POST["nom"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");

    // Validation côté serveur
    if ($prenom === "" || $nom === "") {
        $erreurs[] = "Le prénom et le nom sont obligatoires.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "L'adresse email n'est pas valide.";
    }
    if ($telephone === "") {
        $erreurs[] = "Le numéro de téléphone est obligatoire.";
    }

    // L'email doit être unique... sauf s'il appartient déjà à CE compte
    if (!$erreurs) {
        $req = $pdo->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
        $req->execute([$email, $clientId]);
        if ($req->fetch()) {
            $erreurs[] = "Cette adresse email est déjà utilisée par un autre compte.";
        }
    }

    // Tout est bon : on met à jour
    if (!$erreurs) {
        $req = $pdo->prepare(
            "UPDATE clients SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?"
        );
        $req->execute([$prenom, $nom, $email, $telephone, $clientId]);
        $_SESSION["client_prenom"] = $prenom;   // le menu se met à jour aussitôt
        $succes = true;
    }
}

// ---- On charge les infos à jour pour pré-remplir le formulaire ----
$req = $pdo->prepare(
    "SELECT prenom, nom, email, telephone, date_creation FROM clients WHERE id = ?"
);
$req->execute([$clientId]);
$client = $req->fetch();

// En cas d'erreur, on réaffiche ce que l'utilisateur venait de taper
if ($erreurs) {
    $client["prenom"]    = $prenom;
    $client["nom"]       = $nom;
    $client["email"]     = $email;
    $client["telephone"] = $telephone;
}

include "includes/header.php";
?>

  <main>
    <div class="form-card">
      <h1>Mon compte</h1>

      <?php if ($succes): ?>
        <p class="succes">Vos informations ont bien été mises à jour.</p>
      <?php endif; ?>

      <?php foreach ($erreurs as $e): ?>
        <p class="erreur"><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>

      <form method="post">
        <label>Prénom
          <input type="text" name="prenom" value="<?= htmlspecialchars($client["prenom"]) ?>" required>
        </label>
        <label>Nom
          <input type="text" name="nom" value="<?= htmlspecialchars($client["nom"]) ?>" required>
        </label>
        <label>Email
          <input type="email" name="email" value="<?= htmlspecialchars($client["email"]) ?>" required>
        </label>
        <label>Téléphone
          <input type="tel" name="telephone" value="<?= htmlspecialchars($client["telephone"] ?? "") ?>" required>
        </label>
        <button type="submit" class="btn">Enregistrer les modifications</button>
      </form>

      <p class="form-lien">Membre depuis le <?= htmlspecialchars(date("d/m/Y", strtotime($client["date_creation"]))) ?></p>
    </div>
  </main>

<?php include "includes/footer.php"; ?>
