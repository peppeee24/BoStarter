<?php
// Configurazione della connessione al database
$host = 'localhost'; // Cambia con il tuo host se necessario
$dbname = 'BOSTARTER';
$username = 'root'; // Cambia con il tuo utente MySQL
$password = ''; // Inserisci la tua password MySQL

try {
    // Connessione al database con PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    }
} catch (PDOException $e) {
    echo "Errore di connessione al database: " . $e->getMessage();
}
?>
