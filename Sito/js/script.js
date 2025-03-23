// Assicurati di installare le dipendenze con:
// npm install mysql2 mongodb

const mysql = require('mysql2/promise');
const { MongoClient } = require('mongodb');

// Configurazione MySQL
const MYSQL_CONFIG = {
    host: 'localhost',
    user: 'root',
    password: 'root',  // sostituisci con la tua password
    database: 'BOSTARTER'
};

// Configurazione MongoDB
const MONGO_URI = 'mongodb://localhost:27017';
const MONGO_DB_NAME = 'bostarterLogs';  // oppure il nome che preferisci
const MONGO_COLLECTION = 'log_events';

// Funzione di sincronizzazione dei log
async function syncLogs() {
    let mysqlConnection;
    let mongoClient;
    try {
        // Connessione a MySQL
        mysqlConnection = await mysql.createConnection(MYSQL_CONFIG);
        // Connessione a MongoDB
        mongoClient = new MongoClient(MONGO_URI, { useUnifiedTopology: true });
        await mongoClient.connect();
        const mongoDb = mongoClient.db(MONGO_DB_NAME);
        const logsCollection = mongoDb.collection(MONGO_COLLECTION);

        // Recupera tutti i log dalla tabella SQL
        const [rows] = await mysqlConnection.execute('SELECT * FROM LOG_EVENTI');

        // Per ogni log, esegui un upsert in MongoDB (aggiorna se esiste già, altrimenti inserisci)
        for (const row of rows) {
            await logsCollection.updateOne(
                { id: row.id }, // filtro per identificare il record
                { $set: row },
                { upsert: true }
            );
        }
        console.log(`Sincronizzati ${rows.length} log in MongoDB.`);
    } catch (err) {
        console.error('Errore durante la sincronizzazione dei log:', err);
    } finally {
        if (mysqlConnection) await mysqlConnection.end();
        if (mongoClient) await mongoClient.close();
    }
}

// Avvia la sincronizzazione periodica ogni 10 secondi (puoi modificare l'intervallo)
setInterval(syncLogs, 10000);