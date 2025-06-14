// Definiamo le costanti per la connessione a MongoDB e il percordo per il json di log

const mysql = require('mysql2/promise'); //Per utilizzare async e await con mysql
const { MongoClient } = require('mongodb'); // Permette di connettersi a Mongo DB
const fs = require('fs'); // Modulo per leggere i file
const path = './log.json';

// Configurazione MySQL per leggere la tabella LOG_EVENTI
const MYSQL_CONFIG = {
    host: 'localhost',
    user: 'root',
    password: 'root',
    database: 'BOSTARTER',
    port: 8889 // Simo e Peppe cambiate se su Windows MAMP usa porta diversa
};

// Configurazione MongoDB
const MONGO_URI = 'mongodb://localhost:27017';
const MONGO_DB_NAME = 'bostarterLogs';
const MONGO_COLLECTION = 'log_events';

// Funzione per scrivere log su file JSON
// Identazione
function scriviLog(logs) {
    fs.writeFileSync(path, JSON.stringify(logs, null, 2), 'utf8');
}

// Funzione principale: sincronizza + salva su file
async function syncLogs() {
    let mysqlConnection;
    let mongoClient;
    try {
        // Connessione MySQL
        // Essendo in un metodo asyncrono utlizzo await
        mysqlConnection = await mysql.createConnection(MYSQL_CONFIG);

        // Connessione MongoDB
        mongoClient = new MongoClient(MONGO_URI);
        await mongoClient.connect();
        const mongoDb = mongoClient.db(MONGO_DB_NAME);
        const logsCollection = mongoDb.collection(MONGO_COLLECTION);

        // Prendo solo log non ancora sincronizzati
        const [rows] = await mysqlConnection.execute('SELECT * FROM LOG_EVENTI WHERE sincronizzato = FALSE');

        for (const row of rows) {
            await logsCollection.updateOne(
                { id: row.id },
                { $set: row },
                { upsert: true }
            );

            // Aggiorno il flag sincronizzato a ture
            await mysqlConnection.execute('UPDATE LOG_EVENTI SET sincronizzato = TRUE WHERE id = ?', [row.id]);
        }

        // Cerco tutti i log attuali da MongoDB e effettua il salvataggio nel file log.json
        const allLogs = await logsCollection.find().sort({ data: -1 }).toArray();
        scriviLog(allLogs);

        console.log(`${rows.length} log sincronizzati. File log.json aggiornato con ${allLogs.length} log totali.`);
    } catch (err) {
        console.error('Errore durante la sincronizzazione:', err);
    } finally {
        if (mysqlConnection) await mysqlConnection.end();
        if (mongoClient) await mongoClient.close();
    }
}

// Controllo i log ongi 10 secondi
setInterval(syncLogs, 10000);
