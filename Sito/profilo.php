<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}



if (isset($_GET['action']) && isset($_GET['candidatura_id'])) {
    $action = $_GET['action'];
    $candidatura_id = $_GET['candidatura_id'];

    // Verifica che la candidatura appartenga a un progetto software creato dall'utente loggato
    $stmtCheck = $pdo->prepare("
    SELECT C.id
    FROM CANDIDATURA C
    JOIN PROFILO PF ON C.id_profilo = PF.id
    JOIN PROGETTO_SOFTWARE PS ON PF.nome_software = PS.nome_progetto
    JOIN PROGETTO pr ON PS.nome_progetto = pr.nome
    WHERE C.id = :cid AND pr.email_creatore = :email
");
    $stmtCheck->execute(['cid' => $candidatura_id, 'email' => $_SESSION['email']]);
    if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
        if ($action === 'accept') {
            $stmtUpdate = $pdo->prepare("UPDATE CANDIDATURA SET esito = 1 WHERE id = :cid");
            $stmtUpdate->execute(['cid' => $candidatura_id]);
        } elseif ($action === 'reject') {
            $stmtUpdate = $pdo->prepare("UPDATE CANDIDATURA SET esito = -1 WHERE id = :cid");
            $stmtUpdate->execute(['cid' => $candidatura_id]);
        }
        header("Location: profilo.php");
        exit();
    }
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

// Se l'utente è un creatore, recupera numero progetti e affidabilità
$extraDatiCreatore = null;
if ($user && $user['tipo_utente'] === 'Creatore') {
    $stmtCreatore = $pdo->prepare("SELECT nr_progetti, affidabilita FROM UTENTE_CREATORE WHERE email_utente_creat = :email");
    $stmtCreatore->execute(['email' => $_SESSION['email']]);
    $extraDatiCreatore = $stmtCreatore->fetch(PDO::FETCH_ASSOC);
}

// Gestione salvataggio competenze
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['competenze'])) {
    $pdo->beginTransaction();
    try {
        // Rimuove tutte le competenze esistenti per l'utente
        $deleteStmt = $pdo->prepare("DELETE FROM INDICA WHERE email_utente = ?");
        $deleteStmt->execute([$_SESSION['email']]);

        // Inserisce le nuove competenze (solo se il livello selezionato è maggiore di 0)
        foreach ($_POST['competenze'] as $competenza => $livello) {
            if ($livello >= 0) {
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
$skillsQuery = $pdo->query("SELECT s.competenza, i.livello AS selected_level
                           FROM SKILL s
                           LEFT JOIN INDICA i ON s.competenza = i.competenza 
                               AND i.email_utente = '" . $_SESSION['email'] . "'
                           ORDER BY s.competenza");
$competenze = [];
foreach ($skillsQuery as $row) {
    $competenze[$row['competenza']] = [
        'livelli' => [0,1, 2, 3, 4, 5], // Livelli fissi da 0 a 5
        'selected' => $row['selected_level']
    ];
}

// Recupera candidature ricevute (per progetti software creati dall'utente)
$stmtReceived = $pdo->prepare("
    SELECT C.id, C.email_utente AS candidato, C.esito, pr.nome AS project_name, PF.nome AS profile_name
    FROM CANDIDATURA C
    JOIN PROFILO PF ON C.id_profilo = PF.id
    JOIN PROGETTO_SOFTWARE PS ON PF.nome_software = PS.nome_progetto
    JOIN PROGETTO pr ON PS.nome_progetto = pr.nome
    WHERE pr.email_creatore = :email
");
$stmtReceived->execute(['email' => $_SESSION['email']]);
$receivedCandidatures = $stmtReceived->fetchAll(PDO::FETCH_ASSOC);

// Recupera candidature inviate (dall'utente)
$stmtSent = $pdo->prepare("
    SELECT C.id, C.esito, PF.nome AS profile_name, PS.nome_progetto AS project_name
    FROM CANDIDATURA C
    JOIN PROFILO PF ON C.id_profilo = PF.id
    JOIN PROGETTO_SOFTWARE PS ON PF.nome_software = PS.nome_progetto
    WHERE C.email_utente = :email
");
$stmtSent->execute(['email' => $_SESSION['email']]);
$sentCandidatures = $stmtSent->fetchAll(PDO::FETCH_ASSOC);

// Recupera le reward ottenute dall'utente loggato
$stmtRewardUtente = $pdo->prepare("
    SELECT R.descrizione, R.foto_url, F.nome_progetto
    FROM FINANZIAMENTO F
    JOIN REWARD R ON F.codice_reward = R.codice
    WHERE F.email_utente = :email
");
$stmtRewardUtente->execute(['email' => $_SESSION['email']]);
$rewardConseguite = $stmtRewardUtente->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Mio Profilo - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .table-actions a {
            margin-right: 5px;
        }
    </style>
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
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Informazioni dell'utente -->
            <div class="col-md-6">
                <p><strong>Nome:</strong> <?= htmlspecialchars($user['nome'] . ' ' . $user['cognome']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Nickname:</strong> <?= htmlspecialchars($user['nickname']) ?></p>
                <p><strong>Tipo Utente:</strong> <?= htmlspecialchars($user['tipo_utente']) ?></p>
                <?php if ($user['tipo_utente'] === 'Creatore' && $extraDatiCreatore): ?>
                    <p><strong>Numero di Progetti:</strong> <?= htmlspecialchars($extraDatiCreatore['nr_progetti']) ?></p>
                    <p><strong>Affidabilità:</strong> <?= htmlspecialchars($extraDatiCreatore['affidabilita']) ?>/10</p>
                <?php endif; ?>
                <p><strong>Anno di Nascita:</strong> <?= htmlspecialchars($user['anno_nascita']) ?></p>
                <p><strong>Luogo di Nascita:</strong> <?= htmlspecialchars($user['luogo_nascita']) ?></p>
                <a href="logout.php" class="btn btn-danger">Logout</a>
                <?php if ($user['tipo_utente'] === 'Amministratore'): ?>
                    <a href="admin_login.php" class="btn btn-warning mt-3">Gestione Competenze</a>
                <?php endif; ?>
            </div>

            <!-- Form per salvare le competenze -->
            <div class="col-md-6">
                <form method="POST">
                    <h4>Le tue Competenze</h4>
                    <?php foreach ($competenze as $competenza => $dati): ?>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars($competenza) ?></label>
                            <select name="competenze[<?= htmlspecialchars($competenza) ?>]" class="form-select">

                                <?php for ($i = 0; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= (isset($dati['selected']) && $dati['selected'] == $i) ? 'selected' : '' ?>>
                                        Livello <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Salva Competenze</button>
                </form>
            </div>
        </div>

        <!-- Sezione: Candidature Ricevute (per progetti software creati dall'utente) -->
        <div class="mt-5">
            <h4>Candidature Ricevute</h4>
            <?php if (count($receivedCandidatures) > 0): ?>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Candidato</th>
                        <th>Progetto</th>
                        <th>Profilo</th>
                        <th>Esito</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($receivedCandidatures as $cand): ?>
                        <tr>
                            <td><?= htmlspecialchars($cand['id']) ?></td>
                            <td><?= htmlspecialchars($cand['candidato']) ?></td>
                            <td><?= htmlspecialchars($cand['project_name']) ?></td>
                            <td><?= htmlspecialchars($cand['profile_name']) ?></td>
                            <td>
                                <?php
                                if ($cand['esito'] == 1) {
                                    echo '<strong style="color: green;">Accettato</strong>';
                                } elseif ($cand['esito'] == -1) {
                                    echo '<strong style="color: red;">Rifiutato</strong>';
                                } else {
                                    echo "In attesa";
                                }
                                ?>
                            </td>
                            <td class="table-actions">
                                <?php if ($cand['esito'] == 0): ?>
                                    <a href="profilo.php?action=accept&candidatura_id=<?= $cand['id'] ?>" class="btn btn-success btn-sm">Accetta</a>
                                    <a href="profilo.php?action=reject&candidatura_id=<?= $cand['id'] ?>" class="btn btn-danger btn-sm">Rifiuta</a>
                                <?php else: ?>
                                    Nessuna azione
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nessuna candidatura ricevuta.</p>
            <?php endif; ?>
        </div>

        <!-- Sezione: Candidature Inviate dall'utente -->
        <div class="mt-5">
            <h4>Candidature Inviate</h4>
            <?php if (count($sentCandidatures) > 0): ?>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Progetto</th>
                        <th>Profilo</th>
                        <th>Esito</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sentCandidatures as $cand): ?>
                        <tr>
                            <td><?= htmlspecialchars($cand['id']) ?></td>
                            <td><?= htmlspecialchars($cand['project_name']) ?></td>
                            <td><?= htmlspecialchars($cand['profile_name']) ?></td>
                            <td>
                                <?php
                                if ($cand['esito'] == 1) {
                                    echo '<strong style="color: green;">Accettato</strong>';
                                } elseif ($cand['esito'] == -1) {
                                    echo '<strong style="color: red;">Rifiutato</strong>';
                                } else {
                                    echo "In attesa";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nessuna candidatura inviata.</p>
            <?php endif; ?>
        </div>

        <!-- Sezione: Reward Ottenute -->
        <div class="mt-5">
            <h4>🎁 Reward Ottenute</h4>
            <?php if (!empty($rewardConseguite)): ?>
                <div class="row">
                    <?php foreach ($rewardConseguite as $reward): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <?php if (!empty($reward['foto_url'])): ?>
                                    <img src="<?= htmlspecialchars($reward['foto_url']) ?>" class="card-img-top" alt="Immagine Reward" style="object-fit: cover; height: 200px;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex justify-content-center align-items-center" style="height: 200px;">
                                        Nessuna immagine
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($reward['descrizione']) ?></h5>
                                    <p class="card-text text-muted">Progetto: <strong><?= htmlspecialchars($reward['nome_progetto']) ?></strong></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Non hai ancora ottenuto reward.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>