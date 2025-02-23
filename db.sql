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
	email_utente VARCHAR(255) PRIMARY KEY,
    codice_sicurezza VARCHAR(50) NOT NULL,
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE

) ENGINE "INNODB";

CREATE TABLE UTENTE_CREATORE(
	email_utente VARCHAR(255) PRIMARY KEY,
    nr_progetti INT DEFAULT 0,
    affidabilita FLOAT, /* Valutare se enum "Buono", ecc */
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE
) ENGINE "INNODB";


CREATE TABLE SKILL_CURRICULUM(
    competenza VARCHAR(100),
    livello INT CHECK (livello BETWEEN 0 AND 5),
    email_utente VARCHAR(255),
    PRIMARY KEY (competenza, livello),
    FOREIGN KEY (email_utente) REFERENCES UTENTE(email) ON DELETE CASCADE
    /* VERIFICARE COLLEGAMENTO CON AMMINISTRATORE*/
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
    FOREIGN KEY (email_creatore) REFERENCES UTENTE_CREATORE(email_utente) ON DELETE CASCADE
    /* Valutare se fare chiave primaria composta con id commento e email creatore */ 
);

CREATE TABLE PROGETTO (
    nome VARCHAR(255) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    data_inserimento DATE NOT NULL,
    email_creatore VARCHAR(255) NOT NULL,
    budget FLOAT NOT NULL CHECK (budget > 0),
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') NOT NULL DEFAULT 'aperto',
    FOREIGN KEY (email_creatore) REFERENCES UTENTE_CREATORE(email_utente) ON DELETE CASCADE
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
    accettata BOOLEAN DEFAULT FALSE,
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









