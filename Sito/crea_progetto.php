<?php
require 'session.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email_creatore = $_SESSION['email'];
$errore = '';

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome_progetto = trim($_POST['nome']);
        $descrizione = trim($_POST['descrizione']);
        $budget = floatval($_POST['budget']);
        $data_limite = $_POST['data_limite'];
        $tipo_progetto = $_POST['tipo_progetto']; // 'hardware' o 'software'
        $componenti = $_POST['componenti'] ?? []; // Solo se hardware

        if (empty($nome_progetto) || empty($descrizione) || $budget <= 0 || empty($data_limite) || !in_array($tipo_progetto, ['hardware', 'software'])) {
            throw new Exception("Compila tutti i campi correttamente.");
        }

        $pdo->beginTransaction();

        // Inserisci il progetto principale
        $stmt = $pdo->prepare("INSERT INTO PROGETTO (nome, descrizione, data_inserimento, email_creatore, budget, data_limite) 
                               VALUES (:nome, :descrizione, CURDATE(), :email_creatore, :budget, :data_limite)");
        $stmt->execute([
            'nome' => $nome_progetto,
            'descrizione' => $descrizione,
            'email_creatore' => $email_creatore,
            'budget' => $budget,
            'data_limite' => $data_limite
        ]);

        // Inserisci nella tabella specifica hardware/software
        if ($tipo_progetto == 'hardware') {
            $stmtHardware = $pdo->prepare("INSERT INTO PROGETTO_HARDWARE (nome_progetto) VALUES (:nome_progetto)");
            $stmtHardware->execute(['nome_progetto' => $nome_progetto]);

            // Inserisci i componenti (se presenti)
            $stmtComponente = $pdo->prepare("INSERT INTO COMPONENTE (nome, descrizione, prezzo, quantita) 
                                             VALUES (:nome, :descrizione, :prezzo, :quantita)
                                             ON DUPLICATE KEY UPDATE descrizione = VALUES(descrizione), prezzo = VALUES(prezzo), quantita = VALUES(quantita)");

            $stmtFormato = $pdo->prepare("INSERT INTO FORMATO (nome_componente, nome_hardware) VALUES (:nome_componente, :nome_hardware)");

            foreach ($componenti as $componente) {
                if (!empty($componente['nome']) && !empty($componente['descrizione']) && $componente['prezzo'] > 0 && $componente['quantita'] > 0) {
                    $stmtComponente->execute([
                        'nome' => $componente['nome'],
                        'descrizione' => $componente['descrizione'],
                        'prezzo' => $componente['prezzo'],
                        'quantita' => $componente['quantita']
                    ]);
                    $stmtFormato->execute([
                        'nome_componente' => $componente['nome'],
                        'nome_hardware' => $nome_progetto
                    ]);
                }
            }
        } else {
            $stmtSoftware = $pdo->prepare("INSERT INTO PROGETTO_SOFTWARE (nome_progetto) VALUES (:nome_progetto)");
            $stmtSoftware->execute(['nome_progetto' => $nome_progetto]);
        }

        $pdo->commit();
        header("Location: index.php");
        exit();
    }
} catch (Exception $e) {
    $pdo->rollBack();
    $errore = $e->getMessage();
}


// TODO: Implementaer meglio il cricametno delle immagini, verificare perchè progetto hardware non funziona
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea un Progetto - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script>
        function mostraComponenti() {
            const tipoProgetto = document.getElementById("tipo_progetto").value;
            document.getElementById("componenti-container").style.display = tipoProgetto === "hardware" ? "block" : "none";
        }

        function aggiungiComponente() {
            const container = document.getElementById("componenti");
            const nuovoComponente = document.createElement("div");
            nuovoComponente.classList.add("row", "mb-2");
            nuovoComponente.innerHTML = `
                <div class="col-md-3"><input type="text" name="componenti[][nome]" class="form-control" placeholder="Nome" required></div>
                <div class="col-md-3"><input type="text" name="componenti[][descrizione]" class="form-control" placeholder="Descrizione" required></div>
                <div class="col-md-2"><input type="number" name="componenti[][prezzo]" class="form-control" placeholder="Prezzo" min="0.01" step="0.01" required></div>
                <div class="col-md-2"><input type="number" name="componenti[][quantita]" class="form-control" placeholder="Quantità" min="1" required></div>
                <div class="col-md-2"><button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">Rimuovi</button></div>
            `;
            container.appendChild(nuovoComponente);
        }
    </script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BoStarter</a>
    </div>
</nav>

<!-- Contenuto -->
<div class="container mt-5 pt-4">
    <h1 class="mb-4">Crea un Nuovo Progetto</h1>

    <?php if ($errore): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errore); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label">Nome del Progetto</label>
            <input type="text" class="form-control" name="nome" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Descrizione</label>
            <textarea class="form-control" name="descrizione" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Budget (€)</label>
            <input type="number" class="form-control" name="budget" min="1" step="0.01" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Data Limite</label>
            <input type="date" class="form-control" name="data_limite" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Carica Immagini</label>
            <input type="file" class="form-control" name="foto[]" multiple accept="image/*">
        </div>

        <div class="mb-3">
            <label class="form-label">Tipo di Progetto</label>
            <select class="form-select" name="tipo_progetto" id="tipo_progetto" onchange="mostraComponenti()" required>
                <option value="hardware">Hardware</option>
                <option value="software">Software</option>
            </select>
        </div>

        <!-- Sezione Componenti (solo per hardware) -->
        <div id="componenti-container" style="display: none;">
            <h5>Componenti</h5>
            <div id="componenti"></div>
            <button type="button" class="btn btn-secondary" onclick="aggiungiComponente()">Aggiungi Componente</button>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Crea Progetto</button>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>