<?php
require 'session.php'; // Connessione al database

if (!isset($_GET['nome_progetto'])) {
    die("Errore: Nessun progetto specificato.");
}

$nome_progetto = $_GET['nome_progetto'];
$loggedIn = isset($_SESSION['email']);
$email_utente = $loggedIn ? $_SESSION['email'] : null;

try {
    // Recupera i dettagli del progetto e il tipo
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

    // Recupera componenti solo per hardware
    $componenti = [];
    if ($progetto['tipo_progetto'] === 'hardware') {
        $stmtComponenti = $pdo->prepare("
            SELECT C.nome, C.descrizione, C.prezzo, C.quantita 
            FROM COMPONENTE C
            JOIN FORMATO F ON C.nome = F.nome_componente
            WHERE F.nome_hardware = :nome
        ");
        $stmtComponenti->execute(['nome' => $nome_progetto]);
        $componenti = $stmtComponenti->fetchAll(PDO::FETCH_ASSOC);
    }

    // Recupera immagini e altri dati...
    $stmtImg = $pdo->prepare("SELECT foto_url FROM FOTO_PROGETTO WHERE nome_progetto = :nome");
    $stmtImg->execute(['nome' => $nome_progetto]);
    $immagini = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

    $stmtFinanziamenti = $pdo->prepare("SELECT SUM(importo) AS totale_finanziato FROM FINANZIAMENTO WHERE nome_progetto = :nome");
    $stmtFinanziamenti->execute(['nome' => $nome_progetto]);
    $totale_finanziato = $stmtFinanziamenti->fetch(PDO::FETCH_ASSOC)['totale_finanziato'] ?? 0;

    $budget_raggiunto = $totale_finanziato >= $progetto['budget'] || strtotime($progetto['data_limite']) < time();
    $progetto_chiuso = $progetto['stato'] === 'chiuso';

    $stmtCommenti = $pdo->prepare("
        SELECT C.id, C.email_utente, C.data_commento, C.testo, 
               R.testo AS risposta, R.data_risposta, R.email_creatore
        FROM COMMENTO C
        LEFT JOIN RISPOSTA_COMMENTO R ON C.id = R.id_commento
        WHERE C.nome_progetto = :nome
        ORDER BY C.data_commento DESC
    ");
    $stmtCommenti->execute(['nome' => $nome_progetto]);
    $commenti = $stmtCommenti->fetchAll(PDO::FETCH_ASSOC);

    // Se il form per inserire commenti è stato inviato
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nuovo_commento']) && $loggedIn) {
        $testo_commento = trim($_POST['nuovo_commento']);
        if (!empty($testo_commento)) {
            $stmtInserisciCommento = $pdo->prepare("
                INSERT INTO COMMENTO (email_utente, nome_progetto, data_commento, testo)
                VALUES (:email, :nome_progetto, CURDATE(), :testo)
            ");
            $stmtInserisciCommento->execute([
                'email' => $email_utente,
                'nome_progetto' => $nome_progetto,
                'testo' => $testo_commento
            ]);
            header("Location: progetto.php?nome_progetto=" . urlencode($nome_progetto));
            exit();
        }
    }

    // Se il form per rispondere a un commento è stato inviato
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['risposta_commento']) && isset($_POST['id_commento']) && $email_utente === $progetto['email_creatore']) {
        $testo_risposta = trim($_POST['risposta_commento']);
        $id_commento = intval($_POST['id_commento']);

        if (!empty($testo_risposta)) {
            $stmtInserisciRisposta = $pdo->prepare("
                INSERT INTO RISPOSTA_COMMENTO (id_commento, email_creatore, data_risposta, testo)
                VALUES (:id_commento, :email_creatore, CURDATE(), :testo)
            ");
            $stmtInserisciRisposta->execute([
                'id_commento' => $id_commento,
                'email_creatore' => $email_utente,
                'testo' => $testo_risposta
            ]);
            header("Location: progetto.php?nome_progetto=" . urlencode($nome_progetto));
            exit();
        }
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
    <title><?php echo htmlspecialchars($progetto['nome']); ?> - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BoStarter</a>
    </div>
</nav>

<div class="container mt-5 pt-4">
    <!-- Badge tipo progetto -->
    <div class="alert alert-info mb-4">
        Tipo progetto:
        <span class="badge bg-dark">
            <?php echo strtoupper(htmlspecialchars($progetto['tipo_progetto'])) ?>
        </span>
    </div>



    <!-- Sezione componenti hardware -->
    <?php if ($progetto['tipo_progetto'] === 'hardware' && !empty($componenti)): ?>
        <div class="mt-4">
            <h4>Componenti Richiesti</h4>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($componenti as $componente): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($componente['nome']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($componente['descrizione']); ?></p>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">
                                        Prezzo: €<?php echo number_format($componente['prezzo'], 2); ?>
                                    </span>
                                    <span class="text-muted">
                                        Quantità: <?php echo htmlspecialchars($componente['quantita']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>


    <!-- Contenuto della Pagina -->
    <div class="container mt-5 pt-4">
        <h1 class="mt-4"><?php echo htmlspecialchars($progetto['nome']); ?></h1>
        <p class="text-muted">Creato da: <strong><?php echo htmlspecialchars($progetto['email_creatore']); ?></strong></p>
        <p class="text-muted">Creato il: <?php echo date("d-m-Y", strtotime($progetto['data_inserimento'])); ?></p>
        <p><?php echo nl2br(htmlspecialchars($progetto['descrizione'])); ?></p>

        <h4>Budget richiesto: €<?php echo number_format($progetto['budget'], 2); ?></h4>
        <p><strong>Importo finanziato:</strong> €<?php echo number_format($totale_finanziato, 2); ?></p>
        <p><strong>Stato:</strong> <span class="badge <?php echo $progetto_chiuso ? 'bg-danger' : 'bg-success'; ?>">
        <?php echo $progetto_chiuso ? 'Chiuso' : 'Aperto'; ?>
    </span></p>
        <!-- Galleria immagini -->
        <div class="row">
            <?php foreach ($immagini as $img): ?>
                <div class="col-md-4 mb-3">
                    <img src="<?php echo htmlspecialchars($img['foto_url']); ?>" class="img-fluid rounded" alt="Immagine progetto">
                </div>
            <?php endforeach; ?>
        </div>

    <!-- Pulsanti di azione modificati -->
    <div class="mt-4">
        <?php if ($loggedIn && !$budget_raggiunto && !$progetto_chiuso): ?>
            <a href="finanzia.php?nome_progetto=<?php echo urlencode($progetto['nome']); ?>"
               class="btn btn-primary me-2">
                Finanzia il Progetto
            </a>

            <?php if ($progetto['tipo_progetto'] === 'software'): ?>
                <a href="partecipa.php?nome_progetto=<?php echo urlencode($progetto['nome']); ?>"
                   class="btn btn-success">
                    Partecipa al Progetto
                </a>
            <?php endif; ?>

        <?php elseif (!$loggedIn): ?>
            <button class="btn btn-secondary me-2" disabled>Accedi per finanziare</button>
            <?php if ($progetto['tipo_progetto'] === 'software'): ?>
                <button class="btn btn-secondary" disabled>Accedi per partecipare</button>
            <?php endif; ?>

        <?php else: ?>
            <button class="btn btn-secondary me-2" disabled>Finanziamento completato</button>
            <?php if ($progetto['tipo_progetto'] === 'software'): ?>
                <button class="btn btn-secondary" disabled>Partecipazione chiusa</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>


        <!-- Sezione Commenti -->
        <div class="mt-5">
            <h3>Commenti</h3>
            <?php if ($loggedIn): ?>
                <form action="" method="POST" class="mb-4">
                    <textarea class="form-control" name="nuovo_commento" rows="3" required></textarea>
                    <button type="submit" class="btn btn-primary mt-2">Invia Commento</button>
                </form>
            <?php else: ?>
                <p><a href="login.html">Accedi</a> per lasciare un commento.</p>
            <?php endif; ?>

            <?php foreach ($commenti as $commento): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <strong><?php echo htmlspecialchars($commento['email_utente']); ?></strong>
                        <p><?php echo nl2br(htmlspecialchars($commento['testo'])); ?></p>
                        <?php if ($commento['risposta']): ?>
                            <div class="alert alert-secondary mt-2">
                                <strong>Risposta:</strong> <?php echo nl2br(htmlspecialchars($commento['risposta'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>