DROP
DATABASE IF EXISTS BOSTARTER;
CREATE
DATABASE IF NOT EXISTS BOSTARTER;
USE
BOSTARTER;


-- Creazione Tabelle
/***********INIZIO***********/


CREATE TABLE UTENTE
(
    email         VARCHAR(255) PRIMARY KEY,
    nickname      VARCHAR(50) UNIQUE NOT NULL,
    password      VARCHAR(255)       NOT NULL,
    nome          VARCHAR(50)        NOT NULL,
    cognome       VARCHAR(50)        NOT NULL,
    anno_nascita YEAR NOT NULL,
    luogo_nascita VARCHAR(100)       NOT NULL
) ENGINE="INNODB";


CREATE TABLE UTENTE_AMMINISTRATORE
(
    email_utente_amm VARCHAR(255) PRIMARY KEY,
    codice_sicurezza VARCHAR(255) NOT NULL,
    FOREIGN KEY (email_utente_amm) REFERENCES UTENTE (email) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE UTENTE_CREATORE
(
    email_utente_creat VARCHAR(255) PRIMARY KEY,
    nr_progetti        INT DEFAULT 0,
    affidabilita       FLOAT CHECK (affidabilita BETWEEN 0 AND 10), /* Valutare se enum "Buono", ecc */
    FOREIGN KEY (email_utente_creat) REFERENCES UTENTE (email) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE SKILL
(
    competenza       VARCHAR(100),
    email_utente_amm VARCHAR(255),
    PRIMARY KEY (competenza),
    FOREIGN KEY (email_utente_amm) REFERENCES UTENTE_AMMINISTRATORE (email_utente_amm) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE INDICA
(
    competenza   VARCHAR(100),
    livello      INT CHECK (livello BETWEEN 0 AND 5),
    email_utente VARCHAR(255),
    PRIMARY KEY (competenza, email_utente),
    FOREIGN KEY (email_utente) REFERENCES UTENTE (email) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE PROGETTO
(
    nome             VARCHAR(255) PRIMARY KEY,
    descrizione      TEXT         NOT NULL,
    data_inserimento DATE         NOT NULL,
    email_creatore   VARCHAR(255) NOT NULL,
    budget           FLOAT        NOT NULL CHECK (budget > 0),
    data_limite      DATE         NOT NULL,
    stato            ENUM('aperto', 'chiuso') NOT NULL DEFAULT 'aperto',
    FOREIGN KEY (email_creatore) REFERENCES UTENTE_CREATORE (email_utente_creat) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE COMMENTO
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email_utente  VARCHAR(255) NOT NULL,
    nome_progetto VARCHAR(255) NOT NULL,
    data_commento DATE         NOT NULL,
    testo         TEXT         NOT NULL,
    FOREIGN KEY (email_utente) REFERENCES UTENTE (email) ON DELETE CASCADE,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO (nome) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE RISPOSTA_COMMENTO
(
    id_commento    INT,
    email_creatore VARCHAR(255),
    data_risposta  DATE NOT NULL,
    testo          TEXT NOT NULL,
    PRIMARY KEY (id_commento, email_creatore),
    FOREIGN KEY (id_commento) REFERENCES COMMENTO (id) ON DELETE CASCADE,
    FOREIGN KEY (email_creatore) REFERENCES UTENTE_CREATORE (email_utente_creat) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE FOTO_PROGETTO
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome_progetto VARCHAR(255) NOT NULL,
    foto_url      TEXT         NOT NULL,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO (nome) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE REWARD
(
    codice        INT AUTO_INCREMENT PRIMARY KEY,
    nome_progetto VARCHAR(255) NOT NULL,
    descrizione   TEXT         NOT NULL,
    foto_url      TEXT         NOT NULL,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO (nome) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE PROGETTO_HARDWARE
(
    nome_progetto VARCHAR(255) PRIMARY KEY,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO (nome) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE COMPONENTE
(
    nome        VARCHAR(255) PRIMARY KEY,
    descrizione TEXT  NOT NULL,
    prezzo      FLOAT NOT NULL CHECK (prezzo > 0),
    quantita    INT   NOT NULL CHECK (quantita > 0)
) ENGINE="INNODB";

CREATE TABLE FORMATO
(
    nome_componente VARCHAR(255),
    nome_hardware   VARCHAR(255),
    PRIMARY KEY (nome_componente, nome_hardware),
    FOREIGN KEY (nome_hardware) REFERENCES PROGETTO_HARDWARE (nome_progetto) ON DELETE CASCADE,
    FOREIGN KEY (nome_componente) REFERENCES COMPONENTE (nome) ON DELETE CASCADE
) ENGINE="INNODB";

CREATE TABLE PROGETTO_SOFTWARE
(
    nome_progetto VARCHAR(255) PRIMARY KEY,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO (nome) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE PROFILO
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(255),
    nome_software VARCHAR(255),
    FOREIGN KEY (nome_software) REFERENCES PROGETTO_SOFTWARE (nome_progetto) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE COMPRENDE
(
    competenza VARCHAR(100),
    livello    INT CHECK (livello BETWEEN 0 AND 5),
    id_profilo INT,
    PRIMARY KEY (competenza, id_profilo),
    FOREIGN KEY (id_profilo) REFERENCES PROFILO (id) ON DELETE CASCADE
);


CREATE TABLE FINANZIAMENTO
(
    id                 INT AUTO_INCREMENT,
    data_finanziamento DATE,
    importo            FLOAT        NOT NULL CHECK (importo > 0),
    email_utente       VARCHAR(255) NOT NULL,
    nome_progetto      VARCHAR(255) NOT NULL,
    codice_reward      INT          NOT NULL,
    PRIMARY KEY (id, data_finanziamento),
    FOREIGN KEY (email_utente) REFERENCES UTENTE (email) ON DELETE CASCADE,
    FOREIGN KEY (nome_progetto) REFERENCES PROGETTO (nome) ON DELETE CASCADE,
    FOREIGN KEY (codice_reward) REFERENCES REWARD (codice) ON DELETE CASCADE
) ENGINE="INNODB";


CREATE TABLE CANDIDATURA
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    email_utente VARCHAR(255) NOT NULL,
    id_profilo   INT          NOT NULL,
    esito        BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (email_utente) REFERENCES UTENTE (email) ON DELETE CASCADE,
    FOREIGN KEY (id_profilo) REFERENCES PROFILO (id) ON DELETE CASCADE
) ENGINE="INNODB";


/***********FINE***********/


-- PROCEDURE PER INSERIMENTO LOG EVENTI
/***********INIZIO***********/

CREATE TABLE IF NOT EXISTS LOG_EVENTI (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento VARCHAR(255) NOT NULL,
    email_utente VARCHAR(255) NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    descrizione TEXT NOT NULL,
    sincronizzato BOOLEAN DEFAULT FALSE
    ) ENGINE=INNODB;


DELIMITER //
CREATE PROCEDURE InserisciLogEvento(
    IN p_evento VARCHAR(255),
    IN p_email_utente VARCHAR(255),
    IN p_descrizione TEXT
)
BEGIN

    -- Inserimento del log
INSERT INTO LOG_EVENTI (evento, email_utente, descrizione)
VALUES (p_evento, p_email_utente, p_descrizione);
END //
DELIMITER ;

/***********FINE***********/


-- BUSINESS RULES E VINCOLI SULL'IMPLEMENTAZIONE
/***********INIZIO***********/

/* VISTA
   Validazione delle candidature:
   La candidatura è valida se, per ogni skill richiesta in un profilo (tabella COMPRENDE),
   l'utente (tabella INDICA) possiede la stessa competenza con un livello maggiore o uguale. */
CREATE VIEW Candidature_Valide AS
SELECT C.email_utente, C.id_profilo, CMP.competenza FROM CANDIDATURA C
                                                             JOIN INDICA I ON C.email_utente = I.email_utente
                                                             JOIN COMPRENDE CMP ON C.id_profilo = CMP.id_profilo
WHERE I.competenza = CMP.competenza AND I.livello >= CMP.livello;


/* TRIGGER 1:
   Verifica che la somma dei finanziamenti non superi il budget del progetto */
DELIMITER
//
CREATE TRIGGER verifica_budget_superato
    AFTER INSERT
    ON FINANZIAMENTO
    FOR EACH ROW
BEGIN
    DECLARE totale FLOAT;
    DECLARE budget FLOAT;

    -- Calcolo la somma totale dei finanziamenti per il progetto
    SELECT SUM(importo)
    INTO totale
    FROM FINANZIAMENTO
    WHERE nome_progetto = NEW.nome_progetto;

    -- Ottengo il budget del progetto
    SELECT budget
    INTO budget
    FROM PROGETTO
    WHERE nome = NEW.nome_progetto;

    -- Se i finanziamenti raggiungono o superano il budget, chiudo il progetto
    IF totale >= budget THEN
    UPDATE PROGETTO
    SET stato = 'chiuso'
    WHERE nome = NEW.nome_progetto;
END IF;
END
//
DELIMITER ;


/* EVENTIO:
    Chiude i progetti aperti superata la data limite */
DELIMITER
//
CREATE
EVENT chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
UPDATE PROGETTO
SET stato = 'chiuso'
WHERE stato = 'aperto'
  AND data_limite < CURDATE();
END
//
DELIMITER ;


/* TRIGGER 2:
   Aggiorna l’affidabilità di un creatore alla ricezione di un finanziamento.
   Nota: Per rispettare il vincolo (0-10), si calcola (progetti_finanziati/ totale_progetti)*10 */
DELIMITER
//
CREATE TRIGGER aggiorna_affidabilita_finanziamento
    AFTER INSERT
    ON FINANZIAMENTO
    FOR EACH ROW
BEGIN
    DECLARE progetti_finanziati INT;
    DECLARE totale_progetti INT;
    DECLARE creatore_email VARCHAR(255);

    SELECT email_creatore
    INTO creatore_email
    FROM PROGETTO
    WHERE nome = NEW.nome_progetto;

    SELECT COUNT(DISTINCT nome_progetto)
    INTO progetti_finanziati
    FROM FINANZIAMENTO
    WHERE nome_progetto IN (SELECT nome
                            FROM PROGETTO
                            WHERE email_creatore = creatore_email);

    SELECT COUNT(*)
    INTO totale_progetti
    FROM PROGETTO
    WHERE email_creatore = creatore_email;

    IF totale_progetti > 0 THEN
    UPDATE UTENTE_CREATORE
    SET affidabilita = ROUND((progetti_finanziati / totale_progetti) * 10, 2)
    WHERE email_utente_creat = creatore_email;
END IF;
END;
//
DELIMITER ;


/* TRIGGER 3:
   Aggiorna l’affidabilità di un creatore alla creazione di un progetto.
   Nota: Per rispettare il vincolo (0-10), si calcola (progetti_finanziati/ totale_progetti)*10 */
DELIMITER
//
CREATE TRIGGER aggiorna_affidabilita_progetto
    AFTER INSERT
    ON PROGETTO
    FOR EACH ROW
BEGIN
    DECLARE progetti_finanziati INT;
    DECLARE totale_progetti INT;

    SELECT COUNT(DISTINCT nome_progetto)
    INTO progetti_finanziati
    FROM FINANZIAMENTO
    WHERE nome_progetto IN (SELECT nome
                            FROM PROGETTO
                            WHERE email_creatore = NEW.email_creatore);

    SELECT COUNT(*)
    INTO totale_progetti
    FROM PROGETTO
    WHERE email_creatore = NEW.email_creatore;

    IF totale_progetti > 0 THEN
    UPDATE UTENTE_CREATORE
    SET affidabilita = ROUND((progetti_finanziati / totale_progetti) * 10, 2)
    WHERE email_utente_creat = NEW.email_creatore;
END IF;
END;
//
DELIMITER ;

/* Trigger 4:
    Incrementa il numero di progetti per un creatore alla creazione di un nuovo progetto */
DELIMITER
//
CREATE TRIGGER incrementa_nr_progetti
    AFTER INSERT
    ON PROGETTO
    FOR EACH ROW
BEGIN
    UPDATE UTENTE_CREATORE
    SET nr_progetti = nr_progetti + 1
    WHERE email_utente_creat = NEW.email_creatore;
END //
DELIMITER;

/***********FINE***********/





-- LOGGING - OPERAZIONI SUI DATI:

-- OPERAZIONI CHE RIGUARDANO SOLO GLI UTENTI
/***********INIZIO***********/

/* Log registrazione nuovo utente */
DROP TRIGGER IF EXISTS log_nuovo_utente;

CREATE TRIGGER log_nuovo_utente
    AFTER INSERT ON UTENTE
    FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Nuovo Utente', NEW.email, CONCAT('Utente ', NEW.nickname, ' si è registrato.'));
END;

/* Log inserimento skill di curriculum (tabella INDICA) */
DELIMITER //
CREATE TRIGGER log_inserimento_skill
AFTER INSERT ON INDICA
FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Inserimento Skill', NEW.email_utente,
        CONCAT('Utente ha aggiunto la skill ', NEW.competenza, ' con livello ', NEW.livello, '.'));
END //
DELIMITER;

/* Procedura per log della visualizzazione dei progetti */
DELIMITER
//
CREATE PROCEDURE Log_Visualizzazione_Progetti(
    IN p_email_utente VARCHAR (255)
)
BEGIN
CALL InserisciLogEvento('Visualizzazione Progetti', p_email_utente,
        'Utente ha visualizzato la lista dei progetti disponibili.');
END //
DELIMITER ;

/* Log finanziamento di un progetto */
DELIMITER //
CREATE TRIGGER log_finanziamento
AFTER INSERT ON FINANZIAMENTO
FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Nuovo Finanziamento', NEW.email_utente,
        CONCAT('Utente ha finanziato il progetto ', NEW.nome_progetto, ' con ', NEW.importo, ' euro.'));
END
//
DELIMITER ;

/* Log scelta della reward per un progetto */
DELIMITER
//
CREATE TRIGGER log_scelta_reward
    AFTER INSERT
    ON FINANZIAMENTO
    FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Scelta Reward', NEW.email_utente,
        CONCAT('Utente ha scelto la reward con codice ', NEW.codice_reward, ' per il progetto ', NEW.nome_progetto, '.'));
END //
DELIMITER ;

/* Log inserimento di un commento su un progetto */
DELIMITER //
CREATE TRIGGER log_nuovo_commento
AFTER INSERT ON COMMENTO
FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Nuovo Commento', NEW.email_utente,
        CONCAT('Utente ha commentato il progetto ', NEW.nome_progetto, '.'));
END //
DELIMITER;

/* Log inserimento candidatura per un profilo */
DELIMITER //
CREATE TRIGGER log_nuova_candidatura
    AFTER INSERT
    ON CANDIDATURA
    FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Nuova Candidatura', NEW.email_utente,
        CONCAT('Utente si è candidato per il profilo ', NEW.id_profilo, '.'));
END //
DELIMITER ;



-- OPERAZIONI CHE RIGUARDANO GLI AMMINISTRATORI

/* Log inserimento di una nuova stringa nella lista delle competenze (tabella SKILL) */
DELIMITER //
CREATE TRIGGER log_nuova_skill
AFTER INSERT ON SKILL
FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Nuova Skill', NEW.email_utente_amm,
        CONCAT('L amministratore ha aggiunto la competenza ', NEW.competenza, '.'));
END //
DELIMITER;

/* Procedura per log di autenticazione amministratore */
DELIMITER
//
CREATE PROCEDURE Log_Autenticazione_Amministratore(
    IN p_email_utente VARCHAR (255)
)
BEGIN
CALL InserisciLogEvento('Autenticazione Amministratore', p_email_utente,
        'Amministratore ha effettuato l accesso con codice di sicurezza.');
END
//
DELIMITER ;


-- OPERAZIONI CHE RIGUARDANO GLI UTENTI CREATORI

/* Log creazione di un nuovo progetto */
DELIMITER
//
CREATE TRIGGER log_nuovo_progetto
    AFTER INSERT
    ON PROGETTO
    FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Nuovo Progetto', NEW.email_creatore,
        CONCAT('Utente ha creato il progetto ', NEW.nome, '.'));
END //
DELIMITER ;

/* Log inserimento di una reward per un progetto */
DELIMITER //
CREATE TRIGGER log_nuova_reward
AFTER INSERT ON REWARD
FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Nuova Reward',
        (SELECT email_creatore FROM PROGETTO WHERE nome = NEW.nome_progetto),
        CONCAT('Utente ha aggiunto una reward per il progetto ', NEW.nome_progetto, '.'));
