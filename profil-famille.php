<?php
session_start();
if (!isset($_SESSION["famille_id"])) {
    header("Location: connexion-famille.php");
    exit;
}
require "includes/db.php";

$membreId = $_SESSION["famille_id"];
$erreurs = [];      $succes = false;
$erreursMdp = [];   $succesMdp = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["form"] ?? "";

    // ---- Informations ----
    if ($action === "infos") {
        $prenom = trim($_POST["prenom"] ?? "");
        $nom    = trim($_POST["nom"] ?? "");
        $email  = trim($_POST["email"] ?? "");

        if ($prenom === "" || $nom === "") {
            $erreurs[] = "Le prénom et le nom sont obligatoires.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "L'adresse email n'est pas valide.";
        }
        if (!$erreurs) {
            $req = $pdo->prepare("SELECT id FROM famille WHERE email = ? AND id != ?");
            $req->execute([$email, $membreId]);
            if ($req->fetch()) {
                $erreurs[] = "Cette adresse email est déjà utilisée par un autre accès famille.";
            }
        }
        if (!$erreurs) {
            $req = $pdo->prepare("UPDATE famille SET prenom = ?, nom = ?, email = ? WHERE id = ?");
            $req->execute([$prenom, $nom, $email, $membreId]);
            $_SESSION["famille_prenom"] = $prenom;
            $succes = true;
        }
    }

    // ---- Mot de passe ----
    if ($action === "motdepasse") {
        $actuel  = $_POST["mdp_actuel"] ?? "";
        $nouveau = $_POST["mdp_nouveau"] ?? "";
        $confirm = $_POST["mdp_confirm"] ?? "";

        $req = $pdo->prepare("SELECT mot_de_passe FROM famille WHERE id = ?");
        $req->execute([$membreId]);
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
            $req = $pdo->prepare("UPDATE famille SET mot_de_passe = ? WHERE id = ?");
            $req->execute([$hash, $membreId]);
            $succesMdp = true;
        }
    }
}

$req = $pdo->prepare("SELECT prenom, nom, email, date_creation FROM famille WHERE id = ?");
$req->execute([$membreId]);
$membre = $req->fetch();

if ($erreurs) {
    $membre["prenom"] = $prenom;
    $membre["nom"]    = $nom;
    $membre["email"]  = $email;
}

include "includes/header.php";
?>

  <main>

    <div class="form-card">
      <h1>Mon profil famille</h1>
      <?php if ($succes): ?>
        <p class="succes">Vos informations ont bien été mises à jour.</p>
      <?php endif; ?>
      <?php foreach ($erreurs as $e): ?>
        <p class="erreur"><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
      <form method="post">
        <input type="hidden" name="form" value="infos">
        <label>Prénom
          <input type="text" name="prenom" value="<?= htmlspecialchars($membre["prenom"]) ?>" required>
        </label>
        <label>Nom
          <input type="text" name="nom" value="<?= htmlspecialchars($membre["nom"]) ?>" required>
        </label>
        <label>Email
          <input type="email" name="email" value="<?= htmlspecialchars($membre["email"]) ?>" required>
        </label>
        <button type="submit" class="btn">Enregistrer les modifications</button>
      </form>
      <p class="form-lien">Accès créé le <?= htmlspecialchars(date("d/m/Y", strtotime($membre["date_creation"]))) ?></p>
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
