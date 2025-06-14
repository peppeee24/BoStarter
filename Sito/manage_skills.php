<?php
session_start();
require_once 'session.php';

// Controllo accesso
if (!isset($_SESSION['admin_logged']) || !$_SESSION['admin_logged']) {
    header("Location: admin_login.php");
    exit();
}

// Gestione operazioni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_skill'])) {
        // Aggiungo nuova skill (competneza) leggendo dal form
        // Non aggiungo il livello visto che è sempre da 0 a 5
        $competenza = trim($_POST['competenza']);

        try {
            $pdo->beginTransaction();
            // Faccio l'insert della competenza nel db
            $stmt = $pdo->prepare("CALL sp_aggiungi_skill(?, ?)");
            $stmt->execute([$competenza, $_SESSION['email']]);
            /*$stmt = $pdo->prepare("INSERT INTO SKILL (competenza, email_utente_amm) 
                                  VALUES (?, ?)");
            $stmt->execute([$competenza, $_SESSION['email']]);*/
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Errore nell'inserimento: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_skill'])) {
        $competenza = $_POST['competenza'];

        // Recupera chi ha creato la competenza
        $stmtCheck = $pdo->prepare("SELECT email_utente_amm FROM SKILL WHERE competenza = ? LIMIT 1");
        $stmtCheck->execute([$competenza]);
        $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // Ottengo l'utent loggato per fare si che possa elimianre le competenze create
        if ($result && $result['email_utente_amm'] === $_SESSION['email']) {
            try {
                // Inizia transazione, quindi queste operazione o vengono fatte tutte oppure nessuna
                $pdo->beginTransaction();

                // Elimino la competenza dalla tabella SKILL
                $stmtDelete = $pdo->prepare("DELETE FROM SKILL WHERE competenza = ?");
                $stmtDelete->execute([$competenza]);

                // Se la competenza è associata a uno o più profili, elimino anche le associazioni nella tabella COMPRENDE
                $stmtDeleteFromComprende = $pdo->prepare("DELETE FROM COMPRENDE WHERE competenza = ?");
                $stmtDeleteFromComprende->execute([$competenza]);

                // Fine transazione
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Errore nell'eliminazione: " . $e->getMessage();
            }
        } else {
            $error = "Non puoi eliminare una competenza creata da un altro amministratore.";
        }
    }
}

// Recupera tutte le skills raggruppate
$stmt = $pdo->prepare("
    SELECT competenza, email_utente_amm
    FROM SKILL
    GROUP BY competenza, email_utente_amm
");
$stmt->execute();
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Competenze</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style2.css">
</head>
<body class="container mt-5">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="BoStarter Logo" class="d-inline-block align-text-top">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#progetti">Progetti</a></li>
                    <li class="nav-item"><a class="nav-link" href="statistiche.php">Statistiche</a></li>
                </ul>
            </div>
        </div>
    </nav>

<main class="container mt-5">
<div class="row">
    <div class="col-md-8">
        <h2>Gestione Competenze</h2>

        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <input type="text" class="form-control" name="competenza"
                           placeholder="Nuova competenza" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="add_skill" class="btn btn-success">
                        Crea Competenza (Livelli 0-5)
                    </button>
                </div>
            </div>
            <small class="text-muted">Verranno creati automaticamente tutti i livelli da 0 a 5</small>
        </form>

        <h4>Competenze Esistenti</h4>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Competenza</th>
                <th>Livelli Disponibili</th>
                <th>Azioni</th>
                <th>Creato da</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($skills as $skill): ?>
                <tr>
                    <td><?= htmlspecialchars($skill['competenza']) ?></td>
                    <td>
                        <?php for ($i = 0; $i <= 5; $i++): ?>
                            <span class="level-badge">
                                <?= $i ?>
                            </span>
                        <?php endfor; ?>
                    </td>
                    <td>
                        <?php if ($skill['email_utente_amm'] === $_SESSION['email']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="competenza" value="<?= $skill['competenza'] ?>">
                                <button type="submit" name="delete_skill" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Eliminare tutti i livelli per questa competenza?')">
                                    Elimina
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">Non autorizzato</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($skill['email_utente_amm']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-6 mb-3 mb-md-0">
                <h5 class="mb-1 fw-bold">BoStarter</h5>
                <p class="mb-0 small">&copy; <?php echo date('Y'); ?> Tutti i diritti riservati</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="autore.html" class="text-white me-3">
                    <i class="bi bi-people-fill me-1"></i> Autori
                </a>
                <a href="mailto:info@bostarter.it" class="text-white text-decoration-none">
                    <i class="bi bi-envelope-fill me-1"></i> Contattaci
                </a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>