<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Avvia la sessione solo se non è già attiva
} // Avvia la sessione per la gestione degli utenti

// Configurazione della connessione al database
$host = 'localhost'; // Cambia se necessario
$dbname = 'BOSTARTER';
$username = 'root'; // Cambia se necessario
$password = 'root'; // Inserisci la tua password MySQL

try {
    // Creazione della connessione PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}
?>