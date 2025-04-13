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
        // Aggiungi nuova skill senza livelli (solo competenza)
        $competenza = trim($_POST['competenza']);

        try {
            $pdo->beginTransaction();
            // Inserisci la competenza (senza livello, come unica voce)
            $stmt = $pdo->prepare("INSERT INTO SKILL (competenza, email_utente_amm) 
                                  VALUES (?, ?)");
            $stmt->execute([$competenza, $_SESSION['email']]);
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

        if ($result && $result['email_utente_amm'] === $_SESSION['email']) {
            try {
                $pdo->beginTransaction();

                // Elimina la competenza dalla tabella SKILL
                $stmtDelete = $pdo->prepare("DELETE FROM SKILL WHERE competenza = ?");
                $stmtDelete->execute([$competenza]);

                // Se la competenza è associata a uno o più profili, elimina anche le associazioni nella tabella COMPRENDE
                $stmtDeleteFromComprende = $pdo->prepare("DELETE FROM COMPRENDE WHERE competenza = ?");
                $stmtDeleteFromComprende->execute([$competenza]);

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
    <style>
        .level-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            text-align: center;
            margin: 2px;
            background-color: #28a745;
            color: white;
        }
        .missing-level {
            background-color: #6c757d;
            opacity: 0.5;
        }
    </style>
</head>
<body class="container mt-5">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BoStarter</a>
    </div>
</nav>
<br><br><br>
<div class="row">
    <div class="col-md-8">
        <h2>Gestione Competenze</h2>

        <!-- Form aggiunta skill -->
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

        <!-- Lista skills -->
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
</body>
</html>