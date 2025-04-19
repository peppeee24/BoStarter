<?php
try {
    require_once 'session.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = trim($_POST['name']);
        $cognome = trim($_POST['surname']);
        $nickname = trim($_POST['nickname']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $anno_nascita = intval($_POST['birth-year']);
        $luogo_nascita = trim($_POST['birth-place']);
        $userType = $_POST['user_type'] ?? 'normal';
        $securityCode = password_hash(trim($_POST['security_code']), PASSWORD_BCRYPT);

        // Verifica email esistente
        $checkEmail = $pdo->prepare("SELECT email FROM UTENTE WHERE email = :email");
        $checkEmail->execute(['email' => $email]);
        if ($checkEmail->rowCount() > 0) {
            throw new Exception("L'email è già registrata.");
        }

// Inizia transazione
        $pdo->beginTransaction();

        try {
        // Inserimento utente base
        $stmt = $pdo->prepare("CALL sp_inserisci_utente(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $email,
            $nickname,
            $password,
            $nome,
            $cognome,
            $anno_nascita,
            $luogo_nascita
        ]);
/*
            $sql = "INSERT INTO UTENTE (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita)
VALUES (:email, :nickname, :password, :nome, :cognome, :anno_nascita, :luogo_nascita)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'email' => $email,
                'nickname' => $nickname,
                'password' => $password,
                'nome' => $nome,
                'cognome' => $cognome,
                'anno_nascita' => $anno_nascita,
                'luogo_nascita' => $luogo_nascita
            ]);*/

// Gestione tipi utente speciali
            if ($userType === 'admin') {
                if (empty($securityCode)) {
                    throw new Exception("Il codice di sicurezza è obbligatorio per gli amministratori");
                }
                
                $stmtAdmin = $pdo->prepare("CALL sp_inserisci_amministratore(?, ?)");
                $stmtAdmin->execute([$email, $securityCode]);
                /*$sqlAdmin = "INSERT INTO UTENTE_AMMINISTRATORE (email_utente_amm, codice_sicurezza)
VALUES (:email, :codice)";
                $stmtAdmin = $pdo->prepare($sqlAdmin);
                $stmtAdmin->execute(['email' => $email, 'codice' => $securityCode]);*/
            } elseif ($userType === 'creator') {
                $stmtCreator = $pdo->prepare("CALL sp_inserisci_creatore(?, ?, ?)");
                $stmtCreator->execute([$email, 0, 0.0]); // nr_progetti = 0, affid = 0.0 come default

                /*$sqlCreator = "INSERT INTO UTENTE_CREATORE (email_utente_creat) VALUES (:email)";
                $stmtCreator = $pdo->prepare($sqlCreator);
                $stmtCreator->execute(['email' => $email]);*/
            }

            $pdo->commit();
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "Errore durante la registrazione: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    $errorMessage = "Errore di connessione al database: " . $e->getMessage();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}
?>



<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="auth-page">

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

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Registrati su BoStarter</h2>
            <p>Crea un account per iniziare a supportare progetti incredibili.</p>
            <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
            <?php endif; ?>
        </div>
        <form action="registrati.php" method="POST">
            <div class="register-grid">
                <div>
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div>
                    <label for="surname" class="form-label">Cognome</label>
                    <input type="text" class="form-control" id="surname" name="surname" required>
                </div>
                <div>
                    <label for="nickname" class="form-label">Nickname</label>
                    <input type="text" class="form-control" id="nickname" name="nickname" required>
                </div>
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div>
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div>
                    <label for="confirm-password" class="form-label">Conferma Password</label>
                    <input type="password" class="form-control" id="confirm-password" name="confirm-password" required>
                </div>
                <div>
                    <label for="birth-year" class="form-label">Anno di Nascita</label>
                    <input type="number" class="form-control" id="birth-year" name="birth-year" min="1900" max="2023" required>
                </div>
                <div>
                    <label for="birth-place" class="form-label">Luogo di Nascita</label>
                    <input type="text" class="form-control" id="birth-place" name="birth-place" required>
                </div>
                <div class="full-width">
                    <label class="form-label">Tipo Utente</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="user_type" id="userTypeNormal" value="normal" checked>
                        <label class="form-check-label" for="userTypeNormal">Utente Normale</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="user_type" id="userTypeCreator" value="creator">
                        <label class="form-check-label" for="userTypeCreator">Creatore</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="user_type" id="userTypeAdmin" value="admin">
                        <label class="form-check-label" for="userTypeAdmin">Amministratore</label>
                    </div>
                </div>
                <div class="full-width mb-3" id="securityCodeField" style="display: none;">
                    <label for="security_code" class="form-label">Codice di Sicurezza</label>
                    <input type="text" class="form-control" id="security_code" name="security_code" placeholder="Inserisci il codice di sicurezza">
                </div>
                <div class="full-width">
                    <button type="submit" class="btn btn-primary w-100">Registrati</button>
                </div>
            </div>
        </form>
        <div class="auth-footer">
            <p>Hai già un account? <a href="login.php" class="auth-link">Accedi</a></p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userTypeAdmin = document.getElementById('userTypeAdmin');
        const securityCodeField = document.getElementById('securityCodeField');

        function toggleSecurityCode() {
            const isAdmin = document.querySelector('input[name="user_type"]:checked').value === 'admin';
            securityCodeField.style.display = isAdmin ? 'block' : 'none';
            document.getElementById('security_code').toggleAttribute('required', isAdmin);
        }

        document.querySelectorAll('input[name="user_type"]').forEach(radio => {
            radio.addEventListener('change', toggleSecurityCode);
        });

        toggleSecurityCode();
    });
</script>
</body>
</html>
