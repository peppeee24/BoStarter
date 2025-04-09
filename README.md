Andare su MAMP/conf/php8.3.1/php.ini e aggiungere negli extensions (riga 679) e aggiungere:
extension=fileinfo



Mongo DB - Istruzioni per installazione

1) Installa

brew tap mongodb/brew
brew install mongodb-community@7.0
brew services start mongodb-community@7.0


2) Nella cartella del progetto, installa Note.js

npm install mysql2 mongodb


3) Sempre nella cartella del progetto, eseguire il file js

node script.js

avviare i log da windows

1) nel file script.js controlla che la porta sia la stessa di mamp (per controllare quale sia la porta di MAMP vai su preferences e poi su port -> controlla quella di mySQL)

2) dentro la cartella js del progetto vai nella barra dei percorsi e scrivi cmd per aprire il terminale

3) digita node script.js

4) dovresti vedere i log


