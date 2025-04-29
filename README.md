# BOSTARTER - README

## Cos'è BOSTARTER? 🚀

**BOSTARTER** è una piattaforma di **crowdfunding** dedicata alla realizzazione di progetti **hardware** e **software**. Ispirata a **Kickstarter**, la piattaforma permette agli utenti di creare progetti da finanziare, offrendo **premi** (rewards) non economici in cambio dei contributi ricevuti. Inoltre, BOSTARTER offre anche la possibilità per gli utenti di **candidarsi** per lo sviluppo di progetti software, a condizione che le loro competenze corrispondano a quelle richieste dal progetto.

---

## Funzionalità principali 🛠️

- **Gestione utenti**: Gli utenti si registrano con un'**email unica** e forniscono informazioni personali come **nome**, **cognome**, **data e luogo di nascita**. Ogni utente può indicare le proprie **competenze** attraverso una lista di skill, che includono una competenza e il relativo livello (da 0 a 5).

- **Amministratori e Creatori**: Gli utenti possono appartenere a due categorie:
   - **Amministratori**: Hanno la possibilità di aggiungere nuove **competenze** alla piattaforma.
   - **Creatori**: Possono creare e gestire **progetti** di crowdfunding, specificando il tipo di progetto (hardware o software), il **budget**, la **data limite** e i **rewards** offerti.

- **Progetti**: Ogni progetto ha un **nome univoco**, una **descrizione**, un **budget** da raggiungere, una **data limite** e uno **stato** che può essere "aperto" o "chiuso". I progetti hardware includono una lista di **componenti** necessari, mentre i progetti software richiedono **specifici profili professionali** con skill richieste.

- **Finanziamenti e Rewards**: Gli utenti possono finanziare i progetti con importi variabili. Ogni finanziamento è associato a un **reward**. Quando la somma totale dei finanziamenti supera il budget del progetto, oppure il progetto supera la data limite senza raggiungere il budget, lo stato del progetto cambia a **"chiuso"**, impedendo ulteriori finanziamenti.

- **Commenti e Risposte**: Gli utenti possono lasciare **commenti** sui progetti, ai quali l'utente creatore può rispondere.

- **Candidature per progetti software**: Gli utenti possono candidarsi per ruoli specifici in **progetti software** se le loro **skill** corrispondono ai requisiti del profilo. Ogni candidatura può essere **accettata** o **rifiutata** dall'utente creatore.

---

## Struttura del database 🗃️

Il database di BOSTARTER è strutturato per gestire le seguenti entità principali:

- **Utenti**: Contengono informazioni personali, competenze, e appartenenza a una delle due categorie (amministratore o creatore).
- **Progetti**: Ogni progetto ha un nome, descrizione, stato, budget e data limite. Può essere hardware o software.
- **Reward**: I premi offerti ai finanziatori, ognuno con un codice univoco.
- **Finanziamenti**: Gli importi dei finanziamenti ricevuti dai progetti, associati a un reward.
- **Commenti**: I commenti lasciati dagli utenti sui progetti, a cui i creatori possono rispondere.
- **Candidature**: Le candidature degli utenti per partecipare allo sviluppo di un progetto software.

---

## Accesso e utilizzo 🔑

1. **Registrazione**: Gli utenti si registrano sulla piattaforma utilizzando una **email univoca**. Possono essere **amministratori**, **creatori**, o **utenti comuni**.
2. **Creazione di un progetto**: Un **creatore** può lanciare un progetto, impostando un **budget**, una **data di scadenza** e specificando rewards e dettagli. I progetti possono essere **hardware** o **software**.
3. **Finanziamenti**: Gli utenti possono finanziare i progetti, selezionando un **reward** in cambio del loro contributo.
4. **Candidature ai progetti software**: Gli utenti con competenze adeguate possono candidarsi per **ruoli specifici** nei progetti software.
5. **Commenti e risposte**: Gli utenti possono lasciare **commenti** sui progetti, e i creatori possono rispondere.

---

## Requisiti 🔧

- **Tecnologie**: La piattaforma è sviluppata utilizzando [inserire stack tecnologico].
- **Database**: MySQL per la gestione dei dati relativi agli utenti, progetti, finanziamenti, commenti, ecc.
- **Autenticazione**: Gli utenti possono accedere tramite **login** con email e password.

---

## Contribuire 🤝

Se desideri contribuire allo sviluppo della piattaforma **BOSTARTER**, puoi fare una pull request sul nostro repository **GitHub**.

---

## Licenza 📜

Questa piattaforma è **open-source** e distribuita sotto la licenza [inserire licenza].

---

🔗 [Visita la piattaforma BOSTARTER](#)

# INSTALLAZIONE ⚙️

# 📥 Installazione ed Esecuzione del Progetto

## 1. Configurazione PHP su MAMP (SOLO WINDOWS)
**Modifica del file php.ini**  
1. Apri il file di configurazione PHP:
```bash
MAMP/conf/php8.3.1/php.ini
```
2. Aggiungi l'estensione richiesta alla linea 679:
```ini
extension=fileinfo
```

---

## 2. Installazione MongoDB
**Installazione via Homebrew**
```bash
brew tap mongodb/brew
brew install mongodb-community@7.0
brew services start mongodb-community@7.0
```

---

## 3. Dipendenze del Progetto
**Installazione pacchetti Node.js**
```bash
cd /percorso/progetto
npm install mysql2 mongodb
```

---

## 4. Esecuzione dello Script
**Avvio generale**
```bash
node script.js
```

---

## 5. Configurazione Specifica per Windows
1. **Verifica porte MAMP**:
    - Apri MAMP → Preferences → Ports
    - Confronta la porta MySQL con quella nello script.js

2. **Avvio da terminale integrato**:
    - Apri la cartella `js` del progetto
    - Nella barra dei percorsi digita `cmd`
   ```bash
   node script.js
   ```

---

## 🔍 Verifica Finale
Output atteso:
```
✅ Connessione al database riuscita
📥 Dati importati correttamente
🚀 Script in esecuzione...
```

