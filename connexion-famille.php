<?php
session_start();
require "includes/db.php";

$erreur = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $mdp   = $_POST["mot_de_passe"] ?? "";

    // On cherche le membre dans la table famille (pas clients !)
    $req = $pdo->prepare("SELECT * FROM famille WHERE email = ?");
    $req->execute([$email]);
    $membre = $req->fetch();

    if ($membre && password_verify($mdp, $membre["mot_de_passe"])) {
        // Clé de session DÉDIÉE au circuit famille
        $_SESSION["famille_id"]     = $membre["id"];
        $_SESSION["famille_prenom"] = $membre["prenom"];
        header("Location: intranet.php");
        exit;
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}

include "includes/header.php";
?>

  <main>
    <div class="form-card">
      <h1>Connexion famille</h1>
      <p class="meta">Accès réservé aux membres de la famille.</p>

      <?php if ($erreur): ?>
        <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
      <?php endif; ?>

      <form method="post">
        <label>Email
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </label>
        <label>Mot de passe
          <input type="password" name="mot_de_passe" required>
        </label>
        <button type="submit" class="btn">Se connecter</button>
      </form>

      <p class="form-lien">Vous avez un code d'invitation ? <a href="inscription-famille.php">Créer un accès</a></p>
    </div>
  </main>

<?php include "includes/footer.php"; ?>