END //
DELIMITER;

/* Log inserimento di una risposta ad un commento */
DELIMITER
//
CREATE TRIGGER log_risposta_commento
    AFTER INSERT
    ON RISPOSTA_COMMENTO
    FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Risposta a Commento', NEW.email_creatore,
        CONCAT('Utente ha risposto a un commento con ID ', NEW.id_commento, '.'));
END //
DELIMITER ;

/* Log creazione di un profilo per un progetto software */
DELIMITER //
CREATE TRIGGER log_nuovo_profilo
AFTER INSERT ON PROFILO
FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Nuovo Profilo',
        (SELECT email_creatore FROM PROGETTO WHERE nome = NEW.nome_software),
        CONCAT('Utente ha creato il profilo ', NEW.nome, ' per il progetto software ', NEW.nome_software, '.'));
END //
DELIMITER;

/* Log accettazione o rifiuto di una candidatura */
DELIMITER
//
CREATE TRIGGER log_accettazione_candidatura
    AFTER UPDATE
    ON CANDIDATURA
    FOR EACH ROW
BEGIN
    CALL InserisciLogEvento('Gestione Candidatura',
        (SELECT email_creatore FROM PROGETTO WHERE nome =
            (SELECT nome_software FROM PROFILO WHERE id = NEW.id_profilo)),
        CONCAT('Utente ha ', IF(NEW.esito, 'accettato', 'rifiutato'),
               ' la candidatura di ', NEW.email_utente, ' per il profilo ', NEW.id_profilo, '.'));
