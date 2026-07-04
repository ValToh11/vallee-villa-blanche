<?php
session_start();
unset($_SESSION["famille_id"], $_SESSION["famille_prenom"]);
header("Location: index.php");
exit;
