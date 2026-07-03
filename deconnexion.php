<?php
session_start();
session_unset();      // vide les variables de session
session_destroy();    // détruit la session
header("Location: index.php");
exit;
