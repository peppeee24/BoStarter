DROP DATABASE IF EXISTS BOSTARTER;
CREATE DATABASE IF NOT EXISTS BOSTARTER;
USE BOSTARTER;


CREATE TABLE UTENTE (
    email VARCHAR(255) PRIMARY KEY,
    nickname VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    anno_nascita YEAR NOT NULL,
    luogo_nascita VARCHAR(100) NOT NULL
) ENGINE "INNODB";


CREATE TABLE UTENTE_AMMINISTRATORE(
	email_utente_amm VARCHAR(255) PRIMARY KEY,
    codice_sicurezza VARCHAR(50) NOT NULL,
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE

) ENGINE "INNODB";

CREATE TABLE UTENTE_CREATORE(
	email_utente_creat VARCHAR(255) PRIMARY KEY,
    nr_progetti INT DEFAULT 0,
    affidabilita FLOAT CHECK (livello BETWEEN 0 AND 10), /* Valutare se enum "Buono", ecc */
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE
) ENGINE "INNODB";


CREATE TABLE SKILL_CURRICULUM(
    competenza VARCHAR(100),
    livello INT CHECK (livello BETWEEN 0 AND 5),
    email_utente VARCHAR(255),
    email_utente_amm VARCHAR(255),
    PRIMARY KEY (competenza, livello),
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE,
    FOREIGN KEY (email_utente_amm) REFERENCES UTENTE_AMMINISTRATORE(email_utente_amm) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE COMMENTO (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_utente VARCHAR(255) NOT NULL,
    nome_progetto VARCHAR(255) NOT NULL,
    data_commento DATE NOT NULL,
    testo TEXT NOT NULL,
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO(nome) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE RISPOSTA_COMMENTO (
    id_commento INT,
    email_creatore VARCHAR(255),
    data_risposta DATE NOT NULL,
    testo TEXT NOT NULL,
    PRIMARY KEY (id_commento, email_creatore),
    FOREIGN KEY (id_commento) REFERENCES COMMENTO(id) ON DELETE CASCADE,
    FOREIGN KEY (email_creatore) REFERENCES UTENTE_CREATORE(email_utente_creat) ON DELETE CASCADE
);

CREATE TABLE PROGETTO (
    nome VARCHAR(255) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    data_inserimento DATE NOT NULL,
    email_creatore VARCHAR(255) NOT NULL,
    budget FLOAT NOT NULL CHECK (budget > 0),
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') NOT NULL DEFAULT 'aperto',
    FOREIGN KEY (email_creatore) REFERENCES UTENTE_CREATORE(email_utente_creat) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE FOTO_PROGETTO (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_progetto VARCHAR(255) NOT NULL,
    foto_url TEXT NOT NULL,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO(nome) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE REWARD (
    codice INT AUTO_INCREMENT PRIMARY KEY,
    nome_progetto VARCHAR(255) NOT NULL,
    descrizione TEXT NOT NULL,
    foto_url TEXT NOT NULL,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO(nome) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE PROGETTO_HARDWARE (
    nome_progetto VARCHAR(255) PRIMARY KEY,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO(nome) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE COMPONENTE (
    nome VARCHAR(255) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    prezzo FLOAT NOT NULL CHECK (prezzo > 0),
    quantita INT NOT NULL CHECK (quantita > 0)
) ENGINE "INNODB";

CREATE TABLE FORMATO (
    nome_componente VARCHAR(255),
    nome_hardware VARCHAR(255),
    PRIMARY KEY (nome_componente, nome_hardware),
    FOREIGN KEY (nome_hardware) REFERENCES PROGETTO_HARDWARE(nome_progetto) ON DELETE CASCADE,
    FOREIGN KEY (nome_componente) REFERENCES COMPONENTE(nome) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE PROGETTO_SOFTWARE (
    nome_progetto VARCHAR(255) PRIMARY KEY,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO(nome) ON DELETE CASCADE
) ENGINE "INNODB";


CREATE TABLE PROFILO (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255),
    nome_software VARCHAR(255),
    FOREIGN KEY (nome_software) REFERENCES PROGETTO_SOFTWARE(nome_progetto) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE SKILL_RICHIESTE (
    nome_profilo VARCHAR(255),
    competenza VARCHAR(100),
    livello INT CHECK (livello BETWEEN 0 AND 5),
    PRIMARY KEY (competenza, livello),
    FOREIGN KEY (nome_profilo) REFERENCES PROFILO(nome) ON DELETE CASCADE
) ENGINE "INNODB";

CREATE TABLE FINANZIAMENTO (
    id INT AUTO_INCREMENT,
    data_finanziamento DATE,
    importo FLOAT NOT NULL CHECK (importo > 0),
    email_utente VARCHAR(255) NOT NULL,
    nome_progetto VARCHAR(255) NOT NULL,
    codice_reward INT NOT NULL,
    PRIMARY KEY (id, data_finanziamento),
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO(nome) ON DELETE CASCADE,
    FOREIGN KEY (codice_reward) REFERENCES REWARD(codice) ON DELETE CASCADE
) ENGINE "INNODB";


CREATE TABLE CANDIDATURA (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_utente VARCHAR(255) NOT NULL,
    /* nome_progetto VARCHAR(255) NOT NULL, */
    nome_profilo VARCHAR(255) NOT NULL,
    esito BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE,
    /* FOREIGN KEY (nome_progetto) REFERENCES PROGETTO(nome) ON DELETE CASCADE,*/
    FOREIGN KEY (nome_profilo) REFERENCES PROFILO(nome) ON DELETE CASCADE
);


-- Verifico il vincolo della candidatura: 
/*
La piattaforma consente ad un utente di inserire una candidatura su un profilo SOLO se, 
per ogni skill richiesta da un profilo, l'utente dispone di un livello superiore o uguale 
al valore richiesto.
*/

CREATE VIEW Candidature_Valide AS
SELECT C.email_utente, C.nome_progetto, C.nome_profilo
FROM CANDIDATURA C
JOIN SKILL_CURRICULUM SC ON C.email_utente = SC.email_utente
JOIN SKILL_RICHIESTE SP ON C.nome_profilo = SP.nome_profilo
WHERE SC.competenza = SP.competenza AND SC.livello >= SP.livello;


-- Verifico il vincolo della budget: 
/*
Quando la somma totale degli importi dei finanziamenti supera il budget del progetto il progetto cambia il suo stato in chiuso.  
*/
DELIMITER //
CREATE TRIGGER verifica_budget_superato
AFTER INSERT ON FINANZIAMENTO
FOR EACH ROW
BEGIN
    DECLARE totale DECIMAL(10,2);
    DECLARE budget DECIMAL(10,2);
    
    -- Calcola la somma totale dei finanziamenti per il progetto
    SELECT SUM(importo) INTO totale FROM FINANZIAMENTO WHERE nome_progetto = NEW.nome_progetto;
    
    -- Ottiene il budget del progetto
    SELECT budget INTO budget FROM PROGETTO WHERE nome = NEW.nome_progetto;
    
    -- Se i finanziamenti superano il budget, chiude il progetto
    IF totale >= budget THEN
        UPDATE PROGETTO SET stato = 'chiuso' WHERE nome = NEW.nome_progetto;
    END IF;
END //
DELIMITER ;

-- Verifico il vincolo della data limite: 
/*
Quando il progetto resta aperto oltre la data limite cambia il suo stato in chiuso.  
*/
DELIMITER //
CREATE EVENT chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    UPDATE PROGETTO
    SET stato = 'chiuso'
    WHERE stato = 'aperto' AND data_limite < CURDATE();
END //
DELIMITER ;


/* Vincoli sull'implementazione */


-- TRIGGER 3: Aggiorna affidabilità di un creatore quando riceve un finanziamento
DELIMITER //
CREATE TRIGGER aggiorna_affidabilita_finanziamento
AFTER INSERT ON FINANZIAMENTO
FOR EACH ROW
BEGIN
    DECLARE progetti_finanziati INT;
    DECLARE totale_progetti INT;
    
    -- Numero di progetti dell'utente creatore finanziati almeno una volta
    SELECT COUNT(DISTINCT nome_progetto) INTO progetti_finanziati
    FROM FINANZIAMENTO
    WHERE nome_progetto IN (
        SELECT nome FROM PROGETTO WHERE email_creatore = (SELECT email_creatore FROM PROGETTO WHERE nome = NEW.nome_progetto)
    );
    
    -- Numero totale di progetti creati dall'utente
    SELECT COUNT(*) INTO totale_progetti FROM PROGETTO WHERE email_creatore = (SELECT email_creatore FROM PROGETTO WHERE nome = NEW.nome_progetto);
    
    -- Aggiorna affidabilità
    IF totale_progetti > 0 THEN
        UPDATE UTENTE_CREATORE
        SET affidabilita = (progetti_finanziati / totale_progetti) * 100
        WHERE email_utente = (SELECT email_creatore FROM PROGETTO WHERE nome = NEW.nome_progetto);
    END IF;
END //
DELIMITER ;

-- TRIGGER 4: Incrementa il numero di progetti per un creatore
DELIMITER //
CREATE TRIGGER incrementa_nr_progetti
AFTER INSERT ON PROGETTO
FOR EACH ROW
BEGIN
    UPDATE UTENTE_CREATORE
    SET nr_progetti = nr_progetti + 1
    WHERE email_utente = NEW.email_creatore;
END //
DELIMITER ;



/* DA CHIARIRE LA PARTE SULL USO DI PROCEDURE  */
-- Popolamento dati per test
INSERT INTO UTENTE (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita) VALUES
('test1@example.com', 'testuser1', 'pass123', 'Mario', 'Rossi', 1990, 'Roma'),
('test2@example.com', 'testuser2', 'pass123', 'Luca', 'Bianchi', 1985, 'Milano');

INSERT INTO UTENTE_CREATORE (email_utente, nr_progetti, affidabilita) VALUES
('test1@example.com', 0, 0);

INSERT INTO PROGETTO (nome, descrizione, data_inserimento, email_creatore, budget, data_limite, stato) VALUES
('Progetto1', 'Descrizione del progetto 1', CURDATE(), 'test1@example.com', 5000, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'aperto');

INSERT INTO FINANZIAMENTO (data_finanziamento, importo, email_utente, nome_progetto, codice_reward) VALUES
(CURDATE(), 2000, 'test2@example.com', 'Progetto1', 1);







