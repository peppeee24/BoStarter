<?php
require_once 'session.php';
$pdo->prepare("CALL sp_chiudi_progetti_scaduti()")->execute();

// Controllo se l'utente è loggato
$loggedIn = isset($_SESSION['email']);
$userData = null;
$nickname = '';
$isCreator = false;

if ($loggedIn) {
    // Verifica se l'utente è un creatore per fare comparire il pulsate per creare i progetti
    // Successivamente verifico se l'utente è loggato o no per far compaire il nome o lign

    $stmt = $pdo->prepare("SELECT U.nickname, C.email_utente_creat 
                          FROM UTENTE U
                          LEFT JOIN UTENTE_CREATORE C ON U.email = C.email_utente_creat 
                          WHERE U.email = :email");
    $stmt->execute(['email' => $_SESSION['email']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $nickname = $userData ? htmlspecialchars($userData['nickname']) : 'Profilo';
    $isCreator = !empty($userData['email_utente_creat']);
}

// Numero di progetti per pagina
$progetti_per_pagina = 6;

// Conto il numero totale di progetti per gestire il conto della paginazione
$stmt_count = $pdo->prepare("SELECT COUNT(*) AS totale FROM PROGETTO");
$stmt_count->execute();
$totale_progetti = $stmt_count->fetch(PDO::FETCH_ASSOC)['totale'];

// Calcolo il numero totale di pagine
$totale_pagine = ceil($totale_progetti / $progetti_per_pagina);

// Ottengo il numero di pagina dalla query string (default: 1)
$pagina_corrente = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

// Calcolo l'offset per SQL per saltare la visualizzazione dei prini n progetti in base alla paginazione
$offset = ($pagina_corrente - 1) * $progetti_per_pagina;

// Query per ottenere i progetti per la pagina corrente
$query = "SELECT P.nome, P.descrizione, P.budget, P.stato, 
                 (SELECT foto_url FROM FOTO_PROGETTO FP WHERE FP.nome_progetto = P.nome LIMIT 1) AS foto_url 
          FROM PROGETTO P 
          ORDER BY P.data_inserimento DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $progetti_per_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$progetti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
                <li class="nav-item"><a class="nav-link" href="#progetti">Progetti</a></li>
                <li class="nav-item"><a class="nav-link" href="statistiche.php">Statistiche</a></li>
                <?php if ($loggedIn && $isCreator): ?>
                    <li class="nav-item"><a class="nav-link" href="crea_progetto.php">Crea un Progetto</a></li>
                <?php endif; ?>
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

<section class="hero-section text-center text-white py-5">
    <div class="container">
        <h1 class="display-4 fw-bold">Benvenuto su BoStarter</h1>
        <p class="lead">Dai vita alle tue idee con il supporto della community.</p>
        <a href="#progetti" class="btn btn-primary btn-lg">Scopri i Progetti</a>
        <?php if ($loggedIn && $isCreator): ?>
            <a href="crea_progetto.php" class="btn btn-outline-light btn-lg">Crea un Progetto</a>
        <?php endif; ?>
    </div>
</section>

<section id="progetti" class="featured-projects py-5">
    <div class="container">
        <h2 class="text-center mb-5">Progetti in Evidenza</h2>
        <div class="row">
            <?php foreach ($progetti as $progetto): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo $progetto['foto_url'] ? htmlspecialchars($progetto['foto_url']) : 'https://via.placeholder.com/400x300.png?text=Immagine+Non+Disponibile'; ?>"
                             class="card-img-top" alt="<?php echo htmlspecialchars($progetto['nome']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($progetto['nome']); ?></h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($progetto['descrizione'], 0, 100))) . '...'; ?></p>
                            <p><strong>Budget:</strong> €<?php echo number_format($progetto['budget'], 2); ?></p>
                            <p><strong>Stato:</strong> <span class="badge <?php echo ($progetto['stato'] == 'aperto') ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo ucfirst($progetto['stato']); ?>
                            </span></p>
                            <a href="progetto.php?nome_progetto=<?php echo urlencode($progetto['nome']); ?>" class="btn btn-primary w-100">Scopri di più</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <nav aria-label="Navigazione">
            <ul class="pagination justify-content-center mt-4">
                <?php if ($pagina_corrente > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina_corrente - 1; ?>">Precedente</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totale_pagine; $i++): ?>
                    <li class="page-item <?php echo ($pagina_corrente == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagina_corrente < $totale_pagine): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina_corrente + 1; ?>">Successivo</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
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