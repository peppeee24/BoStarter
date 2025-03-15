<?php
session_start();
require_once 'session.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

// Recupera dati utente
$sql = "SELECT u.*, 
        CASE 
            WHEN a.email_utente_amm IS NOT NULL THEN 'Amministratore'
            WHEN c.email_utente_creat IS NOT NULL THEN 'Creatore'
            ELSE 'Normale'
        END AS tipo_utente
        FROM UTENTE u
        LEFT JOIN UTENTE_AMMINISTRATORE a ON u.email = a.email_utente_amm
        LEFT JOIN UTENTE_CREATORE c ON u.email = c.email_utente_creat
        WHERE u.email = :email";

$stmt = $pdo->prepare($sql);
$stmt->execute(['email' => $_SESSION['email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Errore: Utente non trovato.";
    exit();
}

// Gestione salvataggio competenze
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['competenze'])) {
    $pdo->beginTransaction();
    try {
        // Rimuovi tutte le competenze esistenti
        $deleteStmt = $pdo->prepare("DELETE FROM INDICA WHERE email_utente = ?");
        $deleteStmt->execute([$_SESSION['email']]);

        // Inserisci le nuove competenze
        foreach ($_POST['competenze'] as $competenza => $livello) {
            if ($livello > 0) {
                $insertStmt = $pdo->prepare("INSERT INTO INDICA (competenza, livello, email_utente) 
                                           VALUES (?, ?, ?)");
                $insertStmt->execute([$competenza, $livello, $_SESSION['email']]);
            }
        }
        $pdo->commit();
        header("Location: profilo.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Errore nel salvataggio: " . $e->getMessage();
    }
}

// Recupera tutte le competenze disponibili
$skillsQuery = $pdo->query("SELECT s.competenza, s.livello, i.livello AS selected_level
                           FROM SKILL s
                           LEFT JOIN INDICA i ON s.competenza = i.competenza 
                               AND s.livello = i.livello 
                               AND i.email_utente = '" . $_SESSION['email'] . "'
                           ORDER BY s.competenza, s.livello");

$competenze = [];
foreach ($skillsQuery as $row) {
    $competenze[$row['competenza']]['livelli'][] = $row['livello'];
    if ($row['selected_level'] !== null) {
        $competenze[$row['competenza']]['selected'] = $row['selected_level'];
    }
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
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <p><strong>Nome:</strong> <?= htmlspecialchars($user['nome'] . ' ' . $user['cognome']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Nickname:</strong> <?= htmlspecialchars($user['nickname']) ?></p>
                <p><strong>Tipo Utente:</strong> <?= htmlspecialchars($user['tipo_utente']) ?></p>
                <p><strong>Anno di Nascita:</strong> <?= htmlspecialchars($user['anno_nascita']) ?></p>
                <p><strong>Luogo di Nascita:</strong> <?= htmlspecialchars($user['luogo_nascita']) ?></p>
                <a href="logout.php" class="btn btn-danger">Logout</a>

                <?php if ($user['tipo_utente'] === 'Amministratore'): ?>
                    <a href="admin_login.php" class="btn btn-warning mt-3">Gestione Competenze</a>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <form method="POST">
                    <h4>Le tue Competenze</h4>
                    <?php foreach ($competenze as $competenza => $dati): ?>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars($competenza) ?></label>
                            <select name="competenze[<?= htmlspecialchars($competenza) ?>]" class="form-select">
                                <option value="0">Nessun livello</option>
                                <?php foreach ($dati['livelli'] as $livello): ?>
                                    <option value="<?= $livello ?>" <?= ($dati['selected'] ?? 0) == $livello ? 'selected' : '' ?>>
                                        Livello <?= $livello ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Salva Competenze</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>