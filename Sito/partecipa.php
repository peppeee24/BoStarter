<?php
session_start();
require 'session.php';

if (!isset($_GET['nome_progetto'])) {
    die("Errore: Nessun progetto specificato.");
}

$nome_progetto = $_GET['nome_progetto'];
$loggedIn = isset($_SESSION['email']);
$email_utente = $loggedIn ? $_SESSION['email'] : null;

$errore = '';

// Verifica se l'utente è loggato
if (!$loggedIn) {
    header("Location: login.html");
    exit();
}

try {
    // Recupera i dettagli del progetto e tipo
    $stmt = $pdo->prepare("
        SELECT P.*, 
            COALESCE(
                (SELECT 'hardware' FROM PROGETTO_HARDWARE H WHERE H.nome_progetto = P.nome),
                (SELECT 'software' FROM PROGETTO_SOFTWARE S WHERE S.nome_progetto = P.nome),
                'undefined'
            ) AS tipo_progetto
        FROM PROGETTO P
        WHERE P.nome = :nome
    ");
    $stmt->execute(['nome' => $nome_progetto]);
    $progetto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$progetto) {
        die("Errore: Il progetto non esiste.");
    }

    // Verifica che il progetto sia di tipo software
    if ($progetto['tipo_progetto'] !== 'software') {
        die("Errore: Il progetto non è un progetto software.");
    }

    // Recupera le skill necessarie per il progetto software
    $skills_richieste = [];
    $stmtSkills = $pdo->prepare("
        SELECT competenza, livello
        FROM COMPRENDE
        WHERE id_profilo IN (SELECT id FROM PROFILO WHERE nome_software = :nome)
    ");
    $stmtSkills->execute(['nome' => $nome_progetto]);
    $skills_richieste = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);

    // Recupera le skill dell'utente
    $stmtUserSkills = $pdo->prepare("
        SELECT competenza, livello
        FROM INDICA
        WHERE email_utente = :email
    ");
    $stmtUserSkills->execute(['email' => $email_utente]);
    $user_skills = $stmtUserSkills->fetchAll(PDO::FETCH_ASSOC);

    // Confronta le skill dell'utente con quelle richieste per partecipare
    $can_participate = true;
    foreach ($skills_richieste as $skill) {
        $found = false;
        foreach ($user_skills as $user_skill) {
            if ($user_skill['competenza'] === $skill['competenza'] && $user_skill['livello'] >= $skill['livello']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $can_participate = false;
            break;
        }
    }

    // Se l'utente ha tutte le skill necessarie, permetti di partecipare
    if ($can_participate) {
        // Inserisci la candidatura
        $stmtCandidatura = $pdo->prepare("
            INSERT INTO CANDIDATURA (email_utente, id_profilo, esito) 
            SELECT :email, id, FALSE FROM PROFILO WHERE nome_software = :nome_progetto
        ");
        $stmtCandidatura->execute([
            'email' => $email_utente,
            'nome_progetto' => $nome_progetto
        ]);
        $errore = "Candidatura inviata con successo!";
    } else {
        $errore = "Non hai le skill necessarie per partecipare a questo progetto.";
    }

} catch (PDOException $e) {
    $errore = "Errore database: " . $e->getMessage();
    error_log($errore);
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partecipa al Progetto - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success-message {
            background-color: #28a745;
            color: white;
            padding: 15px;
            border-radius: 5px;
        }
        .error-message {
            background-color: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BoStarter</a>
    </div>
</nav>

<main class="container mt-5 pt-4">
    <h1 class="mb-4">Partecipa al Progetto: <?php echo htmlspecialchars($progetto['nome']); ?></h1>

    <?php if ($errore): ?>
        <div class="alert <?php echo $can_participate ? 'success-message' : 'error-message'; ?>">
            <?php echo htmlspecialchars($errore); ?>
        </div>
    <?php endif; ?>

    <?php if ($can_participate): ?>
        <p>Hai le skill necessarie per partecipare a questo progetto. La tua candidatura è stata inviata!</p>
    <?php else: ?>
        <p>Non hai le skill richieste per partecipare a questo progetto.</p>
    <?php endif; ?>

    <a href="index.php" class="btn btn-primary">Torna alla Home</a>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>