END //
DELIMITER ;


/***********FINE***********/


-- STATISTICHE (VISIBILI DA TUTTI GLI UTENTI)
/***********INIZIO***********/

/* Classifica degli utenti creatori per affidabilità */
CREATE VIEW ClassificaCreatori AS
SELECT U.nickname, UC.affidabilita
FROM UTENTE_CREATORE UC
JOIN UTENTE U ON UC.email_utente_creat = U.email
ORDER BY UC.affidabilita DESC
LIMIT 3;

/* Progetti aperti più vicini al completamento */
CREATE VIEW ProgettiViciniAlCompletamento AS
SELECT P.nome, P.budget,
       (P.budget - COALESCE(SUM(F.importo), 0)) AS mancante
FROM PROGETTO P
LEFT JOIN FINANZIAMENTO F ON P.nome = F.nome_progetto
WHERE P.stato = 'aperto'
GROUP BY P.nome, P.budget
ORDER BY mancante ASC
LIMIT 3;

/* Classifica degli utenti in base al totale dei finanziamenti erogati */
CREATE VIEW ClassificaFinanziatori AS
SELECT U.nickname, SUM(F.importo) AS totale_finanziato
FROM FINANZIAMENTO F
JOIN UTENTE U ON F.email_utente = U.email
GROUP BY U.nickname
ORDER BY totale_finanziato DESC
LIMIT 3;

