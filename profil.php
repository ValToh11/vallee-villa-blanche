<?php
session_start();
if (!isset($_SESSION["client_id"])) {
    header("Location: connexion.php");
    exit;
}
require "includes/db.php";

$clientId = $_SESSION["client_id"];
$erreurs = [];      $succes = false;
$erreursMdp = [];   $succesMdp = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["form"] ?? "";

    if ($action === "infos") {
        $prenom    = trim($_POST["prenom"] ?? "");
        $nom       = trim($_POST["nom"] ?? "");
        $email     = trim($_POST["email"] ?? "");
        $telephone = trim($_POST["telephone"] ?? "");

        if ($prenom === "" || $nom === "") {
            $erreurs[] = "Le prénom et le nom sont obligatoires.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "L'adresse email n'est pas valide.";
        }
        if ($telephone === "") {
            $erreurs[] = "Le numéro de téléphone est obligatoire.";
        }
        if (!$erreurs) {
            $req = $pdo->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
            $req->execute([$email, $clientId]);
            if ($req->fetch()) {
                $erreurs[] = "Cette adresse email est déjà utilisée par un autre compte.";
            }
        }
        if (!$erreurs) {
            $req = $pdo->prepare(
                "UPDATE clients SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?"
            );
            $req->execute([$prenom, $nom, $email, $telephone, $clientId]);
            $_SESSION["client_prenom"] = $prenom;
            $succes = true;
        }
    }

    if ($action === "motdepasse") {
        $actuel  = $_POST["mdp_actuel"] ?? "";
        $nouveau = $_POST["mdp_nouveau"] ?? "";
        $confirm = $_POST["mdp_confirm"] ?? "";

        $req = $pdo->prepare("SELECT mot_de_passe FROM clients WHERE id = ?");
        $req->execute([$clientId]);
        $ligne = $req->fetch();

        if (!password_verify($actuel, $ligne["mot_de_passe"])) {
            $erreursMdp[] = "Le mot de passe actuel est incorrect.";
        }
        if (strlen($nouveau) < 8) {
            $erreursMdp[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        }
        if ($nouveau !== $confirm) {
            $erreursMdp[] = "Les deux nouveaux mots de passe ne correspondent pas.";
        }
        if (!$erreursMdp) {
            $hash = password_hash($nouveau, PASSWORD_DEFAULT);
            $req = $pdo->prepare("UPDATE clients SET mot_de_passe = ? WHERE id = ?");
            $req->execute([$hash, $clientId]);
            $succesMdp = true;
        }
    }
}

$req = $pdo->prepare("SELECT prenom, nom, email, telephone, date_creation FROM clients WHERE id = ?");
$req->execute([$clientId]);
$client = $req->fetch();

if ($erreurs) {
    $client["prenom"] = $prenom; $client["nom"] = $nom;
    $client["email"] = $email;   $client["telephone"] = $telephone;
}

$pageActive = "profil";
include "includes/header.php";
?>

  <main>
    <?php include "includes/menu-compte.php"; ?>

    <div class="form-card">
      <h1>Mes informations</h1>
      <?php if ($succes): ?>
        <p class="succes">Vos informations ont bien été mises à jour.</p>
      <?php endif; ?>
      <?php foreach ($erreurs as $e): ?>
        <p class="erreur"><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
      <form method="post">
        <input type="hidden" name="form" value="infos">
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

    <div class="form-card" style="margin-top:28px">
      <h1>Changer mon mot de passe</h1>
      <?php if ($succesMdp): ?>
        <p class="succes">Votre mot de passe a bien été modifié.</p>
      <?php endif; ?>
      <?php foreach ($erreursMdp as $e): ?>
        <p class="erreur"><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
      <form method="post">
        <input type="hidden" name="form" value="motdepasse">
        <label>Mot de passe actuel
          <input type="password" name="mdp_actuel" required>
        </label>
        <label>Nouveau mot de passe
          <input type="password" name="mdp_nouveau" required minlength="8">
        </label>
        <label>Confirmer le nouveau mot de passe
          <input type="password" name="mdp_confirm" required>
        </label>
        <button type="submit" class="btn">Changer mon mot de passe</button>
      </form>
    </div>

  </main>

<?php include "includes/footer.php"; ?>
