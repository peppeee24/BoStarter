<?php


try {
    require_once 'session.php'; // Include la connessione al database

    // Controllo se il form è stato inviato
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Recupero dati dal form
        $nome = trim($_POST['name']);
        $cognome = trim($_POST['surname']);
        $nickname = trim($_POST['nickname']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Cifratura password
        $anno_nascita = intval($_POST['birth-year']);
        $luogo_nascita = trim($_POST['birth-place']);

        // Controllo se l'email è già registrata
        $checkEmail = $pdo->prepare("SELECT email FROM UTENTE WHERE email = :email");
        $checkEmail->execute(['email' => $email]);
        if ($checkEmail->rowCount() > 0) {
            echo "Errore: L'email è già registrata.";
            exit;
        }

        // Query di inserimento nel database
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
        ]);

        echo "Registrazione completata con successo!";
        header("Location: login.html");
        exit();

    }
} catch (PDOException $e) {
    echo "Errore di connessione al database: " . $e->getMessage();
}

// TODO trovato errore le passowrd non si criptano piu
?>
