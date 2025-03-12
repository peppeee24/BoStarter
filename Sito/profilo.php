<?php
session_start();
require_once 'session.php'; // Connessione al database

if (!isset($_SESSION['email'])) {
    header("Location: login.html"); // Reindirizza se non loggato
    exit();
}

// Recupera le informazioni dell'utente
$stmt = $pdo->prepare("SELECT email, nickname, nome, cognome, anno_nascita, luogo_nascita FROM UTENTE WHERE email = :email");
$stmt->execute(['email' => $_SESSION['email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Errore: Utente non trovato.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Mio Profilo - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BoStarter</a>
    </div>
</nav>

<section class="container mt-5">
    <div class="card p-4 shadow-sm">
        <h2 class="mb-4">Il Mio Profilo</h2>
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Nickname:</strong> <?php echo htmlspecialchars($user['nickname']); ?></p>
        <p><strong>Anno di Nascita:</strong> <?php echo htmlspecialchars($user['anno_nascita']); ?></p>
        <p><strong>Luogo di Nascita:</strong> <?php echo htmlspecialchars($user['luogo_nascita']); ?></p>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>