/***********FINE***********/



-- CREAZIONE STORED PROCEDURE PER INSERIMENTO DEMO

DELIMITER //

CREATE PROCEDURE sp_inserisci_utente(
    IN p_email VARCHAR(255), IN p_nickname VARCHAR(50), IN p_password VARCHAR(255),
    IN p_nome VARCHAR(50), IN p_cognome VARCHAR(50), IN p_anno YEAR, IN p_luogo VARCHAR(100)
)
BEGIN
INSERT INTO UTENTE(email,nickname,password,nome,cognome,anno_nascita,luogo_nascita)
VALUES(p_email,p_nickname,p_password,p_nome,p_cognome,p_anno,p_luogo);
END;//

CREATE PROCEDURE sp_inserisci_amministratore(
    IN p_email VARCHAR(255), IN p_codice VARCHAR(255)
)
BEGIN
INSERT INTO UTENTE_AMMINISTRATORE(email_utente_amm,codice_sicurezza)
VALUES(p_email,p_codice);
END;//

CREATE PROCEDURE sp_inserisci_creatore(
    IN p_email VARCHAR(255), IN p_nr_progetti INT, IN p_affid FLOAT
)
BEGIN
INSERT INTO UTENTE_CREATORE(email_utente_creat,nr_progetti,affidabilita)
VALUES(p_email,p_nr_progetti,p_affid);
END;//

