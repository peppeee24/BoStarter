Andare su MAMP/conf/php8.3.1/php.ini e aggiungere negli extensions (riga 679) e aggiungere:
extension=fileinfo



Mongo DB - Istruzioni

1) Installa

brew tap mongodb/brew
brew install mongodb-community@7.0
brew services start mongodb-community@7.0


2) Nella cartella del progetto, installa Note.js

npm install mysql2 mongodb


3) Sempre nella cartella del progetto, eseguire il file js

node script.js


