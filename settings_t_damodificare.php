<?php
//modulo delle KEYs per funzionamento dei bot (da non committare)

//Telegram

define('TELEGRAM_BOT_TOKEN', ''); //inserire il token del Bot rilasciato da @botfather
define('BOT_WEBHOOK', 'https://dominioinhttpsconpercorsodelbot/start.php');
define('LOG_FILE', 'db/telegram.log');
define('GDRIVEKEY', ''); // key dello sheet di google drive che deve essere prima condiviso e pubblicato sul web come csv
define('GDRIVEGID', ''); // GID dello sheet delle risposte al form di google drive
define('GDRIVEGIDADMIN', ''); // GID dello sheet di google drive in cui ci sono gli utenti autorizzati agli aggiornamenti
define('GRUPPO', ''); // gruppo Telegram riservato dove ricevere le segnalazioni. Inserire il bot tra gli amministratori del gruppo!! di solito inzia con -
define('SERVER', 'https://dominioinhttps/conpercorsodelbot/'); // indirizzo e path del server dove accedere per controllare le segnalazioni
define('GOOGLE_URL_BASE', "https://docs.google.com/spreadsheets/d/". GDRIVEKEY . "/gviz/tq?tqx=out:csv&amp;tq="); // google url to access the spreadsheet

// Your csv database
$csv_path = '/percorsodelbot/db/map_data.txt'; // da personalizzare
$db_path = '/percorsodelbot/db/db.sqlite'; // da personalizzare
define('DB_NAME', "sqlite:" . $db_path);
define('DB_TABLE', "user");
define('DB_TABLE_GEO', "segnalazioni");
define('DB_CONF', 0666);
define('DB_ERR', "errore database SQLITE");
define('DB_PATH', $db_path);
define('CSV_PATH', $csv_path);