CREATE PROCEDURE sp_aggiungi_skill(
    IN p_competenza VARCHAR(100), IN p_email_amm VARCHAR(255)
)
BEGIN
INSERT INTO SKILL(competenza,email_utente_amm)
VALUES(p_competenza,p_email_amm);
END;//

CREATE PROCEDURE sp_indica_competenza(
    IN p_competenza VARCHAR(100), IN p_livello INT, IN p_email VARCHAR(255)
)
BEGIN
INSERT INTO INDICA(competenza,livello,email_utente)
VALUES(p_competenza,p_livello,p_email);
END;//

CREATE PROCEDURE sp_crea_progetto(
    IN p_nome VARCHAR(255), IN p_descr TEXT, IN p_data_ins DATE,
    IN p_email_creat VARCHAR(255), IN p_budget FLOAT, IN p_data_lim DATE
)
BEGIN
INSERT INTO PROGETTO(nome,descrizione,data_inserimento,email_creatore,budget,data_limite)
VALUES(p_nome,p_descr,p_data_ins,p_email_creat,p_budget,p_data_lim);
END;//

CREATE PROCEDURE sp_aggiungi_commento(
    IN p_email VARCHAR(255), IN p_nome_proj VARCHAR(255), IN p_data_comm DATE, IN p_testo TEXT
)
BEGIN
INSERT INTO COMMENTO(email_utente,nome_progetto,data_commento,testo)
VALUES(p_email,p_nome_proj,p_data_comm,p_testo);
END;//

CREATE PROCEDURE sp_rispondi_commento(
    IN p_id_comm INT, IN p_email_creat VARCHAR(255), IN p_data_resp DATE, IN p_testo TEXT
)
BEGIN
INSERT INTO RISPOSTA_COMMENTO(id_commento,email_creatore,data_risposta,testo)
VALUES(p_id_comm,p_email_creat,p_data_resp,p_testo);
END;//

CREATE PROCEDURE sp_aggiungi_foto(
    IN p_nome_proj VARCHAR(255), IN p_url TEXT
)
BEGIN
INSERT INTO FOTO_PROGETTO(nome_progetto,foto_url)
VALUES(p_nome_proj,p_url);
END;//

CREATE PROCEDURE sp_aggiungi_reward(
    IN p_nome_proj VARCHAR(255), IN p_descr TEXT, IN p_url TEXT
)
BEGIN
INSERT INTO REWARD(nome_progetto,descrizione,foto_url)
VALUES(p_nome_proj,p_descr,p_url);
END;//

CREATE PROCEDURE sp_aggiungi_componente(
    IN p_nome VARCHAR(255), IN p_descr TEXT, IN p_prezzo FLOAT, IN p_qta INT
)
BEGIN
INSERT INTO COMPONENTE(nome,descrizione,prezzo,quantita)
VALUES(p_nome,p_descr,p_prezzo,p_qta);
END;//

