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
        $securityCode = trim($_POST['security_code'] ?? '');

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

// Gestione tipi utente speciali
            if ($userType === 'admin') {
                if (empty($securityCode)) {
                    throw new Exception("Il codice di sicurezza è obbligatorio per gli amministratori");
                }

                $sqlAdmin = "INSERT INTO UTENTE_AMMINISTRATORE (email_utente_amm, codice_sicurezza)
VALUES (:email, :codice)";
                $stmtAdmin = $pdo->prepare($sqlAdmin);
                $stmtAdmin->execute(['email' => $email, 'codice' => $securityCode]);
            } elseif ($userType === 'creator') {
                $sqlCreator = "INSERT INTO UTENTE_CREATORE (email_utente_creat) VALUES (:email)";
                $stmtCreator = $pdo->prepare($sqlCreator);
                $stmtCreator->execute(['email' => $email]);
            }

            $pdo->commit();
            header("Location: login.html");
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