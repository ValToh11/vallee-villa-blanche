<?php
session_start();
require "includes/db.php";

$erreur = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $mdp   = $_POST["mot_de_passe"] ?? "";

    // On cherche le client par son email
    $req = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
    $req->execute([$email]);
    $client = $req->fetch();

    // On vérifie le mot de passe contre l'empreinte stockée
    if ($client && password_verify($mdp, $client["mot_de_passe"])) {
        $_SESSION["client_id"]     = $client["id"];
        $_SESSION["client_prenom"] = $client["prenom"];
        header("Location: index.php");   // redirection après connexion
        exit;
    } else {
        // Message volontairement vague (on ne dit pas ce qui est faux)
        $erreur = "Email ou mot de passe incorrect.";
    }
}

include "includes/header.php";
?>

  <main>
    <div class="form-card">
      <h1>Connexion</h1>

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

      <p class="form-lien">Pas encore de compte ? <a href="inscription.php">Créer un compte</a></p>
    </div>
  </main>

<?php include "includes/footer.php"; ?>
