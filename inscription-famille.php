<?php
require "includes/db.php";

$erreurs = [];
$prenom = $nom = $email = "";
$succes = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code   = trim($_POST["code"] ?? "");
    $prenom = trim($_POST["prenom"] ?? "");
    $nom    = trim($_POST["nom"] ?? "");
    $email  = trim($_POST["email"] ?? "");
    $mdp    = $_POST["mot_de_passe"] ?? "";
    $mdp2   = $_POST["confirmation"] ?? "";

    // Le code d'invitation est vérifié en premier
    if ($code !== CODE_FAMILLE) {
        $erreurs[] = "Le code d'invitation est incorrect.";
    }
    if ($prenom === "" || $nom === "") {
        $erreurs[] = "Le prénom et le nom sont obligatoires.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "L'adresse email n'est pas valide.";
    }
    if (strlen($mdp) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    if ($mdp !== $mdp2) {
        $erreurs[] = "Les deux mots de passe ne correspondent pas.";
    }

    // Email déjà utilisé dans le circuit famille ?
    if (!$erreurs) {
        $req = $pdo->prepare("SELECT id FROM famille WHERE email = ?");
        $req->execute([$email]);
        if ($req->fetch()) {
            $erreurs[] = "Un accès famille existe déjà avec cette adresse email.";
        }
    }

    // Création de l'accès famille
    if (!$erreurs) {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $req = $pdo->prepare(
            "INSERT INTO famille (prenom, nom, email, mot_de_passe) VALUES (?, ?, ?, ?)"
        );
        $req->execute([$prenom, $nom, $email, $hash]);
        $succes = true;
    }
}

include "includes/header.php";
?>

  <main>
    <div class="form-card">
      <h1>Accès famille</h1>
      <p class="meta">Réservé aux membres de la famille, sur code d'invitation.</p>

      <?php if ($succes): ?>

        <p class="succes">Votre accès famille a bien été créé. Vous pourrez bientôt vous connecter.</p>

      <?php else: ?>

        <?php foreach ($erreurs as $e): ?>
          <p class="erreur"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>

        <form method="post">
          <label>Code d'invitation
            <input type="text" name="code" required>
          </label>
          <label>Prénom
            <input type="text" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required>
          </label>
          <label>Nom
            <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required>
          </label>
          <label>Email
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
          </label>
          <label>Mot de passe
            <input type="password" name="mot_de_passe" required minlength="8">
          </label>
          <label>Confirmer le mot de passe
            <input type="password" name="confirmation" required>
          </label>
          <button type="submit" class="btn">Créer mon accès</button>
        </form>

      <?php endif; ?>
    </div>
  </main>

<?php include "includes/footer.php"; ?>
