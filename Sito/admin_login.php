<?php
session_start();
require_once 'session.php';

$error = '';

// Recupero l'email dell'utete se loggato
$admin_email = $_SESSION['email'] ?? '';

// Se la richiesta è di tipo post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $security_code = $_POST['security_code'] ?? '';

    // Ottengo il codice di sicurezza dell'amministratore per verificare la passowrd inserita e procedere con l'autenticazione
    $stmt = $pdo->prepare("SELECT codice_sicurezza FROM UTENTE_AMMINISTRATORE WHERE email_utente_amm = :email");
    $stmt->execute(['email' => $admin_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($security_code, $user['codice_sicurezza'])) {
        $_SESSION['admin_logged'] = true;
        header("Location: manage_skills.php");
        exit();
    } else {
        $error = "Codice di sicurezza errato o non sei un amministratore";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BoStarter</a>
    </div>
</nav>
<br><br><br>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>Accesso Amministratore</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="security_code" class="form-label">Codice di Sicurezza</label>
                        <input type="password" class="form-control" id="security_code" name="security_code" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Accedi</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>