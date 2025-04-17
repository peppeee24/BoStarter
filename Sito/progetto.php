<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'session.php'; // Connessione al database

if (!isset($_GET['nome_progetto'])) {
    die("Errore: Nessun progetto specificato.");
}

$nome_progetto = $_GET['nome_progetto'];
$loggedIn = isset($_SESSION['email']);
$email_utente = $loggedIn ? $_SESSION['email'] : null;

try {
    // Recupera i dettagli del progetto e il suo tipo
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

    // Verifica se la data di scadenza è passata e, in tal caso, aggiorna lo stato a "chiuso"
    $data_limite = strtotime($progetto['data_limite']);
    if ($data_limite < time()) {
        $stmtUpdate = $pdo->prepare("UPDATE PROGETTO SET stato = 'chiuso' WHERE nome = :nome");
        $stmtUpdate->execute(['nome' => $nome_progetto]);
    }

    // Recupera componenti se il progetto è di tipo hardware
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

    // Per i progetti software, recupera i profili necessari e per ciascuno le relative skill richieste
    $profili = [];
    if ($progetto['tipo_progetto'] === 'software') {
        $stmtProfili = $pdo->prepare("
            SELECT p.id, p.nome as nome_profilo, c.competenza, c.livello
            FROM PROFILO p
            LEFT JOIN COMPRENDE c ON p.id = c.id_profilo
            WHERE p.nome_software = :nome
        ");
        $stmtProfili->execute(['nome' => $nome_progetto]);
        $profili_skills = $stmtProfili->fetchAll(PDO::FETCH_ASSOC);
        foreach ($profili_skills as $row) {
            $id = $row['id'];
            if (!isset($profili[$id])) {
                $profili[$id] = [
                    'id' => $row['id'],
                    'nome_profilo' => $row['nome_profilo'],
                    'skills' => []
                ];
            }
            if (!empty($row['competenza'])) {
                $profili[$id]['skills'][] = [
                    'competenza' => $row['competenza'],
                    'livello' => $row['livello']
                ];
            }
        }
        // Riorganizza l'array dei profili per avere indici numerici consecutivi
        $profili = array_values($profili);
    }

    // Recupera immagini associate al progetto
    $stmtImg = $pdo->prepare("SELECT foto_url FROM FOTO_PROGETTO WHERE nome_progetto = :nome");
    $stmtImg->execute(['nome' => $nome_progetto]);
    $immagini = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

    $stmtFinanziamenti = $pdo->prepare("SELECT SUM(importo) AS totale_finanziato FROM FINANZIAMENTO WHERE nome_progetto = :nome");
    $stmtFinanziamenti->execute(['nome' => $nome_progetto]);
    $totale_finanziato = $stmtFinanziamenti->fetch(PDO::FETCH_ASSOC)['totale_finanziato'] ?? 0;

    $budget_raggiunto = $totale_finanziato >= $progetto['budget'];
    $progetto_chiuso = $progetto['stato'] === 'chiuso';

    // Recupera commenti e, se presenti, le eventuali risposte
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

    // Gestione dell'inserimento di un nuovo commento
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

    // Gestione dell'inserimento di una risposta a un commento (solo per il creatore del progetto)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['risposta_commento']) && isset($_POST['id_commento']) && $loggedIn && $email_utente === $progetto['email_creatore']) {
        $testo_risposta = trim($_POST['risposta_commento']);
        $id_commento = intval($_POST['id_commento']);

        if (!empty($testo_risposta)) {
            try {
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
            } catch (PDOException $e) {
                echo "<p style='color: red; text-align: center;'>Errore nell'inserimento della risposta: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

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

<div class="container mt-5 pt-4">

    <h1><?php echo htmlspecialchars($progetto['nome']); ?></h1>

    <p class="text-muted">Creato da: <strong><?php echo htmlspecialchars($progetto['email_creatore']); ?></strong></p>
    <p class="text-muted">Creato il: <?php echo date("d-m-Y", strtotime($progetto['data_inserimento'])); ?></p>
    <p class="text-muted">Data di Chiusura: <?php echo date("d-m-Y", strtotime($progetto['data_limite'])); ?></p>

    <p><?php echo nl2br(htmlspecialchars($progetto['descrizione'])); ?></p>

    <h4>Budget richiesto: €<?php echo number_format($progetto['budget'], 2); ?></h4>
    <p><strong>Importo finanziato:</strong> €<?php echo number_format($totale_finanziato, 2); ?></p>
    <p><strong>Stato:</strong> <span class="badge <?php echo $progetto_chiuso ? 'bg-danger' : 'bg-success'; ?>">
    <?php echo $progetto_chiuso ? 'Chiuso' : 'Aperto'; ?>
    </span></p>

    <!-- Carosello immagini -->
    <?php if (!empty($immagini)): ?>
        <div id="progettoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
            <!-- Indicatori -->
            <div class="carousel-indicators">
                <?php foreach ($immagini as $index => $img): ?>
                    <button type="button" data-bs-target="#progettoCarousel" 
                            data-bs-slide-to="<?php echo $index; ?>" 
                            class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                <?php endforeach; ?>
            </div>
            
            <!-- Slide -->
            <div class="carousel-inner rounded">
                <?php foreach ($immagini as $index => $img): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($img['foto_url']); ?>" 
                             class="d-block w-100 img-fluid" 
                             alt="Immagine progetto <?php echo $index + 1; ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Controlli -->
            <button class="carousel-control-prev" type="button" data-bs-target="#progettoCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#progettoCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Badge tipo progetto -->
    <div class="alert alert-info mb-4">
        Tipo progetto:
        <span class="badge bg-dark">
        <?php echo strtoupper(htmlspecialchars($progetto['tipo_progetto'])) ?>
    </span>
    </div>

    <!-- Sezione per progetti software: Profili richiesti e relative skill -->
    <?php if ($progetto['tipo_progetto'] === 'software' && !empty($profili)): ?>
        <div class="mt-4">
            <h4>Profili Richiesti</h4>
            <?php foreach ($profili as $profilo): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($profilo['nome_profilo']); ?></h5>
                        <?php if (!empty($profilo['skills'])): ?>
                            <ul class="list-group">
                                <?php foreach ($profilo['skills'] as $skill): ?>
                                    <li class="list-group-item">
                                        <strong><?php echo htmlspecialchars($skill['competenza']); ?></strong>
                                        - Livello richiesto: <?php echo htmlspecialchars($skill['livello']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Nessuna skill richiesta per questo profilo.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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

    <!-- Pulsanti di azione -->
    <div class="mt-4">
        <?php if ($loggedIn && !$budget_raggiunto && !$progetto_chiuso): ?>
            <a href="finanzia.php?nome_progetto=<?php echo urlencode($progetto['nome']); ?>"
               class="btn btn-primary me-2">
                Finanzia il Progetto
            </a>

            <?php if ($progetto['tipo_progetto'] === 'software' && $email_utente !== $progetto['email_creatore']): ?>
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
                <textarea class="form-control" name="nuovo_commento" rows="3" required placeholder="Inserisci il tuo commento..."></textarea>
                <button type="submit" class="btn btn-primary mt-2">Invia Commento</button>
            </form>
        <?php else: ?>
            <p><a href="login.html">Accedi</a> per lasciare un commento.</p>
        <?php endif; ?>

        <?php if (empty($commenti)): ?>
            <div class="alert alert-info">Nessun commento presente. Sii il primo a commentare!</div>
        <?php else: ?>
            <?php foreach ($commenti as $commento): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($commento['email_utente']); ?></strong>
                            <small class="text-muted"><?php echo date("d-m-Y", strtotime($commento['data_commento'])); ?></small>
                        </div>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($commento['testo'])); ?></p>

                        <?php if ($commento['risposta']): ?>
                            <div class="alert alert-light mt-3">
                                <div class="d-flex justify-content-between">
                                    <strong>Risposta del creatore:</strong>
                                    <small class="text-muted"><?php echo date("d-m-Y", strtotime($commento['data_risposta'])); ?></small>
                                </div>
                                <p class="mt-1"><?php echo nl2br(htmlspecialchars($commento['risposta'])); ?></p>
                            </div>
                        <?php elseif ($loggedIn && $email_utente === $progetto['email_creatore']): ?>
                            <!-- Form di risposta visibile solo al creatore del progetto -->
                            <form action="" method="POST" class="mt-3">
                                <input type="hidden" name="id_commento" value="<?php echo $commento['id']; ?>">
                                <div class="form-group">
                                    <label for="risposta_<?php echo $commento['id']; ?>">Rispondi a questo commento:</label>
                                    <textarea class="form-control" id="risposta_<?php echo $commento['id']; ?>" name="risposta_commento" rows="2" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-primary btn-sm mt-2">Invia risposta</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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