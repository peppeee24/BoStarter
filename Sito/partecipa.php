<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session.php'; // Connessione al database

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

// Variabili iniziali
$errore = '';
$successo = '';

// Recupera il nome del progetto
if (!isset($_GET['nome_progetto'])) {
    die("Errore: Nessun progetto specificato.");
}

$nome_progetto = $_GET['nome_progetto'];
$email_utente = $_SESSION['email'];

// Recupera i dettagli del progetto
try {
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

    // Verifica se il progetto è di tipo software
    if ($progetto['tipo_progetto'] !== 'software') {
        die("Errore: Il progetto non è un progetto software.");
    }

    // Recupera i profili disponibili per il progetto software
    $stmtProfili = $pdo->prepare("
        SELECT p.id, p.nome as nome_profilo
        FROM PROFILO p
        WHERE p.nome_software = :nome
    ");
    $stmtProfili->execute(['nome' => $nome_progetto]);
    $profili = $stmtProfili->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errore = "Errore nel recupero dei dati del progetto: " . $e->getMessage();
}

// Gestione della selezione del profilo e della candidatura
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['profilo'])) {
    $id_profilo = $_POST['profilo'];

    // Recupera le skill richieste per il profilo selezionato
    $stmtSkills = $pdo->prepare("
        SELECT c.competenza, c.livello
        FROM COMPRENDE c
        WHERE c.id_profilo = :id_profilo
    ");
    $stmtSkills->execute(['id_profilo' => $id_profilo]);
    $skills_richieste = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);

    // Recupera le skill dell'utente
    $stmtUserSkills = $pdo->prepare("
        SELECT competenza, livello
        FROM INDICA
        WHERE email_utente = :email
    ");
    $stmtUserSkills->execute(['email' => $email_utente]);
    $user_skills = $stmtUserSkills->fetchAll(PDO::FETCH_ASSOC);

    // Confronta le skill dell'utente con quelle richieste per il profilo
    $missing_skills = array();
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
            $missing_skills[] = $skill;
        }
    }

    // Se l'utente ha tutte le skill necessarie, invia la candidatura
    if ($can_participate) {
        try {
            $stmtCandidatura = $pdo->prepare("
                INSERT INTO CANDIDATURA (email_utente, id_profilo, esito) 
                VALUES (:email_utente, :id_profilo, FALSE)
            ");
            $stmtCandidatura->execute([
                'email_utente' => $email_utente,
                'id_profilo' => $id_profilo
            ]);
            $successo = "Candidatura inviata con successo!";
        } catch (PDOException $e) {
            $errore = "Errore nell'invio della candidatura: " . $e->getMessage();
        }
    } else {
        // Elenco delle skill mancanti
        $missing_list = [];
        foreach ($missing_skills as $ms) {
            $missing_list[] = $ms['competenza'] . " (livello " . $ms['livello'] . ")";
        }
        $errore = "Non hai le seguenti skill necessarie per partecipare a questo progetto: " . implode(", ", $missing_list) . ".";
    }
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

    <?php if ($successo): ?>
        <div class="alert success-message">
            <?php echo htmlspecialchars($successo); ?>
        </div>
    <?php endif; ?>

    <!-- Se il progetto ha più profili -->
    <?php if (!empty($profili)): ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label" for="profilo">Seleziona un Profilo</label>
                <select name="profilo" id="profilo" class="form-select" required>
                    <option value="">Seleziona un profilo</option>
                    <?php foreach ($profili as $profilo): ?>
                        <option value="<?= htmlspecialchars($profilo['id']); ?>"><?= htmlspecialchars($profilo['nome_profilo']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Invia la candidatura</button>
        </form>
    <?php else: ?>
        <p>Non ci sono profili disponibili per la partecipazione a questo progetto.</p>
    <?php endif; ?>

    <a href="index.php" class="btn btn-primary mt-4">Torna alla Home</a>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>