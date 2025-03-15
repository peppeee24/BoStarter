<?php
session_start();
require 'session.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email_creatore = $_SESSION['email'];
$errore = '';

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        error_log("Inizio processo POST per creazione progetto.");

        // Validazione dati base
        $nome_progetto = trim($_POST['nome']);
        $descrizione = trim($_POST['descrizione']);
        $budget = floatval(str_replace(',', '.', $_POST['budget']));
        $data_limite = $_POST['data_limite'];
        $tipo_progetto = $_POST['tipo_progetto'];
        $componenti = $_POST['componenti'] ?? [];

        if (empty($nome_progetto) || strlen($nome_progetto) > 255 ||
            empty($descrizione) ||
            $budget <= 0 ||
            !DateTime::createFromFormat('Y-m-d', $data_limite) ||
            !in_array($tipo_progetto, ['hardware', 'software'])) {
            throw new Exception("Dati del progetto non validi");
        }

        $pdo->beginTransaction();

        // Inserimento progetto principale
        $stmtProgetto = $pdo->prepare("INSERT INTO PROGETTO 
            (nome, descrizione, data_inserimento, email_creatore, budget, data_limite) 
            VALUES (?, ?, CURDATE(), ?, ?, ?)");
        $stmtProgetto->execute([
            $nome_progetto,
            $descrizione,
            $email_creatore,
            $budget,
            $data_limite
        ]);

        // Inserimento tipo specifico
        if ($tipo_progetto == 'hardware') {
            $pdo->prepare("INSERT INTO PROGETTO_HARDWARE (nome_progetto) VALUES (?)")
                ->execute([$nome_progetto]);

            // Gestione componenti
            if (!empty($componenti)) {
                $stmtComponente = $pdo->prepare("INSERT INTO COMPONENTE 
                    (nome, descrizione, prezzo, quantita) 
                    VALUES (?, ?, ?, ?)");

                $stmtFormato = $pdo->prepare("INSERT INTO FORMATO 
                    (nome_componente, nome_hardware) 
                    VALUES (?, ?)");

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
            $pdo->prepare("INSERT INTO PROGETTO_SOFTWARE (nome_progetto) VALUES (?)")
                ->execute([$nome_progetto]);
        }

        // Gestione immagini
        if (!empty($_FILES['foto']['name'][0])) {
            $uploadDir = 'uploads/progetti/' . $nome_progetto . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $stmtFoto = $pdo->prepare("INSERT INTO FOTO_PROGETTO 
                (nome_progetto, foto_url) VALUES (?, ?)");

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
    <style>
        .preview-img {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            margin: 5px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .componente-box {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
    </style>
    <script>
        // Funzioni per gestione componenti
        function mostraComponenti() {
            const tipo = document.getElementById('tipo_progetto').value;
            document.getElementById('componenti-section').style.display = tipo === 'hardware' ? 'block' : 'none';
        }

        function aggiungiComponente() {
            const container = document.getElementById('componenti-container');
            const index = Date.now();

            const html = `
                <div class="componente-box">
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
                                    onclick="this.closest('.componente-box').remove()">Rimuovi</button>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
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

        // Conversione virgole in punti per decimali
        document.querySelector('form').addEventListener('submit', function(e) {
            document.querySelectorAll('input[type="number"]').forEach(input => {
                if (input.step === '0.01') {
                    input.value = input.value.replace(',', '.');
                }
            });
        });
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BoStarter</a>
    </div>
</nav>

<main class="container mt-5 pt-4">
    <h1 class="mb-4">Crea Nuovo Progetto</h1>

    <?php if ($errore): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <!-- Sezione base -->
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

        <!-- Sezione immagini -->
        <div class="mb-4">
            <label class="form-label">Immagini del Progetto (max 5MB, jpg/png/webp)</label>
            <input type="file" class="form-control" name="foto[]" multiple
                   accept="image/jpeg, image/png, image/webp"
                   onchange="mostraAnteprima(this)">
            <div id="anteprima-imgs" class="mt-2"></div>
        </div>

        <!-- Sezione tipo progetto -->
        <div class="mb-4">
            <label class="form-label">Tipo Progetto *</label>
            <select name="tipo_progetto" id="tipo_progetto"
                    class="form-select" onchange="mostraComponenti()" required>
                <option value="">Seleziona...</option>
                <option value="hardware">Hardware</option>
                <option value="software">Software</option>
            </select>
        </div>

        <!-- Sezione componenti hardware -->
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

        <button type="submit" class="btn btn-primary btn-lg">Crea Progetto</button>
    </form>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>