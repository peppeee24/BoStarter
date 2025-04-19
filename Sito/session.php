<?php

// Avvia la sessione per la gestione degli utenti solo se non è già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurazione della connessione al database
$host = 'localhost';
$dbname = 'BOSTARTER';
$username = 'root';
$password = 'root';



/*
    PDO (PHP Data Objects) estensione di PHP che fornisce un metodo uniforme e sicuro per accedere a diversi database:
    (MySQL, PostgreSQL, SQLite, Oracle, SQL Server, ecc.).
*/

try {
    // Creazione della connessione PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Controllo gli eventuali errori con ATTR_ERRMODE e lancio l'eccezione con ERRMODE_EXCEPTION
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}
?>