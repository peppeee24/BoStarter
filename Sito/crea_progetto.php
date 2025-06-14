<?php
session_start();
require 'session.php';

// Verifico se sono loggato, se non lo sono vengo reinderizzato alla pagina di login
if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

// Se sono loggato ottengo l'utente
$email_creatore = $_SESSION['email'];
$errore = '';

// Verifico tutte le skill disponibili (raggruppo per nome per evitare duplicati)
$skill_disponibili = [];
try {
    $stmtSkill = $pdo->prepare("SELECT DISTINCT competenza FROM SKILL ORDER BY competenza");
    $stmtSkill->execute();

    // Recupero l'output delal query selezionando la prima colonna
    $skill_disponibili = $stmtSkill->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Errore nel caricamento delle skill: " . $e->getMessage());
}


// Provo a creare il nuovo progetto
try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        error_log("Inizio processo POST per creazione progetto.");

        // Validazione dati inseriti nel form
        $nome_progetto = trim($_POST['nome']);
        $descrizione = trim($_POST['descrizione']);
        $budget = floatval(str_replace(',', '.', $_POST['budget']));
        $data_limite = $_POST['data_limite'];
        $tipo_progetto = $_POST['tipo_progetto'];
        $componenti = $_POST['componenti'] ?? [];
        $profili = $_POST['profili'] ?? [];
        $rewards = $_POST['rewards'] ?? [];

        // Vincolo per obbligare l'utente a compilare correttamente alcune parti del form
        if (empty($nome_progetto) || strlen($nome_progetto) > 255 ||
            empty($descrizione) ||
            $budget <= 0 ||
            !DateTime::createFromFormat('Y-m-d', $data_limite) ||
            !in_array($tipo_progetto, ['hardware', 'software'])) {
            throw new Exception("Dati del progetto non validi");
        }

        // Inizio la transazione quindi le operazioni devono essere eseguite tutte con successo oppure nessuna
        // Si fa riferimento a tutte le operazione di insert nelal tabella
        // Lo facciamo per evitare di inserire informazioni parziali
        // Questo blocco termina con $pdo->commit();
        $pdo->beginTransaction();

        // Inserimento progetto principale
        // ??? sono dei segnaposto per i valori, servono per evitare di lasciare spazi buchi per SQL Injection
        $stmtProgetto = $pdo->prepare("CALL sp_crea_progetto(?, ?, ?, ?, ?, ?)");
        $data_oggi = date('Y-m-d');
        $stmtProgetto->execute([
            $nome_progetto,
            $descrizione,
            $data_oggi,
            $email_creatore,
            $budget,
            $data_limite
        ]);
        /*$stmtProgetto = $pdo->prepare("INSERT INTO PROGETTO 
            (nome, descrizione, data_inserimento, email_creatore, budget, data_limite) 
            VALUES (?, ?, CURDATE(), ?, ?, ?)");
        $stmtProgetto->execute([
            $nome_progetto,
            $descrizione,
            $email_creatore,
            $budget,
            $data_limite
        ]);*/

        // Inserimento tipo specifico
        if ($tipo_progetto == 'hardware') {
            $pdo->prepare("CALL sp_crea_progetto_hardware(?)")
                ->execute([$nome_progetto]);
            /*$pdo->prepare("INSERT INTO PROGETTO_HARDWARE (nome_progetto) VALUES (?)")
                ->execute([$nome_progetto]);*/

            // Gestione componenti
            if (!empty($componenti)) {
                $stmtComponente = $pdo->prepare("CALL sp_aggiungi_componente(?, ?, ?, ?)");
                $stmtFormato = $pdo->prepare("CALL sp_link_formato(?, ?)");
                /*$stmtComponente = $pdo->prepare("INSERT INTO COMPONENTE 
                    (nome, descrizione, prezzo, quantita) 
                    VALUES (?, ?, ?, ?)");

                $stmtFormato = $pdo->prepare("INSERT INTO FORMATO 
                    (nome_componente, nome_hardware) 
                    VALUES (?, ?)");*/

                foreach ($componenti as $comp) {
                    $prezzo = (float)str_replace(',', '.', $comp['prezzo']);
                    $quantita = (int)$comp['quantita'];

                    if ($prezzo > 0 && $quantita > 0) {
                        $stmtComponente->execute([
                            $comp['nome'],
                            $comp['descrizione'],
                            $prezzo,
                            $quantita
                        ]);

                        $stmtFormato->execute([
                            $comp['nome'],
                            $nome_progetto
                        ]);
                    }
                }
            }
        } else {
            $pdo->prepare("CALL sp_crea_progetto_software(?)")
                ->execute([$nome_progetto]);
            /*$pdo->prepare("INSERT INTO PROGETTO_SOFTWARE (nome_progetto) VALUES (?)")
                ->execute([$nome_progetto]);*/

            // Gestione profili per progetti software
            if (!empty($profili)) {
                //$stmtProfilo = $pdo->prepare("CALL sp_crea_profilo(?, ?)");
                $stmtProfilo = $pdo->prepare("INSERT INTO PROFILO 
                    (nome, nome_software) 
                    VALUES (?, ?)");
                
                $stmtComprende = $pdo->prepare("CALL sp_aggiungi_comprende(?, ?, ?)");
                /*$stmtComprende = $pdo->prepare("INSERT INTO COMPRENDE
                    (competenza, livello, id_profilo)
                    VALUES (?, ?, ?)");*/

                foreach ($profili as $profilo) {
                    if (!empty($profilo['nome'])) {
                        // Inserisce il profilo
                        $stmtProfilo->execute([
                            $profilo['nome'],
                            $nome_progetto
                        ]);

                        // Ottengo l'ID del profilo appena inserito
                        $id_profilo = $pdo->lastInsertId();

                        // Gestisce le skill associate al profilo
                        if (!empty($profilo['skills'])) {
                            foreach ($profilo['skills'] as $competenza => $livello) {
                                // Verifico se la competenza esiste
                                $stmtVerifica = $pdo->prepare("SELECT COUNT(*) FROM SKILL WHERE competenza = ?");
                                $stmtVerifica->execute([$competenza]);
                                $esiste = $stmtVerifica->fetchColumn();

                                // Se la competenza esiste, viene inserita nella tabella COMPRENDE
                                if ($esiste && $livello >= 0 && $livello <= 5) {
                                    $stmtComprende->execute([
                                        $competenza,
                                        $livello,
                                        $id_profilo
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Gestione rewards
        if (!empty($rewards)) {
            $stmtReward = $pdo->prepare("CALL sp_aggiungi_reward(?, ?, ?)");
            /*$stmtReward = $pdo->prepare("INSERT INTO REWARD 
                (nome_progetto, descrizione, foto_url) 
                VALUES (?, ?, ?)");*/

            // Cartella per le immagini dei reward
            $uploadRewardDir = 'images/uploads/progetti/' . $nome_progetto . '/rewards/';
            if (!file_exists($uploadRewardDir)) {

                mkdir($uploadRewardDir, 0755, true);
            }

            foreach ($rewards as $rewardKey => $reward) {
                if (!empty($reward['descrizione'])) {
                    $foto_url = ''; // Valore predefinito se non c'è immagine

                    // Gestione immagine del reward
                    if (!empty($_FILES['reward_foto']['name'][$rewardKey]) &&
                        $_FILES['reward_foto']['error'][$rewardKey] === UPLOAD_ERR_OK) {

                        $tmpName = $_FILES['reward_foto']['tmp_name'][$rewardKey];

                        // Validazione dell'immagine
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($tmpName);
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

                        if (in_array($mime, $allowedTypes) &&
                            $_FILES['reward_foto']['size'][$rewardKey] <= 5242880) { // 5MB

                            $ext = [
                                'image/jpeg' => 'jpg',
                                'image/png' => 'png',
                                'image/webp' => 'webp'
                            ][$mime] ?? 'bin';

                            $filename = uniqid('reward_') . '.' . $ext;
                            $path = $uploadRewardDir . $filename;

                            if (move_uploaded_file($tmpName, $path)) {
                                $foto_url = $path;
                            }
                        }
                    }

                    // Inserimento del reward
                    $stmtReward->execute([
                        $nome_progetto,
                        $reward['descrizione'],
                        $foto_url
                    ]);
                }
            }
        }

        // Gestione immagini
        if (!empty($_FILES['foto']['name'][0])) {
            $uploadDir = 'images/uploads/progetti/' . $nome_progetto . '/';

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $stmtFoto = $pdo->prepare("CALL sp_aggiungi_foto(?, ?)");
            /*$stmtFoto = $pdo->prepare("INSERT INTO FOTO_PROGETTO 
                (nome_progetto, foto_url) VALUES (?, ?)");*/

            foreach ($_FILES['foto']['tmp_name'] as $key => $tmpName) {
                // Validazione
                if ($_FILES['foto']['error'][$key] !== UPLOAD_ERR_OK) continue;

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpName);
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($mime, $allowedTypes) ||
                    $_FILES['foto']['size'][$key] > 5242880) { // 5MB
                    continue;
                }

                $ext = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ][$mime] ?? 'bin';

                $filename = uniqid('img_') . '.' . $ext;
                $path = $uploadDir . $filename;

                if (move_uploaded_file($tmpName, $path)) {
                    $stmtFoto->execute([$nome_progetto, $path]);
                }
            }
        }

        // Fine transazione
        $pdo->commit();
        header("Location: index.php");
        exit();

    }
} catch (PDOException $e) {
    $pdo->rollBack();
    $errore = "Errore database: " . $e->getMessage();
    error_log($errore);
} catch (Exception $e) {
    $pdo->rollBack();
    $errore = $e->getMessage();
    error_log($errore);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Progetto - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/style.css">

    <script>
        // Funzioni per gestione componenti e profili
        function mostraComponentiOProfili() {
            const tipo = document.getElementById('tipo_progetto').value;
            document.getElementById('componenti-section').style.display = tipo === 'hardware' ? 'block' : 'none';
            document.getElementById('profili-section').style.display = tipo === 'software' ? 'block' : 'none';
        }

        function aggiungiComponente() {
            const container = document.getElementById('componenti-container');
            const index = Date.now();

            const html = `
                <div class="componente-box2">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="componenti[${index}][nome]"
                                   class="form-control" placeholder="Nome" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="componenti[${index}][descrizione]"
                                   class="form-control" placeholder="Descrizione" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="componenti[${index}][prezzo]"
                                   class="form-control" placeholder="Prezzo" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="componenti[${index}][quantita]"
                                   class="form-control" placeholder="Quantità" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger"
                                    onclick="this.closest('.componente-box2').remove()">Rimuovi</button>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
        }

        function aggiungiProfilo() {
            const container = document.getElementById('profili-container');
            const index = Date.now();

            const html = `
                <div class="profilo-box">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome Profilo *</label>
                            <input type="text" name="profili[${index}][nome]"
                                   class="form-control" placeholder="Nome del profilo richiesto" required>
                        </div>
                        <div class="col-md-4 text-end d-flex align-items-end">
                            <button type="button" class="btn btn-danger"
                                    onclick="this.closest('.profilo-box').remove()">Rimuovi Profilo</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>Competenze richieste:</h6>
                        <div class="skill-list">
                            <?php foreach ($skill_disponibili as $competenza): ?>
                            <div class="skill-item">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   id="skill_<?= htmlspecialchars($competenza) ?>_<?= $index ?>"
                                                   onchange="toggleSkillLevel(this, '${index}', '<?= htmlspecialchars($competenza) ?>')" required>
                                            <label class="form-check-label" for="skill_<?= htmlspecialchars($competenza) ?>_<?= $index ?>" >
                                                <?= htmlspecialchars($competenza) ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="form-select"
                                                id="level_<?= htmlspecialchars($competenza) ?>_${index}"
                                                name="profili[${index}][skills][<?= htmlspecialchars($competenza) ?>]"
                                                disabled required>
                                            <option value="">Seleziona livello</option>
                                            <option value="0">0 - Non richiesto</option>
                                            <option value="1">1 - Base</option>
                                            <option value="2">2 - Elementare</option>
                                            <option value="3">3 - Intermedio</option>
                                            <option value="4">4 - Avanzato</option>
                                            <option value="5">5 - Esperto</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
        }

        function aggiungiReward() {
            const container = document.getElementById('reward-container');
            const index = Date.now();

            const html = `
                <div class="reward-box2">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Descrizione *</label>
                            <textarea name="rewards[${index}][descrizione]" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Immagine</label>
                            <input type="file" class="form-control" name="reward_foto[${index}]"
                                   accept="image/jpeg, image/png, image/webp"
                                   onchange="mostraAnteprimaReward(this, ${index})" required>
                            <div id="anteprima-reward-${index}" class="mt-2"></div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger"
                                    onclick="this.closest('.reward-box2').remove()">Rimuovi</button>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
        }

        function toggleSkillLevel(checkbox, profiloIndex, skillName) {
            const levelSelect = document.getElementById(`level_${skillName}_${profiloIndex}`);
            if (checkbox.checked) {
                levelSelect.disabled = false;
                levelSelect.required = true;
            } else {
                levelSelect.disabled = true;
                levelSelect.required = false;
                levelSelect.value = "";
            }
        }

        // Anteprima immagini
        function mostraAnteprima(input) {
            const container = document.getElementById('anteprima-imgs');
            container.innerHTML = '';

            Array.from(input.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-img');
                    container.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }

        // Anteprima immagini reward
        function mostraAnteprimaReward(input, index) {
            const container = document.getElementById(`anteprima-reward-${index}`);
            container.innerHTML = '';

            if (input.files && input.files[0] && input.files[0].type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-img');
                    container.appendChild(img);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Conversione virgole in punti per decimali
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('form').addEventListener('submit', function(e) {
                document.querySelectorAll('input[type="number"]').forEach(input => {
                    if (input.step === '0.01') {
                        input.value = input.value.replace(',', '.');
                    }
                });
            });
        });

        // Validazione: almeno una reward deve essere presente
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form-progetto');
            const rewardContainer = document.getElementById('reward-container');
            const tipoSelect = document.getElementById('tipo_progetto');
            const componentiContainer = document.getElementById('componenti-container');
            const profiliContainer = document.getElementById('profili-container');

            form.addEventListener('submit', function (e) {
                // Check almeno una reward
                const rewardBoxes = rewardContainer.querySelectorAll('.reward-box2');
                if (rewardBoxes.length === 0) {
                    e.preventDefault();
                    alert("Devi aggiungere almeno una reward per creare il progetto.");
                    return false;
                }

                // Se tipo = hardware, controlla che ci sia almeno un componente
                if (tipoSelect.value === 'hardware') {
                    const componentiBoxes = componentiContainer.querySelectorAll('.componente-box2');
                    if (componentiBoxes.length === 0) {
                        e.preventDefault();
                        alert("Devi aggiungere almeno un componente hardware.");
                        return false;
                    }
                }

                // Se tipo = software, controlla che ci sia almeno un profilo
                if (tipoSelect.value === 'software') {
                    const profiliBoxes = profiliContainer.querySelectorAll('.profilo-box');
                    if (profiliBoxes.length === 0) {
                        e.preventDefault();
                        alert("Devi aggiungere almeno un profilo richiesto.");
                        return false;
                    }
                }
            });

            // Controllo dimensione immagini progetto
            const immaginiProgettoInput = document.querySelector('input[name="foto[]"]');
            const maxFileSize = 5 * 1024 * 1024; // max 5MB

            if (immaginiProgettoInput && immaginiProgettoInput.files.length > 0) {
                for (let file of immaginiProgettoInput.files) {
                    if (file.size > maxFileSize) {
                        e.preventDefault();
                        alert(`L'immagine "${file.name}" del progetto supera il limite di 5 MB.`);
                        return false;
                    }
                }
            }
        });


    </script>
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

<main class="container mt-5 pt-4">
    <h1 class="mb-4">Crea Nuovo Progetto</h1>

    <?php if ($errore): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="form-progetto">
        <div class="mb-4">
            <label class="form-label">Nome Progetto *</label>
            <input type="text" name="nome" class="form-control" required maxlength="255">
        </div>

        <div class="mb-4">
            <label class="form-label">Descrizione *</label>
            <textarea name="descrizione" class="form-control" rows="4" required></textarea>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label">Budget (€) *</label>
                <input type="number" name="budget" class="form-control"
                       step="0.01" min="0.01" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Data Limite *</label>
                <input type="date" name="data_limite" class="form-control"
                       min="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Immagini del Progetto (max 5MB, jpg/png/webp)</label>
            <input type="file" class="form-control" name="foto[]" multiple
                   accept="image/jpeg, image/png, image/webp"
                   onchange="mostraAnteprima(this)" required>
            <div id="anteprima-imgs" class="mt-2"></div>
        </div>

        <div class="mb-4">
            <label class="form-label">Premi per il Progetto</label>
            <div id="reward-container"></div>
            <button type="button" class="btn btn-secondary mt-2" onclick="aggiungiReward()">
                + Aggiungi Reward
            </button>
        </div>


        <div class="mb-4">
            <label class="form-label">Tipo Progetto *</label>
            <select name="tipo_progetto" id="tipo_progetto"
                    class="form-select" onchange="mostraComponentiOProfili()" required>
                <option value="">Seleziona...</option>
                <option value="hardware">Hardware</option>
                <option value="software">Software</option>
            </select>
        </div>

        <div id="componenti-section" style="display: none;">
            <div class="mb-4">
                <h5>Componenti Hardware</h5>
                <div id="componenti-container"></div>
                <button type="button" class="btn btn-secondary mt-2"
                        onclick="aggiungiComponente()">
                    + Aggiungi Componente
                </button>
            </div>
        </div>


        <div id="profili-section" style="display: none;">
            <div class="mb-4">
                <h5>Profili Richiesti</h5>
                <div id="profili-container"></div>
                <button type="button" class="btn btn-secondary mt-2"
                        onclick="aggiungiProfilo()">
                    + Aggiungi Profilo
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Crea Progetto</button>
    </form>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>