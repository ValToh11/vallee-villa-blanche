<?php
require "includes/db.php";

$erreurs = [];
$prenom = $nom = $email = $telephone = "";
$succes = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Récupération et nettoyage des champs
    $prenom    = trim($_POST["prenom"] ?? "");
    $nom       = trim($_POST["nom"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $mdp       = $_POST["mot_de_passe"] ?? "";
    $mdp2      = $_POST["confirmation"] ?? "";

    // 2. Validation côté serveur
    if ($prenom === "" || $nom === "") {
        $erreurs[] = "Le prénom et le nom sont obligatoires.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "L'adresse email n'est pas valide.";
    }
    if ($telephone === "") {
        $erreurs[] = "Le numéro de téléphone est obligatoire.";
    }
    if (strlen($mdp) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    if ($mdp !== $mdp2) {
        $erreurs[] = "Les deux mots de passe ne correspondent pas.";
    }

    // 3. Email déjà utilisé ?
    if (!$erreurs) {
        $req = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
        $req->execute([$email]);
        if ($req->fetch()) {
            $erreurs[] = "Un compte existe déjà avec cette adresse email.";
        }
    }

    // 4. Tout est bon : on hache le mot de passe et on enregistre
    if (!$erreurs) {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $req = $pdo->prepare(
            "INSERT INTO clients (prenom, nom, email, telephone, mot_de_passe)
             VALUES (?, ?, ?, ?, ?)"
        );
        $req->execute([$prenom, $nom, $email, $telephone, $hash]);
        $succes = true;
    }
}

include "includes/header.php";
?>

  <main>
    <div class="form-card">
      <h1>Créer un compte</h1>

      <?php if ($succes): ?>

        <p class="succes">Votre compte a bien été créé. Vous pourrez bientôt vous connecter.</p>

      <?php else: ?>

        <?php foreach ($erreurs as $e): ?>
          <p class="erreur"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>

        <form method="post">
          <label>Prénom
            <input type="text" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required>
          </label>
          <label>Nom
            <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required>
          </label>
          <label>Email
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
          </label>
          <label>Téléphone
            <input type="tel" name="telephone" value="<?= htmlspecialchars($telephone) ?>" required>
          </label>
          <label>Mot de passe
            <input type="password" name="mot_de_passe" required minlength="8">
          </label>
          <label>Confirmer le mot de passe
            <input type="password" name="confirmation" required>
          </label>
          <button type="submit" class="btn">Créer mon compte</button>
        </form>

      <?php endif; ?>
    </div>
  </main>

<?php include "includes/footer.php"; ?>