CREATE PROCEDURE sp_link_formato(
    IN p_comp VARCHAR(255), IN p_hardware VARCHAR(255)
)
BEGIN
INSERT INTO FORMATO(nome_componente,nome_hardware)
VALUES(p_comp,p_hardware);
END;//

CREATE PROCEDURE sp_crea_progetto_software(
    IN p_nome_software VARCHAR(255)
)
BEGIN
INSERT INTO PROGETTO_SOFTWARE(nome_progetto)
VALUES(p_nome_software);
END;//

CREATE PROCEDURE sp_crea_profilo(
    IN p_nome VARCHAR(255), IN p_proj_software VARCHAR(255)
)
BEGIN
INSERT INTO PROFILO(nome,nome_software)
VALUES(p_nome,p_proj_software);
END;//

CREATE PROCEDURE sp_aggiungi_comprende(
    IN p_comp VARCHAR(100), IN p_liv INT, IN p_id_prof INT
)
BEGIN
INSERT INTO COMPRENDE(competenza,livello,id_profilo)
VALUES(p_comp,p_liv,p_id_prof);
END;//

CREATE PROCEDURE sp_finanzia(
    IN p_data DATE, IN p_importo FLOAT, IN p_email VARCHAR(255),
    IN p_proj VARCHAR(255), IN p_codice_reward INT
)
BEGIN
INSERT INTO FINANZIAMENTO(data_finanziamento,importo,email_utente,nome_progetto,codice_reward)
VALUES(p_data,p_importo,p_email,p_proj,p_codice_reward);
END;//

CREATE PROCEDURE sp_crea_candidatura(
    IN p_email VARCHAR(255), IN p_id_prof INT
)
BEGIN
INSERT INTO CANDIDATURA(email_utente,id_profilo)
VALUES(p_email,p_id_prof);
END;//

DELIMITER ;

DELIMITER //
CREATE PROCEDURE sp_crea_progetto_hardware(
    IN p_nome_progetto VARCHAR(255)
)
BEGIN
INSERT INTO PROGETTO_HARDWARE(nome_progetto)
VALUES (p_nome_progetto);
END;
//
DELIMITER ;


-- CHIAMATA ALLA STORE PROEDURE PER INSERIMENTO DEMO

-- Utenti
CALL sp_inserisci_utente('test1@example.com','testuser1','$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2','Mario','Rossi',1990,'Roma');
CALL sp_inserisci_utente('test2@example.com','testuser2','$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2','Luca','Bianchi',1985,'Milano');
CALL sp_inserisci_utente('fedesgambe@icloud.com','fedesgambe','$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2','Federico','Sgambelluri',2003,'Bologna');
CALL sp_inserisci_utente('peppe24@gmail.com','peppeeee','$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2','Giuseppe','Cozza',2003,'Bologna');
CALL sp_inserisci_utente('simonemagli@gmail.com','sama','$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2', 'Simone','Magli',2003,'Bologna');
CALL sp_inserisci_utente('mirko@gmail.com','Mirko','$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2','Mirko','Rossi',1980,'Roma');
CALL sp_inserisci_utente('test3@example.com','testuser3','$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2','Giulia','Verdi',1992,'Napoli');

-- Creatori
CALL sp_inserisci_creatore('test1@example.com',1,0);
CALL sp_inserisci_creatore('test2@example.com',1,10);
CALL sp_inserisci_creatore('fedesgambe@icloud.com',3,0.33);
CALL sp_inserisci_creatore('simonemagli@gmail.com',1,0);
CALL sp_inserisci_creatore('test3@example.com',1,0);

-- Amministratore
CALL sp_inserisci_amministratore('peppe24@gmail.com','$2y$10$aFf/xsZRwAke8XG47X1HUOYTHH4hIdoHe0pTKEXlSuQw4Q9szA0Yu');

-- Progetti
CALL sp_crea_progetto('Smart Home AI','Un sistema di intelligenza artificiale per la gestione delle case smart.',CURDATE(),'test1@example.com',15000,DATE_ADD(CURDATE(),INTERVAL 30 DAY));
CALL sp_crea_progetto('EcoCar','Un’auto elettrica ecologica con materiali sostenibili.',CURDATE(),'test2@example.com',25000,DATE_ADD(CURDATE(),INTERVAL 45 DAY));
CALL sp_crea_progetto('VR Learning','Una piattaforma di apprendimento in realtà virtuale per scuole e università.',CURDATE(),'test3@example.com',20000,DATE_ADD(CURDATE(),INTERVAL 60 DAY));
CALL sp_crea_progetto('Mucca Silver','Una mucca di carta stagnola',CURDATE(),'fedesgambe@icloud.com',15000,DATE_ADD(CURDATE(),INTERVAL -30 DAY));
CALL sp_crea_progetto('Smart Park','Un parcheggio digitale per trovare sempre posto',CURDATE(),'simonemagli@gmail.com',25000,DATE_ADD(CURDATE(),INTERVAL 45 DAY));
CALL sp_crea_progetto('Flipper City','Gioca a flipper, ma la mappa è la tua città',CURDATE(),'fedesgambe@icloud.com',20000,DATE_ADD(CURDATE(),INTERVAL 60 DAY));
CALL sp_crea_progetto('Emilio 5.0','Il famosissimo Emilio, ma per adulti',CURDATE(),'fedesgambe@icloud.com',20000,DATE_ADD(CURDATE(),INTERVAL 60 DAY));

