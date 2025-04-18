<?php
require_once 'session.php';

// Controllo se l'utente è loggato per il nome nell'header
$loggedIn = isset($_SESSION['email']);
$userData = null;
$nickname = '';

if ($loggedIn) {
    // Verifica il nickname dell'utente loggato
    $stmt = $pdo->prepare("SELECT nickname FROM UTENTE WHERE email = :email");
    $stmt->execute(['email' => $_SESSION['email']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $nickname = $userData ? htmlspecialchars($userData['nickname']) : 'Profilo';
}

// Query per la classifica degli utenti creatori in base all'affidabilità
$sql_affidabilita = "SELECT nickname, affidabilita FROM ClassificaCreatori";
$result_affidabilita = $pdo->query($sql_affidabilita);

// Query per i progetti aperti con il minor scostamento tra budget e finanziamenti ricevuti
$sql_progetti = "SELECT nome, budget, mancante FROM ProgettiViciniAlCompletamento";
$result_progetti = $pdo->query($sql_progetti);

// Query per la classifica degli utenti in base ai finanziamenti erogati
$sql_finanziamenti = "SELECT nickname, totale_finanziato FROM ClassificaFinanziatori";
$result_finanziamenti = $pdo->query($sql_finanziamenti);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Navbar -->
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
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#progetti">Progetti</a></li>
                <li class="nav-item"><a class="nav-link" href="statistiche.php">Statistiche</a></li>
                <?php if ($loggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $nickname; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profilo.php">Profilo</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light px-3" href="login.php">Accedi</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section text-center text-white py-5">
    <div class="container">
        <h1 class="display-4 fw-bold">Statistiche di BoStarter</h1>
        <p class="lead">Visualizza i principali risultati della nostra piattaforma</p>
    </div>
</section>

<!-- Statistiche -->
<section class="statistics-section py-5">
    <div class="container">
        <!-- Classifica Affidabilità -->
        <h2 class="text-center mb-5">Top 3 Utenti Creatori (Affidabilità)</h2>
        <ul class="list-group">
            <?php while($row = $result_affidabilita->fetch(PDO::FETCH_ASSOC)): ?>
                <!-- Se l'affidabilità è bassa, evidenziamo la riga in rosso -->
                <li class="list-group-item <?php echo ($row['affidabilita'] < 5) ? 'affidabilita-bassa' : ''; ?>">
                    <?php echo htmlspecialchars($row['nickname']) . " - Affidabilità: " . $row['affidabilita']; ?>
                </li>
            <?php endwhile; ?>
        </ul>

        <!-- Progetti più vicini al completamento -->
        <h2 class="text-center mt-5 mb-5">Top 3 Progetti più vicini al completamento</h2>
        <ul class="list-group">
            <?php while($row = $result_progetti->fetch(PDO::FETCH_ASSOC)): ?>
                <li class="list-group-item">
                    <?php echo htmlspecialchars($row['nome']) . " - Budget: €" . $row['budget'] . " - Mancante: €" . $row['mancante']; ?>
                </li>
            <?php endwhile; ?>
        </ul>

        <!-- Classifica finanziamenti -->
        <h2 class="text-center mt-5 mb-5">Top 3 Utenti per Finanziamenti Erogati</h2>
        <ul class="list-group">
            <?php while($row = $result_finanziamenti->fetch(PDO::FETCH_ASSOC)): ?>
                <li class="list-group-item">
                    <?php echo htmlspecialchars($row['nickname']) . " - Totale Finanziamenti: €" . $row['totale_finanziato']; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</section>

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