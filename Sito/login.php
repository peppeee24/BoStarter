<?php

// Connessione al database
require_once 'session.php';
$errorMessage = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Controlla se email e password sono settati prima di usarli
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Se uno dei campi è vuoto, mostro errore
    if (empty($email) || empty($password)) {
        $errorMessage = "Compila tutti i campi";
        exit();
    }


    // Verifico se l'utente esiste nel database
    $stmt = $pdo->prepare("SELECT email, password FROM UTENTE WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Controllo password
    if ($user && password_verify($password, $user['password'])) {
        // Salvp l'utente nella sessione
        $_SESSION['email'] = $user['email'];
        // Reindirizza alla dashboard
        header("Location: dashboard.html");
        echo "Login completata con successo!";
        header("Location: index.php");
        exit();
    } else {
        $errorMessage = "L'email inserita o la password sono errate. Riprova.";
    }
}

?>



<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BoStarter</title>
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

    <section class="auth-container">
        <div class="auth-card">
            <div class="auth-header text-center">
                <h2>🔐 Accedi a BoStarter</h2>
                <p class="text-muted">Bentornato! Inserisci le credenziali per continuare.</p>
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger"><?= $errorMessage ?></div>
                <?php endif; ?>
            </div>
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email"  name="email" placeholder="Inserisci la tua email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Inserisci la tua password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Accedi</button>
            </form>
            <div class="auth-footer">
                <p>Non hai un account? <a href="registrati.php" class="auth-link">Registrati</a></p>
            </div>
        </div>
    </section>

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