-- Foto Progetto
CALL sp_aggiungi_foto('EcoCar','images/progetto1.jpg');
CALL sp_aggiungi_foto('Smart Home AI','images/progetto2.jpg');
CALL sp_aggiungi_foto('VR Learning','images/progetto3.jpg');
CALL sp_aggiungi_foto('Mucca Silver','images/mucca.png');
CALL sp_aggiungi_foto('Smart Park','images/smartpark.png');
CALL sp_aggiungi_foto('Flipper City','images/flipper.png');
CALL sp_aggiungi_foto('Emilio 5.0','images/emilio.png');

-- Reward
CALL sp_aggiungi_reward('EcoCar','Sticker esclusivo del progetto','images/car.png');
CALL sp_aggiungi_reward('EcoCar','T-shirt personalizzata del progetto','images/reward2.png');
CALL sp_aggiungi_reward('EcoCar','Accesso anticipato ai contenuti del progetto','images/reward3.png');
CALL sp_aggiungi_reward('Smart Home AI','Stampa digitale ad alta risoluzione','images/reward4.png');
CALL sp_aggiungi_reward('Smart Home AI','NFT esclusivo del progetto','images/reward5.png');
CALL sp_aggiungi_reward('Smart Home AI','Meet & Greet con l''artista','images/reward6.png');
CALL sp_aggiungi_reward('VR Learning','Guida e-book su sostenibilità','images/reward7.png');
CALL sp_aggiungi_reward('VR Learning','Sconto su prodotti green','images/reward8.png');
CALL sp_aggiungi_reward('VR Learning','Nome inciso sul prodotto finale','images/reward9.png');
CALL sp_aggiungi_reward('Mucca Silver','Sticker in edizione limitata a tema mucca','images/reward10.png');
CALL sp_aggiungi_reward('Mucca Silver','Miniatura in carta stagnola firmata dal team','images/reward11.png');
CALL sp_aggiungi_reward('Mucca Silver','Accesso esclusivo al backstage di creazione','images/reward12.png');
CALL sp_aggiungi_reward('Smart Park','Priorità di parcheggio in strutture convenzionate','images/reward13.png');
CALL sp_aggiungi_reward('Smart Park','T-shirt ufficiale del progetto con logo','images/reward14.png');
CALL sp_aggiungi_reward('Smart Park','App premium con funzioni avanzate di parcheggio','images/reward15.png');
CALL sp_aggiungi_reward('Flipper City','Spilla da collezione con design del flipper','images/reward16.png');
CALL sp_aggiungi_reward('Flipper City','Tavola da gioco personalizzata con la mappa della città','images/monopoli.png');
CALL sp_aggiungi_reward('Flipper City','Accesso anticipato alla versione beta del gioco','images/reward18.png');
CALL sp_aggiungi_reward('Emilio 5.0','Poster firmato dal creatore','images/reward19.png');
CALL sp_aggiungi_reward('Emilio 5.0','Contenuti extra e dietro le quinte','images/reward20.png');
CALL sp_aggiungi_reward('Emilio 5.0','Incontro virtuale con il team di sviluppo','images/reward21.png');

-- Progetto Hardware & Componenti & Formato
CALL sp_aggiungi_componente('Scheda madre','Scheda madre per sistema di automazione domestica',150.00,10);
CALL sp_aggiungi_componente('Batteria','Batteria per auto elettrica, lunga durata',120.00,15);
CALL sp_aggiungi_componente('Sensore','Sensore di movimento per sistema di sorveglianza',25.00,50);
CALL sp_aggiungi_componente('Fotocamera','Fotocamera di sicurezza con visione notturna',80.00,30);
CALL sp_aggiungi_componente('Microcontrollore','Microcontrollore per automazione industriale',50.00,20);
CALL sp_aggiungi_componente('Motore servo','Motore passo-passo per movimentazione automatica',45.00,25);
CALL sp_aggiungi_componente('Motore passo-passo','Motore utilizzato per precisione nei movimenti automatizzati',40.00,10);
CALL sp_aggiungi_componente('Modulo Wi-Fi','Modulo per connessione wireless a internet',30.00,50);
CALL sp_aggiungi_componente('Display LCD','Display a cristalli liquidi per visualizzazione dati',15.00,30);
CALL sp_aggiungi_componente('Sensore di temperatura','Sensore per rilevazione temperatura ambiente',10.00,100);
CALL sp_aggiungi_componente('Modulo Bluetooth','Modulo per connessione Bluetooth',20.00,40);
CALL sp_aggiungi_componente('Cavo di alimentazione','Cavo per alimentazione componenti elettronici',5.00,200);

