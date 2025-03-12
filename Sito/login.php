<?php
require_once 'session.php'; // Connessione al database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Controlla se email e password sono settati prima di usarli
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Se uno dei campi è vuoto, mostra errore
    if (empty($email) || empty($password)) {
        echo "Errore: Compila tutti i campi.";
        exit();
    }


    // Verifica se l'utente esiste nel database
    $stmt = $pdo->prepare("SELECT email, password FROM UTENTE WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Controllo password
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['email'] = $user['email']; // Salva l'utente nella sessione
        header("Location: dashboard.html"); // Reindirizza alla dashboard
        echo "Login completata con successo!";
        header("Location: index.php");
        exit();
    } else {
        echo "Credenziali non valide.";
        echo $email;
        echo $password;



    }
}
?>