<?php
require 'session.php';

// Verifico l'account con cui solo loggato, se sono loggato (come sempre)
if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

// Verifico il progetto che voglio finanziare se selezionato
if (!isset($_GET['nome_progetto'])) {
    die("Errore: Nessun progetto specificato.");
}

$nome_progetto = $_GET['nome_progetto'];
$email_utente = $_SESSION['email'];

try {
    // Recupera i dettagli del progetto dal DB
    $stmt = $pdo->prepare("SELECT * FROM PROGETTO WHERE nome = :nome");
    $stmt->execute(['nome' => $nome_progetto]);
    $progetto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$progetto) {
        die("Errore: Il progetto non esiste.");
    }

    // Recupera le reward disponibili per il progetto
    $stmtReward = $pdo->prepare("SELECT * FROM REWARD WHERE nome_progetto = :nome");
    $stmtReward->execute(['nome' => $nome_progetto]);
    $rewards = $stmtReward->fetchAll(PDO::FETCH_ASSOC);

    // Recupera la somma totale dei finanziamenti attuali
    $stmtFinanziamenti = $pdo->prepare("SELECT SUM(importo) AS totale_finanziato FROM FINANZIAMENTO WHERE nome_progetto = :nome");
    $stmtFinanziamenti->execute(['nome' => $nome_progetto]);
    $totale_finanziato = $stmtFinanziamenti->fetch(PDO::FETCH_ASSOC)['totale_finanziato'] ?? 0;

    // Controllo se il progetto è ancora finanziabile
    $budget_raggiunto = $totale_finanziato >= $progetto['budget'] || strtotime($progetto['data_limite']) < time();
    if ($budget_raggiunto) {
        die("<p style='color: red; text-align: center;'>Il progetto ha già raggiunto il budget o è scaduto.</p>");
    }

    // Se il form è stato inviato
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $importo = floatval($_POST['importo']);
        $codice_reward = intval($_POST['codice_reward']);
        $data_finanziamento = date("Y-m-d");

        // Controllo importo valido
        if ($importo <= 0) {
            throw new Exception("Errore: L'importo deve essere maggiore di 0.");
        }

        // Controllo che la reward esista e appartenga al progetto
        $stmtCheckReward = $pdo->prepare("SELECT COUNT(*) FROM REWARD WHERE codice = :codice AND nome_progetto = :nome");
        $stmtCheckReward->execute(['codice' => $codice_reward, 'nome' => $nome_progetto]);
        if ($stmtCheckReward->fetchColumn() == 0) {
            throw new Exception("Errore: Reward non valida.");
        }

        // Inserimento finanziamento nel database
        $stmtInsert = $pdo->prepare("INSERT INTO FINANZIAMENTO (data_finanziamento, importo, email_utente, nome_progetto, codice_reward) 
                                     VALUES (:data_finanziamento, :importo, :email_utente, :nome_progetto, :codice_reward)");
        $stmtInsert->execute([
            'data_finanziamento' => $data_finanziamento,
            'importo' => $importo,
            'email_utente' => $email_utente,
            'nome_progetto' => $nome_progetto,
            'codice_reward' => $codice_reward
        ]);

        // Aggiorna il totale finanziato
        $totale_finanziato += $importo;

        // Se il budget è stato raggiunto, chiudi il progetto
        if ($totale_finanziato >= $progetto['budget']) {
            $stmtCloseProject = $pdo->prepare("UPDATE PROGETTO SET stato = 'chiuso' WHERE nome = :nome");
            $stmtCloseProject->execute(['nome' => $nome_progetto]);
        }

        // Redirect alla pagina del progetto
        header("Location: progetto.php?nome_progetto=" . urlencode($nome_progetto));
        exit();
    }

} catch (Exception $e) {
    echo "<p style='color: red; text-align: center;'>Errore: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzia <?php echo htmlspecialchars($progetto['nome']); ?> - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BoStarter</a>
    </div>
</nav>

<!-- Form per il finanziamento -->
<div class="container mt-5">
    <div class="card p-4 shadow-sm">
        <h2 class="mb-4">Finanzia il Progetto: <?php echo htmlspecialchars($progetto['nome']); ?></h2>
        <form action="" method="POST">
            <div class="mb-3">
                <label for="importo" class="form-label">Importo (€):</label>
                <input type="number" class="form-control" id="importo" name="importo" min="1" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="codice_reward" class="form-label">Seleziona una Reward:</label>
                <select class="form-select" id="codice_reward" name="codice_reward" required>
                    <?php foreach ($rewards as $reward): ?>
                        <option value="<?php echo $reward['codice']; ?>">
                            <?php echo htmlspecialchars($reward['descrizione']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Conferma Finanziamento</button>
        </form>
    </div>
</div>

<!-- Footer -->
<footer class="footer text-white py-4">
    <div class="container text-center">
        <p class="footer-copy">&copy; 2025 BoStarter. Tutti i diritti riservati.</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>