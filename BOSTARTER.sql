-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Creato il: Mar 18, 2025 alle 14:09
-- Versione del server: 8.0.40
-- Versione PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `BOSTARTER`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `CANDIDATURA`
--

CREATE TABLE `CANDIDATURA` (
  `id` int NOT NULL,
  `email_utente` varchar(255) NOT NULL,
  `id_profilo` int NOT NULL,
  `esito` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `CANDIDATURA`
--

INSERT INTO `CANDIDATURA` (`id`, `email_utente`, `id_profilo`, `esito`) VALUES
(4, 'fedesgambe@icloud.com', 1, -1),
(5, 'fedesgambe@icloud.com', 2, 1),
(7, 'simonemagli@gmail.com', 14, 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `COMMENTO`
--

CREATE TABLE `COMMENTO` (
  `id` int NOT NULL,
  `email_utente` varchar(255) NOT NULL,
  `nome_progetto` varchar(255) NOT NULL,
  `data_commento` date NOT NULL,
  `testo` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `COMMENTO`
--

INSERT INTO `COMMENTO` (`id`, `email_utente`, `nome_progetto`, `data_commento`, `testo`) VALUES
(4, 'fedesgambe@icloud.com', 'Flipper City', '2025-03-18', 'ciao'),
(5, 'simonemagli@gmail.com', 'Emilio 5.0', '2025-03-18', 'Che bel progetto');

-- --------------------------------------------------------

--
-- Struttura della tabella `COMPONENTE`
--

CREATE TABLE `COMPONENTE` (
  `nome` varchar(255) NOT NULL,
  `descrizione` text NOT NULL,
  `prezzo` float NOT NULL,
  `quantita` int NOT NULL
) ;

--
-- Dump dei dati per la tabella `COMPONENTE`
--

INSERT INTO `COMPONENTE` (`nome`, `descrizione`, `prezzo`, `quantita`) VALUES
('ewfea', 'fwesd', 23, 21),
('fsf', 'sdfds', 2, 4),
('grse', 'fsdf', 45, 33),
('jfosdjo', 'dsfkdsofj', 39, 2),
('tesngkewn', 'dsfvdskn', 34, 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `COMPRENDE`
--

CREATE TABLE `COMPRENDE` (
  `competenza` varchar(100) NOT NULL,
  `livello` int NOT NULL,
  `id_profilo` int NOT NULL
) ;

--
-- Dump dei dati per la tabella `COMPRENDE`
--

INSERT INTO `COMPRENDE` (`competenza`, `livello`, `id_profilo`) VALUES
('Basi di Dati', 2, 14),
('Fisica delle sfere', 1, 1),
('Google Maps', 3, 2),
('HTML, CSS, PHP, Javascript', 5, 14),
('Java', 4, 2),
('Python', 5, 14);

-- --------------------------------------------------------

--
-- Struttura della tabella `FINANZIAMENTO`
--

CREATE TABLE `FINANZIAMENTO` (
  `id` int NOT NULL,
  `data_finanziamento` date NOT NULL,
  `importo` float NOT NULL,
  `email_utente` varchar(255) NOT NULL,
  `nome_progetto` varchar(255) NOT NULL,
  `codice_reward` int NOT NULL
) ;

-- --------------------------------------------------------

--
-- Struttura della tabella `FORMATO`
--

CREATE TABLE `FORMATO` (
  `nome_componente` varchar(255) NOT NULL,
  `nome_hardware` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `FOTO_PROGETTO`
--

CREATE TABLE `FOTO_PROGETTO` (
  `id` int NOT NULL,
  `nome_progetto` varchar(255) NOT NULL,
  `foto_url` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `FOTO_PROGETTO`
--

INSERT INTO `FOTO_PROGETTO` (`id`, `nome_progetto`, `foto_url`) VALUES
(11, 'EcoCar', 'images/progetto1.jpg'),
(12, 'Smart Home AI', 'images/progetto2.jpg'),
(13, 'VR Learning', 'images/progetto3.jpg'),
(14, 'Mucca Silver', 'images/mucca.png'),
(15, 'Smart Park', 'images/smartpark.png'),
(16, 'Flipper City', 'images/flipper.png'),
(17, 'Emilio 5.0', 'images/emilio.png'),
(21, 'Traffic Live BO', 'images/maps.png');

-- --------------------------------------------------------

--
-- Struttura della tabella `INDICA`
--

CREATE TABLE `INDICA` (
  `competenza` varchar(100) NOT NULL,
  `livello` int NOT NULL,
  `email_utente` varchar(255) NOT NULL
) ;

--
-- Dump dei dati per la tabella `INDICA`
--

INSERT INTO `INDICA` (`competenza`, `livello`, `email_utente`) VALUES
('Basi di Dati', 5, 'simonemagli@gmail.com'),
('Fisica delle sfere', 3, 'fedesgambe@icloud.com'),
('Google Maps', 3, 'fedesgambe@icloud.com'),
('HTML, CSS, PHP, Javascript', 5, 'simonemagli@gmail.com'),
('Java', 5, 'fedesgambe@icloud.com'),
('Python', 5, 'simonemagli@gmail.com');

-- --------------------------------------------------------

--
-- Struttura della tabella `PROFILO`
--

CREATE TABLE `PROFILO` (
  `id` int NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `nome_software` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `PROFILO`
--

INSERT INTO `PROFILO` (`id`, `nome`, `nome_software`) VALUES
(1, 'Fisico', 'Flipper City'),
(2, 'Esperto IT', 'Flipper City'),
(14, 'Programmatore', 'Traffic Live BO');

-- --------------------------------------------------------

--
-- Struttura della tabella `PROGETTO`
--

CREATE TABLE `PROGETTO` (
  `nome` varchar(255) NOT NULL,
  `descrizione` text NOT NULL,
  `data_inserimento` date NOT NULL,
  `email_creatore` varchar(255) NOT NULL,
  `budget` float NOT NULL,
  `data_limite` date NOT NULL,
  `stato` enum('aperto','chiuso') NOT NULL DEFAULT 'aperto'
) ;

--
-- Dump dei dati per la tabella `PROGETTO`
--

INSERT INTO `PROGETTO` (`nome`, `descrizione`, `data_inserimento`, `email_creatore`, `budget`, `data_limite`, `stato`) VALUES
('EcoCar', 'Un’auto elettrica ecologica con materiali sostenibili.', '2025-03-18', 'test2@example.com', 25000, '2025-05-02', 'aperto'),
('Emilio 5.0', 'Il famosissimo Emilio, ma per adulti', '2025-03-18', 'fedesgambe@icloud.com', 20000, '2025-05-17', 'aperto'),
('Flipper City', 'Gioca a flipper, ma la mappa è la tua città', '2025-03-18', 'simonemagli@gmail.com', 20000, '2025-05-17', 'aperto'),
('Mucca Silver', 'Una mucca di carta stagnola', '2025-03-18', 'fedesgambe@icloud.com', 15000, '2025-04-17', 'aperto'),
('Smart Home AI', 'Un sistema di intelligenza artificiale per la gestione delle case smart.', '2025-03-18', 'test1@example.com', 15000, '2025-04-17', 'aperto'),
('Smart Park', 'Un parcehggio digitale per torvare sempre posto', '2025-03-18', 'simonemagli@gmail.com', 25000, '2025-05-02', 'aperto'),
('Traffic Live BO', 'Un canale telegram dove vedere il traffico dei bolognesi intendo reale!', '2025-03-18', 'fedesgambe@icloud.com', 50, '2025-03-20', 'aperto'),
('VR Learning', 'Una piattaforma di apprendimento in realtà virtuale per scuole e università.', '2025-03-18', 'test3@example.com', 20000, '2025-05-17', 'aperto');

-- --------------------------------------------------------

--
-- Struttura della tabella `PROGETTO_HARDWARE`
--

CREATE TABLE `PROGETTO_HARDWARE` (
  `nome_progetto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `PROGETTO_HARDWARE`
--

INSERT INTO `PROGETTO_HARDWARE` (`nome_progetto`) VALUES
('Emilio 5.0'),
('Mucca Silver'),
('Smart Park');

-- --------------------------------------------------------

--
-- Struttura della tabella `PROGETTO_SOFTWARE`
--

CREATE TABLE `PROGETTO_SOFTWARE` (
  `nome_progetto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `PROGETTO_SOFTWARE`
--

INSERT INTO `PROGETTO_SOFTWARE` (`nome_progetto`) VALUES
('Flipper City'),
('Traffic Live BO');

-- --------------------------------------------------------

--
-- Struttura della tabella `REWARD`
--

CREATE TABLE `REWARD` (
  `codice` int NOT NULL,
  `nome_progetto` varchar(255) NOT NULL,
  `descrizione` text NOT NULL,
  `foto_url` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `REWARD`
--

INSERT INTO `REWARD` (`codice`, `nome_progetto`, `descrizione`, `foto_url`) VALUES
(10, 'EcoCar', 'Sticker esclusivo del progetto', 'images/reward1.png'),
(11, 'EcoCar', 'T-shirt personalizzata del progetto', 'images/reward2.png'),
(12, 'EcoCar', 'Accesso anticipato ai contenuti del progetto', 'images/reward3.png'),
(13, 'Smart Home AI', 'Stampa digitale ad alta risoluzione', 'images/reward4.png'),
(14, 'Smart Home AI', 'NFT esclusivo del progetto', 'images/reward5.png'),
(15, 'Smart Home AI', 'Meet & Greet con l\'artista', 'images/reward6.png'),
(16, 'VR Learning', 'Guida e-book su sostenibilità', 'images/reward7.png'),
(17, 'VR Learning', 'Sconto su prodotti green', 'images/reward8.png'),
(18, 'VR Learning', 'Nome inciso sul prodotto finale', 'images/reward9.png');

-- --------------------------------------------------------

--
-- Struttura della tabella `RISPOSTA_COMMENTO`
--

CREATE TABLE `RISPOSTA_COMMENTO` (
  `id_commento` int NOT NULL,
  `email_creatore` varchar(255) NOT NULL,
  `data_risposta` date NOT NULL,
  `testo` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `RISPOSTA_COMMENTO`
--

INSERT INTO `RISPOSTA_COMMENTO` (`id_commento`, `email_creatore`, `data_risposta`, `testo`) VALUES
(5, 'fedesgambe@icloud.com', '2025-03-18', 'Grazie mille Simone!');

-- --------------------------------------------------------

--
-- Struttura della tabella `SKILL`
--

CREATE TABLE `SKILL` (
  `competenza` varchar(100) NOT NULL,
  `livello` int NOT NULL,
  `email_utente_amm` varchar(255) DEFAULT NULL
) ;

--
-- Dump dei dati per la tabella `SKILL`
--

INSERT INTO `SKILL` (`competenza`, `livello`, `email_utente_amm`) VALUES
('Analisi 1', 1, 'peppe24@gmail.com'),
('Analisi 1', 2, 'peppe24@gmail.com'),
('Analisi 1', 3, 'peppe24@gmail.com'),
('Analisi 1', 4, 'peppe24@gmail.com'),
('Analisi 1', 5, 'peppe24@gmail.com'),
('Basi di Dati', 1, 'peppe24@gmail.com'),
('Basi di Dati', 2, 'peppe24@gmail.com'),
('Basi di Dati', 3, 'peppe24@gmail.com'),
('Basi di Dati', 4, 'peppe24@gmail.com'),
('Basi di Dati', 5, 'peppe24@gmail.com'),
('Chimica dei materiali', 1, 'peppe24@gmail.com'),
('Chimica dei materiali', 2, 'peppe24@gmail.com'),
('Chimica dei materiali', 3, 'peppe24@gmail.com'),
('Chimica dei materiali', 4, 'peppe24@gmail.com'),
('Chimica dei materiali', 5, 'peppe24@gmail.com'),
('Elettronica di base', 1, 'peppe24@gmail.com'),
('Elettronica di base', 2, 'peppe24@gmail.com'),
('Elettronica di base', 3, 'peppe24@gmail.com'),
('Elettronica di base', 4, 'peppe24@gmail.com'),
('Elettronica di base', 5, 'peppe24@gmail.com'),
('Fisica delle sfere', 1, 'peppe24@gmail.com'),
('Fisica delle sfere', 2, 'peppe24@gmail.com'),
('Fisica delle sfere', 3, 'peppe24@gmail.com'),
('Fisica delle sfere', 4, 'peppe24@gmail.com'),
('Fisica delle sfere', 5, 'peppe24@gmail.com'),
('Google Maps', 1, 'peppe24@gmail.com'),
('Google Maps', 2, 'peppe24@gmail.com'),
('Google Maps', 3, 'peppe24@gmail.com'),
('Google Maps', 4, 'peppe24@gmail.com'),
('Google Maps', 5, 'peppe24@gmail.com'),
('HTML, CSS, PHP, Javascript', 1, 'peppe24@gmail.com'),
('HTML, CSS, PHP, Javascript', 2, 'peppe24@gmail.com'),
('HTML, CSS, PHP, Javascript', 3, 'peppe24@gmail.com'),
('HTML, CSS, PHP, Javascript', 4, 'peppe24@gmail.com'),
('HTML, CSS, PHP, Javascript', 5, 'peppe24@gmail.com'),
('Java', 1, 'peppe24@gmail.com'),
('Java', 2, 'peppe24@gmail.com'),
('Java', 3, 'peppe24@gmail.com'),
('Java', 4, 'peppe24@gmail.com'),
('Java', 5, 'peppe24@gmail.com'),
('Python', 1, 'peppe24@gmail.com'),
('Python', 2, 'peppe24@gmail.com'),
('Python', 3, 'peppe24@gmail.com'),
('Python', 4, 'peppe24@gmail.com'),
('Python', 5, 'peppe24@gmail.com'),
('Sistemi e reti', 1, 'peppe24@gmail.com'),
('Sistemi e reti', 2, 'peppe24@gmail.com'),
('Sistemi e reti', 3, 'peppe24@gmail.com'),
('Sistemi e reti', 4, 'peppe24@gmail.com'),
('Sistemi e reti', 5, 'peppe24@gmail.com');

-- --------------------------------------------------------

--
-- Struttura della tabella `SKILL_CURRICULUM`
--

CREATE TABLE `SKILL_CURRICULUM` (
  `competenza` varchar(100) NOT NULL,
  `livello` int DEFAULT NULL,
  `email_utente` varchar(255) NOT NULL,
  `email_utente_amm` varchar(255) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Struttura della tabella `SKILL_RICHIESTE`
--

CREATE TABLE `SKILL_RICHIESTE` (
  `id_profilo` int NOT NULL,
  `competenza` varchar(100) NOT NULL,
  `livello` int DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Struttura della tabella `UTENTE`
--

CREATE TABLE `UTENTE` (
  `email` varchar(255) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `anno_nascita` year NOT NULL,
  `luogo_nascita` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `UTENTE`
--

INSERT INTO `UTENTE` (`email`, `nickname`, `password`, `nome`, `cognome`, `anno_nascita`, `luogo_nascita`) VALUES
('fedesgambe@icloud.com', 'fedesgambe', '$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2', 'Federico', 'Sgambelluri', '2003', 'Bologna'),
('mirko@gmail.com', 'Mirko', '$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2', 'Mirko', 'Rossi', '1980', 'Roma'),
('peppe24@gmail.com', 'peppeeee', '$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2', 'Giuseppe', 'Cozza', '2003', 'Bologna'),
('simonemagli@gmail.com', 'sama', '$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2', 'Simone', 'Magli', '2003', 'Bologna'),
('test1@example.com', 'testuser1', '$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2', 'Mario', 'Rossi', '1990', 'Roma'),
('test2@example.com', 'testuser2', '$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2', 'Luca', 'Bianchi', '1985', 'Milano'),
('test3@example.com', 'testuser3', '$2y$10$kAG8SXG2hVSiKwnkc4Ho1OhFzp8Iqgv7gsv/xx7XiFFGOKGAPn7g2', 'Giulia', 'Verdi', '1992', 'Napoli');

-- --------------------------------------------------------

--
-- Struttura della tabella `UTENTE_AMMINISTRATORE`
--

CREATE TABLE `UTENTE_AMMINISTRATORE` (
  `email_utente_amm` varchar(255) NOT NULL,
  `codice_sicurezza` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `UTENTE_AMMINISTRATORE`
--

INSERT INTO `UTENTE_AMMINISTRATORE` (`email_utente_amm`, `codice_sicurezza`) VALUES
('peppe24@gmail.com', '12345');

-- --------------------------------------------------------

--
-- Struttura della tabella `UTENTE_CREATORE`
--

CREATE TABLE `UTENTE_CREATORE` (
  `email_utente_creat` varchar(255) NOT NULL,
  `nr_progetti` int DEFAULT '0',
  `affidabilita` float DEFAULT NULL
) ;

--
-- Dump dei dati per la tabella `UTENTE_CREATORE`
--

INSERT INTO `UTENTE_CREATORE` (`email_utente_creat`, `nr_progetti`, `affidabilita`) VALUES
('fedesgambe@icloud.com', 0, 8),
('simonemagli@gmail.com', 0, 8),
('test1@example.com', 0, 7.5),
('test2@example.com', 0, 8),
('test3@example.com', 0, 6.5);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `CANDIDATURA`
--
ALTER TABLE `CANDIDATURA`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_utente` (`email_utente`),
  ADD KEY `id_profilo` (`id_profilo`);

--
-- Indici per le tabelle `COMMENTO`
--
ALTER TABLE `COMMENTO`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_utente` (`email_utente`),
  ADD KEY `nome_progetto` (`nome_progetto`);

--
-- Indici per le tabelle `COMPONENTE`
--
ALTER TABLE `COMPONENTE`
  ADD PRIMARY KEY (`nome`);

--
-- Indici per le tabelle `COMPRENDE`
--
ALTER TABLE `COMPRENDE`
  ADD PRIMARY KEY (`id_profilo`,`competenza`,`livello`),
  ADD KEY `competenza` (`competenza`,`livello`);

--
-- Indici per le tabelle `FINANZIAMENTO`
--
ALTER TABLE `FINANZIAMENTO`
  ADD PRIMARY KEY (`id`,`data_finanziamento`),
  ADD KEY `email_utente` (`email_utente`),
  ADD KEY `nome_progetto` (`nome_progetto`),
  ADD KEY `codice_reward` (`codice_reward`);

--
-- Indici per le tabelle `FORMATO`
--
ALTER TABLE `FORMATO`
  ADD PRIMARY KEY (`nome_componente`,`nome_hardware`),
  ADD KEY `nome_hardware` (`nome_hardware`);

--
-- Indici per le tabelle `FOTO_PROGETTO`
--
ALTER TABLE `FOTO_PROGETTO`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nome_progetto` (`nome_progetto`);

--
-- Indici per le tabelle `INDICA`
--
ALTER TABLE `INDICA`
  ADD PRIMARY KEY (`email_utente`,`competenza`,`livello`),
  ADD KEY `competenza` (`competenza`,`livello`);

--
-- Indici per le tabelle `PROFILO`
--
ALTER TABLE `PROFILO`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nome_software` (`nome_software`);

--
-- Indici per le tabelle `PROGETTO`
--
ALTER TABLE `PROGETTO`
  ADD PRIMARY KEY (`nome`),
  ADD KEY `email_creatore` (`email_creatore`);

--
-- Indici per le tabelle `PROGETTO_HARDWARE`
--
ALTER TABLE `PROGETTO_HARDWARE`
  ADD PRIMARY KEY (`nome_progetto`);

--
-- Indici per le tabelle `PROGETTO_SOFTWARE`
--
ALTER TABLE `PROGETTO_SOFTWARE`
  ADD PRIMARY KEY (`nome_progetto`);

--
-- Indici per le tabelle `REWARD`
--
ALTER TABLE `REWARD`
  ADD PRIMARY KEY (`codice`),
  ADD KEY `nome_progetto` (`nome_progetto`);

--
-- Indici per le tabelle `RISPOSTA_COMMENTO`
--
ALTER TABLE `RISPOSTA_COMMENTO`
  ADD PRIMARY KEY (`id_commento`,`email_creatore`),
  ADD KEY `email_creatore` (`email_creatore`);

--
-- Indici per le tabelle `SKILL`
--
ALTER TABLE `SKILL`
  ADD PRIMARY KEY (`competenza`,`livello`),
  ADD KEY `email_utente_amm` (`email_utente_amm`);

--
-- Indici per le tabelle `SKILL_CURRICULUM`
--
ALTER TABLE `SKILL_CURRICULUM`
  ADD PRIMARY KEY (`email_utente`,`competenza`),
  ADD KEY `email_utente_amm` (`email_utente_amm`);

--
-- Indici per le tabelle `SKILL_RICHIESTE`
--
ALTER TABLE `SKILL_RICHIESTE`
  ADD PRIMARY KEY (`id_profilo`,`competenza`);

--
-- Indici per le tabelle `UTENTE`
--
ALTER TABLE `UTENTE`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `nickname` (`nickname`);

--
-- Indici per le tabelle `UTENTE_AMMINISTRATORE`
--
ALTER TABLE `UTENTE_AMMINISTRATORE`
  ADD PRIMARY KEY (`email_utente_amm`);

--
-- Indici per le tabelle `UTENTE_CREATORE`
--
ALTER TABLE `UTENTE_CREATORE`
  ADD PRIMARY KEY (`email_utente_creat`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `CANDIDATURA`
--
ALTER TABLE `CANDIDATURA`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `COMMENTO`
--
ALTER TABLE `COMMENTO`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `FINANZIAMENTO`
--
ALTER TABLE `FINANZIAMENTO`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `FOTO_PROGETTO`
--
ALTER TABLE `FOTO_PROGETTO`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT per la tabella `PROFILO`
--
ALTER TABLE `PROFILO`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT per la tabella `REWARD`
--
ALTER TABLE `REWARD`
  MODIFY `codice` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `CANDIDATURA`
--
ALTER TABLE `CANDIDATURA`
  ADD CONSTRAINT `candidatura_ibfk_1` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `candidatura_ibfk_2` FOREIGN KEY (`id_profilo`) REFERENCES `PROFILO` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `COMMENTO`
--
ALTER TABLE `COMMENTO`
  ADD CONSTRAINT `commento_ibfk_1` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `commento_ibfk_2` FOREIGN KEY (`nome_progetto`) REFERENCES `PROGETTO` (`nome`) ON DELETE CASCADE;

--
-- Limiti per la tabella `COMPRENDE`
--
ALTER TABLE `COMPRENDE`
  ADD CONSTRAINT `comprende_ibfk_1` FOREIGN KEY (`id_profilo`) REFERENCES `PROFILO` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comprende_ibfk_2` FOREIGN KEY (`competenza`,`livello`) REFERENCES `SKILL` (`competenza`, `livello`) ON DELETE CASCADE;

--
-- Limiti per la tabella `FINANZIAMENTO`
--
ALTER TABLE `FINANZIAMENTO`
  ADD CONSTRAINT `finanziamento_ibfk_1` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `finanziamento_ibfk_2` FOREIGN KEY (`nome_progetto`) REFERENCES `PROGETTO` (`nome`) ON DELETE CASCADE,
  ADD CONSTRAINT `finanziamento_ibfk_3` FOREIGN KEY (`codice_reward`) REFERENCES `REWARD` (`codice`) ON DELETE CASCADE;

--
-- Limiti per la tabella `FORMATO`
--
ALTER TABLE `FORMATO`
  ADD CONSTRAINT `formato_ibfk_1` FOREIGN KEY (`nome_hardware`) REFERENCES `PROGETTO_HARDWARE` (`nome_progetto`) ON DELETE CASCADE,
  ADD CONSTRAINT `formato_ibfk_2` FOREIGN KEY (`nome_componente`) REFERENCES `COMPONENTE` (`nome`) ON DELETE CASCADE;

--
-- Limiti per la tabella `FOTO_PROGETTO`
--
ALTER TABLE `FOTO_PROGETTO`
  ADD CONSTRAINT `foto_progetto_ibfk_1` FOREIGN KEY (`nome_progetto`) REFERENCES `PROGETTO` (`nome`) ON DELETE CASCADE;

--
-- Limiti per la tabella `INDICA`
--
ALTER TABLE `INDICA`
  ADD CONSTRAINT `indica_ibfk_1` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `indica_ibfk_2` FOREIGN KEY (`competenza`,`livello`) REFERENCES `SKILL` (`competenza`, `livello`) ON DELETE CASCADE;

--
-- Limiti per la tabella `PROFILO`
--
ALTER TABLE `PROFILO`
  ADD CONSTRAINT `profilo_ibfk_1` FOREIGN KEY (`nome_software`) REFERENCES `PROGETTO_SOFTWARE` (`nome_progetto`) ON DELETE CASCADE;

--
-- Limiti per la tabella `PROGETTO`
--
ALTER TABLE `PROGETTO`
  ADD CONSTRAINT `progetto_ibfk_1` FOREIGN KEY (`email_creatore`) REFERENCES `UTENTE_CREATORE` (`email_utente_creat`) ON DELETE CASCADE;

--
-- Limiti per la tabella `PROGETTO_HARDWARE`
--
ALTER TABLE `PROGETTO_HARDWARE`
  ADD CONSTRAINT `progetto_hardware_ibfk_1` FOREIGN KEY (`nome_progetto`) REFERENCES `PROGETTO` (`nome`) ON DELETE CASCADE;

--
-- Limiti per la tabella `PROGETTO_SOFTWARE`
--
ALTER TABLE `PROGETTO_SOFTWARE`
  ADD CONSTRAINT `progetto_software_ibfk_1` FOREIGN KEY (`nome_progetto`) REFERENCES `PROGETTO` (`nome`) ON DELETE CASCADE;

--
-- Limiti per la tabella `REWARD`
--
ALTER TABLE `REWARD`
  ADD CONSTRAINT `reward_ibfk_1` FOREIGN KEY (`nome_progetto`) REFERENCES `PROGETTO` (`nome`) ON DELETE CASCADE;

--
-- Limiti per la tabella `RISPOSTA_COMMENTO`
--
ALTER TABLE `RISPOSTA_COMMENTO`
  ADD CONSTRAINT `risposta_commento_ibfk_1` FOREIGN KEY (`id_commento`) REFERENCES `COMMENTO` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `risposta_commento_ibfk_2` FOREIGN KEY (`email_creatore`) REFERENCES `UTENTE_CREATORE` (`email_utente_creat`) ON DELETE CASCADE;

--
-- Limiti per la tabella `SKILL`
--
ALTER TABLE `SKILL`
  ADD CONSTRAINT `skill_ibfk_1` FOREIGN KEY (`email_utente_amm`) REFERENCES `UTENTE_AMMINISTRATORE` (`email_utente_amm`) ON DELETE CASCADE;

--
-- Limiti per la tabella `SKILL_CURRICULUM`
--
ALTER TABLE `SKILL_CURRICULUM`
  ADD CONSTRAINT `skill_curriculum_ibfk_1` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `skill_curriculum_ibfk_2` FOREIGN KEY (`email_utente_amm`) REFERENCES `UTENTE_AMMINISTRATORE` (`email_utente_amm`) ON DELETE CASCADE;

--
-- Limiti per la tabella `SKILL_RICHIESTE`
--
ALTER TABLE `SKILL_RICHIESTE`
  ADD CONSTRAINT `skill_richieste_ibfk_1` FOREIGN KEY (`id_profilo`) REFERENCES `PROFILO` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `UTENTE_AMMINISTRATORE`
--
ALTER TABLE `UTENTE_AMMINISTRATORE`
  ADD CONSTRAINT `utente_amministratore_ibfk_1` FOREIGN KEY (`email_utente_amm`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE;

--
-- Limiti per la tabella `UTENTE_CREATORE`
--
ALTER TABLE `UTENTE_CREATORE`
  ADD CONSTRAINT `utente_creatore_ibfk_1` FOREIGN KEY (`email_utente_creat`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
