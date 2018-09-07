<?php
/**
* Telegram Bot example for mapping "comunepulitobot".
* @author Francesco Piero Paolicelli
*/
include("/usr/www/piersoft/comunepulitobot/settings_t.php");
include("/usr/www/piersoft/comunepulitobot/Telegram.php");

class mainloop{
const MAX_LENGTH = 4096;
function start($telegram,$update)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	//$data=new getdata();
	// Instances the class
	$db = new PDO(DB_NAME);

	/* If you need to manually take some parameters
	*  $result = $telegram->getData();
	*  $text = $result["message"] ["text"];
	*  $chat_id = $result["message"] ["chat"]["id"];
	*/

	$first_name=$update["message"]["from"]["first_name"];
	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];
	$username=$update["message"]["from"]["username"];
	$this->shell($username,$telegram, $db,$first_name,$text,$chat_id,$user_id,$location,$reply_to_msg);
	//$db = NULL;

}

//gestisce l'interfaccia utente
 function shell($username,$telegram,$db,$first_name,$text,$chat_id,$user_id,$location,$reply_to_msg)
{

	$csv_path=CSV_PATH;
	$db_path=DB_PATH;
	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	if ($text == "/start" || $text == "info" || $text == "©️info") {
		$reply = "Benvenuto ".$first_name.". Questo Bot è stato realizzato da @piersoft per dimostrazione di Civic Hacking.\nPermette di mappare situazioni per un monitoraggio civico di degrado urbano, buche stradali o lampioni con luci fulminate, a sussidio del proprio ente comunale.\nLeggi il progetto su http://www.piersoft.it/civichacking-con-un-bot-telegram-la-demo-di-comunepulitobot/.\nL'autore non è responsabile per l'uso improprio di questo strumento e dei contenuti degli utenti.\nLa mappatura è abilitata solo per utenti che hanno \"username\" (univoci su Telegram tramite la sua sezione Impostazioni) e vengono registrati e visualizzati pubblicamente su mappa con licenza CC0 (pubblico dominio).\nPer partecipare bisogna compilare il seguente form: https://goo.gl/forms/jHF32JX6K7V2mkkk2. \n\nLa geocodifca dei dati avviene grazie al database Nominatim di openStreeMap con licenza oDBL.\nTutti i dati sono in licenza CC0 in formato CSV su http://bit.ly/2MGxRPP.\nIcone della mappa realizzate da Francesco Lanotte";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);


		$forcehide=$telegram->buildKeyBoardHide(true);
		$content = array('chat_id' => $chat_id, 'text' => "", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
		$bot_request_message=$telegram->sendMessage($content);

		$log=$today. ",new chat started," .$chat_id. "\n";
		$this->create_keyboard($telegram,$chat_id);
		exit;
	}elseif ($text == "/location" || $text == "🌐posizione") {

		$option = array(array($telegram->buildKeyboardButton("Invia la tua posizione / send your location", false, true)) //this work
											);
	// Create a permanent custom keyboard
	$keyb = $telegram->buildKeyBoard($option, $onetime=false);
	$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Attiva la localizzazione sul tuo smartphone / Turn on your GPS");
	$telegram->sendMessage($content);
	exit;
	}else if ($text == "/istruzioni" || $text == "istruzioni" || $text == "❓istruzioni") {

		$img = curl_file_create('istruzioni.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$content = array('chat_id' => $chat_id, 'text' => "[Immagine realizzata da Alessandro Ghezzer]");
		$telegram->sendMessage($content);
		$content = array('chat_id' => $chat_id, 'text' => "<b>Dopo che hai inviato la tua posizione puoi aggiungere una categoria!!</b>\nSe hai mandato una foto o file e vuoi aggiungere un testo puoi usare t:numsegnalazione:testo\nEsempio <b>t:123:testo prova</b>",'parse_mode'=>"HTML");
		$telegram->sendMessage($content);
		$content = array('chat_id' => $chat_id, 'text' => "Per cancellare: <b>cancella:numerosegnalazione</b>\n",'parse_mode'=>"HTML");
		$telegram->sendMessage($content);
		$link="http://bit.ly/2N9NDlH";
		$content = array('chat_id' => $chat_id, 'text' => "<b>Vuoi anche un piccolo video passo passo? clicca </b>".$link,'parse_mode'=>"HTML");
		$telegram->sendMessage($content);
		$log=$today. ",istruzioni," .$chat_id. "\n";
		$this->create_keyboard($telegram,$chat_id);
		exit;
	}elseif ($text=="update"){
		$statement = "DELETE FROM ". DB_TABLE_GEO ." WHERE username =' '";
		$db->exec($statement);
		exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
		$this->create_keyboard($telegram,$chat_id);
		exit;
	}elseif ($text=="Annulla")
		{
			$this->create_keyboard($telegram,$chat_id);
			exit;
		}
		elseif ($text=="aggiorna" || $text =="/aggiorna" || $text =="❌aggiorna" )
			{

				$reply = "Per aggiornare una segnalazione digita a:numerosegnalazione, esempio a:699";
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$this->create_keyboard($telegram,$chat_id);
				exit;
			}elseif (strpos($text,'cancella:') !== false)

			{
				$text=str_replace("cancella:","",$text);
				$text=str_replace(" ","",$text);

				if ($username==""){
					$content = array('chat_id' => $chat_id, 'text' => "Devi obbligatoriamente impostare il tuo username nelle impostazioni di Telegram",'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$log=$today.",".$todayd. ",nousernameset," .$chat_id.",".$username.",".$user_id."\n";
					file_put_contents('/usr/www/piersoft/comunepulitobot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);
					$this->create_keyboard($telegram,$chat_id);
					exit;
				}else
				{
					$text1=strtoupper($username);
					$homepage="";
					// il GDRIVEGID è il gid per un google sheet dove c'è l'elenco degli username registrati.
					$url ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25".$text1;
					$url .="%25%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID;
					$csv = array_map('str_getcsv', file($url));
					$count = 0;
					foreach($csv as $data=>$csv1){
						$count = $count+1;
					}
						if ($count >1 OR $username="piersoft") // inserire l'admin abilitato alla cancellazione oltre l'utente
							{
					$statement = "DELETE FROM ".DB_TABLE_GEO ." WHERE bot_request_message ='".$text."'";
					$db->exec($statement);
					$reply = "La segnalazione n° ".$text." è stata cancellata";
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
					$log=$today. ",segnalazione cancellata," .$chat_id. "\n";
				}else{
					$content = array('chat_id' => $chat_id, 'text' => $username.", non risulti essere un utente autorizzato ad aggiornare le segnalazioni.",'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$this->create_keyboard($telegram,$chat_id);
					exit;
				}

			}

		}	elseif (strpos($text,'e:') !== false) {
				$text=str_replace("e:","",$text);
				$text=str_replace(" ","",$text);

				if ($username==""){
					$content = array('chat_id' => $chat_id, 'text' => "Devi obbligatoriamente impostare il tuo username nelle impostazioni di Telegram",'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$log=$today.",".$todayd. ",nousernameset," .$chat_id.",".$username.",".$user_id."\n";
					file_put_contents('/usr/www/piersoft/comunepulitobot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);
					$this->create_keyboard($telegram,$chat_id);
					exit;
				}else
				{
					$text1=strtoupper($username);
					$homepage="";
					$url ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25".$text1;
					$url .="%25%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID1;
					$csv = array_map('str_getcsv', file($url));
					$count = 0;
					foreach($csv as $data=>$csv1){
						$count = $count+1;
					}
						if ($count >1)
							{

					$statement = "UPDATE ".DB_TABLE_GEO ." SET aggiornata='evasa' WHERE bot_request_message ='".$text."'";
					$db->exec($statement);
					$reply = "Segnalazione n° ".$text." è stata evasa";
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
					$log=$today. ",segnalazione aggiornata," .$chat_id. "\n";
					$db1 = new SQLite3($db_path);
					$q = "SELECT user,username FROM ".DB_TABLE_GEO ." WHERE bot_request_message='".$text."'";
					$result=	$db1->query($q);
					$row = array();
					$i=0;

					while($res = $result->fetchArray(SQLITE3_ASSOC))
							{

									if(!isset($res['user'])) continue;

									 $row[$i]['user'] = $res['user'];
									 $row[$i]['username'] = $res['username'];

									 $i++;
							 }
							 $content = array('chat_id' => $row[0]['user'], 'text' => $row[0]['username']." la tua segnalazione è stata evasa, ti ringraziamo.",'disable_web_page_preview'=>true);
						 	 $telegram->sendMessage($content);
				}else{
					$content = array('chat_id' => $chat_id, 'text' => $username.", non risulti essere un utente autorizzato ad aggiornare le segnalazioni.",'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$this->create_keyboard($telegram,$chat_id);
					exit;
				}

			}

		}
			elseif (strpos($text,'a:') !== false) {
				$text=str_replace("a:","",$text);
				$text=str_replace(" ","",$text);

				if ($username==""){
					$content = array('chat_id' => $chat_id, 'text' => "Devi obbligatoriamente impostare il tuo username nelle impostazioni di Telegram",'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$log=$today.",".$todayd. ",nousernameset," .$chat_id.",".$username.",".$user_id."\n";
					file_put_contents('/usr/www/piersoft/comunepulitobot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);
					$this->create_keyboard($telegram,$chat_id);
					exit;
				}else
				{
					$text1=strtoupper($username);
					$homepage="";
					$url ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25".$text1;
					$url .="%25%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID1;
					$csv = array_map('str_getcsv', file($url));
					$count = 0;
					foreach($csv as $data=>$csv1){
						$count = $count+1;
					}
						if ($count >1)
							{

					$statement = "UPDATE ".DB_TABLE_GEO ." SET aggiornata='gestita' WHERE bot_request_message ='".$text."'";
					$db->exec($statement);
					$reply = "Segnalazione n° ".$text." è stata aggiornata";
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
					$log=$today. ",segnalazione aggiornata," .$chat_id. "\n";
					$db1 = new SQLite3($db_path);
					$q = "SELECT user,username FROM ".DB_TABLE_GEO ." WHERE bot_request_message='".$text."'";
					$result=	$db1->query($q);
					$row = array();
					$i=0;

					while($res = $result->fetchArray(SQLITE3_ASSOC))
							{

									if(!isset($res['user'])) continue;

									 $row[$i]['user'] = $res['user'];
									 $row[$i]['username'] = $res['username'];

									 $i++;
							 }
							 $content = array('chat_id' => $row[0]['user'], 'text' => $row[0]['username']." la tua segnalazione è stata presa in gestione, ti ringraziamo.",'disable_web_page_preview'=>true);
						 	 $telegram->sendMessage($content);
				}else{
					$content = array('chat_id' => $chat_id, 'text' => $username.", non risulti essere un utente autorizzato ad aggiornare le segnalazioni.",'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$this->create_keyboard($telegram,$chat_id);
					exit;
				}

			}

		}elseif (strpos($text,'😡') !== false || strpos($text,'📕') !== false || strpos($text,'🎢') !== false || strpos($text,'☕️') !== false || strpos($text,'👨‍👨‍👧‍👧') !== false ||strpos($text,'👨‍🎓') !== false ||strpos($text,'🤩') !== false ||strpos($text,'🏫') !== false ||strpos($text,'🏛') !== false || strpos($text,'📗') !== false || strpos($text,'♿️') !== false || strpos($text,'👇') !== false || strpos($text,'👍') !== false||strpos($text,'🌲') !== false ||strpos($text,'💡') !== false ||strpos($text,'🍺') !== false ||strpos($text,'🍕') !== false ||strpos($text,'1️⃣') !== false ||strpos($text,'🏨') !== false) {

			$string="";
			if (strpos($text,'📕') !== false) $string="-";
			if (strpos($text,'📗') !== false) $string="+";

			  $text=str_replace("\n","",$text);
			  $text=str_replace("📕",":",$text);
				$text=str_replace("📗",":",$text);
			  $text=str_replace("👇",":",$text);
			  $text=str_replace("👍",":",$text);
			  $text=str_replace("🏨",":",$text);
			  $text=str_replace("1️⃣",":",$text);
			  $text=str_replace("🍕",":",$text);
			  $text=str_replace("🍺",":",$text);
 			  $text=str_replace("🌲",":",$text);
				$text=str_replace("💡",":",$text);
				$text=str_replace("♿️",":",$text);
				$text=str_replace("👨‍👨‍👧‍👧",":",$text);
				$text=str_replace("👨‍🎓",":",$text);
				$text=str_replace("🤩",":",$text);
				$text=str_replace("🏫",":",$text);
				$text=str_replace("🏛",":",$text);
				$text=str_replace("☕️",":",$text);
				$text=str_replace("🎢",":",$text);
				$text=str_replace("😡",":",$text);

			function extractString($string, $start, $end) {
					$string = " ".$string;
					$ini = strpos($string, $start);
					if ($ini == 0) return "";
					$ini += strlen($start);
					$len = strpos($string, $end, $ini) - $ini;
					return substr($string, $ini, $len);
			}
			$id=extractString($text,":",":");
			$text=str_replace($id,"",$text);
			$text=str_replace(":","",$text);
			$text=str_replace(",","",$text);
			$id=$id.$string;
			$statement = "UPDATE ".DB_TABLE_GEO ." SET categoria='".$id."' WHERE bot_request_message ='".$text."' AND username='".$username."'";
			$db->exec($statement);
			$reply = "La mappatura ".$text." è stata aggiornata con la categoria ".$id;
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$telegram->sendMessage($content);
			exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
			$log=$today. ",forza_debolezza_aggiornata," .$chat_id. "\n";
			$this->create_keyboard($telegram,$chat_id);
			exit;
		}
			elseif (strpos($text,'t:') !== false || strpos($text,'T:') !== false) {
			$text=str_replace("t:",":",$text);
			$text=str_replace("T:",":",$text);
			function extractString($string, $start, $end) {
					$string = " ".$string;
					$ini = strpos($string, $start);
					if ($ini == 0) return "";
					$ini += strlen($start);
					$len = strpos($string, $end, $ini) - $ini;
					return substr($string, $ini, $len);
			}
			$id=extractString($text,":",":");
			$text=str_replace($id,"",$text);
			$text=str_replace(":","",$text);
			$text=str_replace(",","",$text);
			$statement = "UPDATE ".DB_TABLE_GEO ." SET text='".$text."' WHERE bot_request_message ='".$id."' AND username='".$username."'";
//	print_r($reply_to_msg['message_id']);
			$db->exec($statement);
			$reply = "Segnalazione n° ".$id." è stata aggiornata con il testo (solo se sei stato tu l'utente segnalante)";
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$telegram->sendMessage($content);
			exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
			$log=$today. ",segnalazione aggiornata," .$chat_id. "\n";
			$this->create_keyboard($telegram,$chat_id);
			exit;
	}
		//gestione segnalazioni georiferite
		elseif($location!=null)

		{
			if ($username==""){
				$content = array('chat_id' => $chat_id, 'text' => "Devi obbligatoriamente impostare il tuo username nelle impostazioni di Telegram",'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				$log=$today.",".$todayd. ",nousernameset," .$chat_id.",".$username.",".$user_id."\n";
				file_put_contents('/usr/www/piersoft/comunepulitobot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);
				$this->create_keyboard($telegram,$chat_id);
				exit;
			}else
			{
				$text=strtoupper($username);
				$homepage="";
				$url ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25".$text;
				$url .="%25%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID;
				$csv = array_map('str_getcsv', file($url));
				$count = 0;
				foreach($csv as $data=>$csv1){
					$count = $count+1;
				}
					if ($count >1)
						{
							$this->location_manager($username,$db,$telegram,$user_id,$chat_id,$location);
							exit;
							}else{
								$content = array('chat_id' => $chat_id, 'text' => $username.", non risulti essere un utente autorizzato ad inviare le segnalazioni. Compila questo form: https://goo.gl/forms/jHF32JX6K7V2mkkk2.",'disable_web_page_preview'=>true);
								$telegram->sendMessage($content);
								$this->create_keyboard($telegram,$chat_id);
								exit;
							}

			}


		}


else //($reply_to_msg != NULL)
{
if ($reply_to_msg != NULL){

	$response=$telegram->getData();

	$type=$response["message"]["video"]["file_id"];
	$text =$response["message"]["text"];
	$risposta="";
	$file_name="";
	$file_path="";
	$file_name="";


if ($type !=NULL) {

$file_id=$type;
//$text="video allegato";
//$risposta="ID dell'allegato:".$file_id."\n";
$content = array('chat_id' => $chat_id, 'text' => "Non è possibile inviare video direttamente ma devi cliccare \xF0\x9F\x93\x8E e poi File");

//$content = array('chat_id' => $chat_id, 'text' => "per inviare un video devi cliccare \xF0\x9F\x93\x8E e poi File");
$telegram->sendMessage($content);
$this->create_keyboard($telegram,$chat_id);
$statement = "DELETE FROM ". DB_TABLE_GEO ." where bot_request_message = '" . $reply_to_msg['message_id'] . "'";
$db->exec($statement);
exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');

exit;
}

$file_id=$response["message"]["photo"][2]["file_id"];

if ($file_id !=NULL) {

$telegramtk=TELEGRAM_BOT; // inserire il token
$rawData = file_get_contents("https://api.telegram.org/bot".$telegramtk."/getFile?file_id=".$file_id);
$obj=json_decode($rawData, true);
$file_path=$obj["result"]["file_path"];
$caption=$response["message"]["caption"];
if ($caption != NULL) $text=$caption;
$risposta="ID dell'allegato: ".$file_id."\n";

}
$typed=$response["message"]["document"]["file_id"];

if ($typed !=NULL){
$file_id=$typed;
$file_name=$response["message"]["document"]["file_name"];
$text="documento: ".$file_name." allegato";
$risposta="ID dell'allegato:".$file_id."\n";

}

$typev=$response["message"]["voice"]["file_id"];

if ($typev !=NULL){
$file_id=$typev;
$text="audio allegato";
$risposta="ID dell'allegato:".$file_id."\n";
$content = array('chat_id' => $chat_id, 'text' => "Non è possibile inviare file audio");
$telegram->sendMessage($content);
$this->create_keyboard($telegram,$chat_id);
$statement = "DELETE FROM ". DB_TABLE_GEO ." where bot_request_message = '" . $reply_to_msg['message_id'] . "'";
$db->exec($statement);
exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');

exit;
}

$csv_path=CSV_PATH;
$db_path=DB_PATH;
//echo $db_path;
$username=$response["message"]["from"]["username"];
$first_name=$response["message"]["from"]["first_name"];

$db1 = new SQLite3($db_path);
$q = "SELECT lat,lng FROM ".DB_TABLE_GEO ." WHERE bot_request_message='".$reply_to_msg['message_id']."'";
$result=	$db1->query($q);
$row = array();
$i=0;

while($res = $result->fetchArray(SQLITE3_ASSOC))
		{

				if(!isset($res['lat'])) continue;

				 $row[$i]['lat'] = $res['lat'];
				 $row[$i]['lng'] = $res['lng'];
				 $i++;
		 }

if ($row[0]['lat'] == ""){
	$content = array('chat_id' => $chat_id, 'text' => "Errore di georeferenzazione. riprova per cortesia");
	$telegram->sendMessage($content);
	exit;
}
		 //inserisce la segnalazione nel DB delle segnalazioni georiferite
			 $statement = "UPDATE ".DB_TABLE_GEO ." SET text='". $text ."',file_id='". $file_id ."',filename='". $file_name ."',first_name='". $first_name ."',file_path='". $file_path ."',username='". $username ."' WHERE bot_request_message ='".$reply_to_msg['message_id']."'";
			 print_r($reply_to_msg['message_id']);
			 $db->exec($statement);

	  $reply = "La segnalazione n° ".$reply_to_msg['message_id']." è stata registrata.\nGrazie!\n";
 		$reply .= "Puoi visualizzarla su :\nhttp://www.piersoft.it/comunepulitobot/#18/".$row[0]['lat']."/".$row[0]['lng'];
 		$content = array('chat_id' => $chat_id, 'text' => $reply);
 		$telegram->sendMessage($content);

 		$log=$today. ",information for maps recorded," .$chat_id. "\n";

 		exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
		$mappa = "Puoi visualizzarla su :\nhttp://www.piersoft.it/comunepulitobot/#18/".$row[0]['lat']."/".$row[0]['lng'];
		$linkfile="\nScarica foto:\nhttp://www.piersoft.it/comunepulitobot/allegato.php?id=".$file_id;
		if ($file_id==null) $linkfile="";
		$content = array('chat_id' => GRUPPO, 'text' => "Segnalazione in arrivo numero ".$reply_to_msg['message_id']." da parte dell'utente @".$username." il ".$today."\n".$mappa.$linkfile."\n".$text);
		$telegram->sendMessage($content);
	// STANDARD //
	$option = array(["😡Vandalismo\n:".$reply_to_msg['message_id'].":","♿️Buche\n:".$reply_to_msg['message_id'].":"],["🌲Rifiuti\n:".$reply_to_msg['message_id'].":","💡Palo luce\n:".$reply_to_msg['message_id'].":"],["Annulla"]);
	$keyb = $telegram->buildKeyBoard($option, $onetime=true);
		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[guarda la mappa delle segnalazioni su http://www.piersoft.it/comunepulitobot/ oppure aggiungi una categoria:]");
		$telegram->sendMessage($content);

	}
 	//comando errato

 	else{

 		 $reply = "Hai selezionato un comando non previsto. Ricordati che devi prima inviare la tua posizione";
 		 $content = array('chat_id' => $chat_id, 'text' => $reply);
 		 $telegram->sendMessage($content);

 		 $log=$today. ",wrong command sent," .$chat_id. "\n";

 	 }

}
 	//aggiorna tastiera
 	file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
	$statement = "DELETE FROM ". DB_TABLE_GEO ." WHERE username =' '";
	$db->exec($statement);
	exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');

 }



// Crea la tastiera
function create_keyboard($telegram, $chat_id)
 {
	 			$option = array(["❓istruzioni","©️info"]);
				$keyb = $telegram->buildKeyBoard($option, $onetime=true);
				$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[guarda la mappa delle segnalazioni su http://www.piersoft.it/comunepulitobot/ oppure invia la tua segnalazione cliccando \xF0\x9F\x93\x8E]");
				$telegram->sendMessage($content);

 }



   function location_manager($username,$db,$telegram,$user_id,$chat_id,$location)
   	{
   		if ($username==""){
   			$content = array('chat_id' => $chat_id, 'text' => "Devi obbligatoriamente impostare il tuo username nelle impostazioni di Telegram",'disable_web_page_preview'=>true);
   			$telegram->sendMessage($content);
   			$log=$today.",".$todayd. ",nousernameset," .$chat_id.",".$username.",".$user_id."\n";
   			file_put_contents('/usr/www/piersoft/comunepulitobot/db/telegram.log', $log, FILE_APPEND | LOCK_EX);
   			$this->create_keyboard($telegram,$chat_id);
   			exit;
   		}else
   		{
   			$lng=$location["longitude"];
   			$lat=$location["latitude"];


   			$reply="http://nominatim.openstreetmap.org/reverse?email=piersoft2@gmail.com&format=json&lat=".$lat."&lon=".$lng."&zoom=18&addressdetails=1";
   			$json_string = file_get_contents($reply);
   			$parsed_json = json_decode($json_string);
   			//var_dump($parsed_json);
   			$temp_c1 =$parsed_json->{'display_name'};
   			if ($parsed_json->{'address'}->{'city'}) {
   			//  $temp_c1 .="\ncittà: ".$parsed_json->{'address'}->{'city'};

   			}

   			$response=$telegram->getData();

   			$bot_request_message_id=$response["message"]["message_id"];
   			$time=$response["message"]["date"]; //registro nel DB anche il tempo unix

   			$h = "1";// Hour for time zone goes here e.g. +7 or -4, just remove the + or -
   			$hm = $h * 60;
   			$ms = $hm * 60;
   			$timec=gmdate("Y-m-d\TH:i:s\Z", $time+($ms));
   			$timec=str_replace("T"," ",$timec);
   			$timec=str_replace("Z"," ",$timec);
  			$content = array('chat_id' => $chat_id, 'text' => "Cosa vuoi comunicarmi in ".$temp_c1."? (".$lat.",".$lng.")", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
   		  $bot_request_message=$telegram->sendMessage($content);
   		  $forcehide=$telegram->buildForceReply(true);
   		  $content = array('chat_id' => $chat_id, 'text' => "[scrivi il tuo messaggio]", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
  			$bot_request_message=$telegram->sendMessage($content);
   			//memorizzare nel DB
   			$obj=json_decode($bot_request_message);
   			$id=$obj->result;
   			$id=$id->message_id;
  			$temp_c1=str_replace(",","_",$temp_c1);
				$temp_c1=str_replace("'","_",$temp_c1);
   			$statement = "INSERT INTO ". DB_TABLE_GEO. " (lat,lng,user,username,text,bot_request_message,time,file_id,file_path,filename,first_name,luogo) VALUES ('" . $lat . "','" . $lng . "','" . $user_id . "',' ',' ','". $id ."','". $timec ."',' ',' ',' ',' ','" . $temp_c1 . "')";
   			$stmt = $db->exec($statement);
				if (!$stmt) {
				$content = array('chat_id' => $chat_id, 'text' => "errore!!!".$statement,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
			}


   	}

  }
   }

   ?>