-- Associazione Hardware
CALL sp_crea_progetto_hardware('Smart Home AI');
CALL sp_crea_progetto_hardware('EcoCar');
CALL sp_crea_progetto_hardware('Smart Park');
CALL sp_crea_progetto_hardware('Mucca Silver');
CALL sp_crea_progetto_hardware('Emilio 5.0');

-- Associaizoni componenti
CALL sp_link_formato('Scheda madre','Smart Home AI');
CALL sp_link_formato('Batteria','Smart Home AI');
CALL sp_link_formato('Sensore','Smart Home AI');
CALL sp_link_formato('Fotocamera','Smart Home AI');
CALL sp_link_formato('Batteria','EcoCar');
CALL sp_link_formato('Motore servo','EcoCar');
CALL sp_link_formato('Sensore','EcoCar');

-- Smart Park
CALL sp_link_formato('Sensore','Smart Park');
CALL sp_link_formato('Fotocamera','Smart Park');
CALL sp_link_formato('Display LCD','Smart Park');
CALL sp_link_formato('Modulo Wi-Fi','Smart Park');

-- Mucca Silver
CALL sp_link_formato('Scheda madre','Mucca Silver');
CALL sp_link_formato('Motore servo','Mucca Silver');
CALL sp_link_formato('Motore passo-passo','Mucca Silver');
CALL sp_link_formato('Display LCD','Mucca Silver');

-- Emilio 5.0
CALL sp_link_formato('Microcontrollore','Emilio 5.0');
CALL sp_link_formato('Motore servo','Emilio 5.0');
CALL sp_link_formato('Modulo Wi-Fi','Emilio 5.0');
CALL sp_link_formato('Cavo di alimentazione','Emilio 5.0');

-- Progetti Software & Profili
CALL sp_crea_progetto_software('Flipper City');
CALL sp_crea_progetto_software('VR Learning');
CALL sp_crea_profilo('Fisico','Flipper City');
CALL sp_crea_profilo('Ingegnere IT','Flipper City');
CALL sp_crea_profilo('Programmatore','Flipper City');

-- Comprende
CALL sp_aggiungi_comprende('Basi di Dati',2,3);
CALL sp_aggiungi_comprende('Fisica delle sfere',1,1);
CALL sp_aggiungi_comprende('Google Maps',3,2);
CALL sp_aggiungi_comprende('Java',4,2);
CALL sp_aggiungi_comprende('Python',5,3);


-- Competenze
CALL sp_aggiungi_skill('Analisi 1', 'peppe24@gmail.com');
CALL sp_aggiungi_skill('Basi di Dati', 'peppe24@gmail.com');
CALL sp_aggiungi_skill('Chimica dei materiali', 'peppe24@gmail.com');
CALL sp_aggiungi_skill('Elettronica di base', 'peppe24@gmail.com');
CALL sp_aggiungi_skill('Python', 'peppe24@gmail.com');
CALL sp_aggiungi_skill('Google Maps', 'peppe24@gmail.com');
CALL sp_aggiungi_skill('Fisica delle sfere', 'peppe24@gmail.com');

-- Indica
CALL sp_indica_competenza('Basi di Dati',5,'simonemagli@gmail.com');
CALL sp_indica_competenza('Fisica delle sfere',3,'fedesgambe@icloud.com');
CALL sp_indica_competenza('Fisica delle sfere',5,'mirko@gmail.com');
CALL sp_indica_competenza('Google Maps',3,'fedesgambe@icloud.com');
CALL sp_indica_competenza('Google Maps',5,'mirko@gmail.com');
CALL sp_indica_competenza('Java',5,'fedesgambe@icloud.com');
CALL sp_indica_competenza('Python',5,'simonemagli@gmail.com');

-- Finanziamento
CALL sp_finanzia('2025-03-23',78,'fedesgambe@icloud.com','EcoCar',1);
CALL sp_finanzia('2025-03-23',67,'simonemagli@gmail.com','Flipper City',17);

-- Candidatura
-- CALL sp_crea_candidatura('fedesgambe@icloud.com',1);
-- CALL sp_crea_candidatura('fedesgambe@icloud.com',2);
-- CALL sp_crea_candidatura('simonemagli@gmail.com',3);
-- CALL sp_crea_candidatura('mirko@gmail.com',1);
-- CALL sp_crea_candidatura('mirko@gmail.com',2);
