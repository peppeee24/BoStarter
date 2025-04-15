<?php
// Avvio la sessione
session_start();
// Rimuovo tutte le variabili di sessione
session_unset();
// Distruggo la sessione
session_destroy();

// Reindirizzo alla homepage
header("Location: index.php");
exit();
?>