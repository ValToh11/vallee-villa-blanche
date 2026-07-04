<?php
session_start();
unset($_SESSION["client_id"], $_SESSION["client_prenom"]);
header("Location: index.php");
exit;
