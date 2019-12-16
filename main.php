<?php

/**
 * Telegram Bot example for mapping points noir in Brussels.
 * @author Francesco Piero Paolicelli
 * @author Remo Moro @pagaia
 */
include("./settings_t.php");
include("./Telegram.php");

class mainloop
{
	const MAX_LENGTH = 4096;

	function start($telegram, $update)
	{

		date_default_timezone_set('Europe/Brussels');
		$today = date("Y-m-d H:i:s");
		//$data=new getdata();
		// Instances the class
		$db = new PDO(DB_NAME);

		//   If you need to manually take some parameters
		// 	  $result = $telegram->getData();
		// 	  $text = $result["message"] ["text"];
		// 	  $chat_id = $result["message"] ["chat"]["id"];

		$first_name = $update["message"]["from"]["first_name"];
		$text = $update["message"]["text"];
		$chat_id = $update["message"]["chat"]["id"];
		$user_id = $update["message"]["from"]["id"];
		$location = $update["message"]["location"];
		$reply_to_msg = $update["message"]["reply_to_message"];
		$username = $update["message"]["from"]["username"];

		$this->shell($username, $telegram, $db, $first_name, $text, $chat_id, $user_id, $location, $reply_to_msg);
		//$db = NULL;

	}

	//gestisce l'interfaccia utente
	function shell($username, $telegram, $db, $first_name, $text, $chat_id, $user_id, $location, $reply_to_msg)
	{
		$csv_path = CSV_PATH;
		$db_path = DB_PATH;
		date_default_timezone_set('Europe/Brussels');
		$today = date("Y-m-d H:i:s");

		$log = $today . ",Message: ," . $username . "," .  $text . "," . $chat_id . "," . $user_id . "," . $location . "," . print_r($reply_to_msg, TRUE)  . "\n";
		file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

		// convert text to UPPER CASE only if not a T: command
		if (strpos($text, 'T:') == false) {
			$text = strtoupper($text);
		}

		if ($text == "/START" || $text == "INFO" || $text == "©️INFO") {
			$reply = "Welcome $first_name 
			This Bot has been adapted by @pagaia as support tool to get reports about issues in the city.
			In particular it is used to report sensitive areas/points in some streets for the normal way to and from school in order to help the Filter Cafe Filtre of Etterbeek
			to localize in a simple and shared way the information.
			The author is not responsible for the improper use of this tool and the contents of the users.
			
			Only registered users with a Telegram \"username\" can add report. All reports are registered with the username and can be
			publicily accessed on map with CC0 (public domain) license.
			To partecipate please fill the following form: https://forms.gle/mVjCWrdGbETrhZk76.
				
			The address geocoding is achieved thanks to the OpenStreetMap Nominatim database with oDBL license.
			The map icons have been created by Francesco Lanotte";


			$content = array('chat_id' => $chat_id, 'text' => $reply, 'disable_web_page_preview' => true);
			$telegram->sendMessage($content);

			$forcehide = $telegram->buildKeyBoardHide(true);
			$content = array('chat_id' => $chat_id, 'text' => "", 'reply_markup' => $forcehide, 'reply_to_message_id' => $bot_request_message_id);
			$bot_request_message = $telegram->sendMessage($content);

			$log = $today . ",new chat started," . $chat_id . "\n";
			$this->create_keyboard($telegram, $chat_id);
			exit;
		} elseif ($text == "/LOCATION" || $text == "🌐location") {

			$option = array(
				array($telegram->buildKeyboardButton("Send your location", false, true))
			);
			// Create a permanent custom keyboard
			$keyb = $telegram->buildKeyBoard($option, $onetime = false);
			$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Turn on your GPS");
			$telegram->sendMessage($content);
			exit;
		} else if ($text == "/INSTRUCTION" || $text == "INSTRUCTION" || $text == "❓INSTRUCTION") {

			//	$img = curl_file_create('istruzioni.png', 'image/png');
			//	$contentp = array('chat_id' => $chat_id, 'photo' => $img);
			//	$telegram->sendPhoto($contentp);
			//	$content = array('chat_id' => $chat_id, 'text' => "[Immagine realizzata da Alessandro Ghezzer]");
			//	$telegram->sendMessage($content);
			$content = array('chat_id' => $chat_id, 'text' => "<b>After having sent your position you can add a category</b>\nYou can also add a text to a previous image/file with t:report_number:text\nE.g. <b>t:123:this is a test</b>", 'parse_mode' => "HTML");
			$telegram->sendMessage($content);
			$content = array('chat_id' => $chat_id, 'text' => "To remove a report: <b>delete:report_number</b>\n", 'parse_mode' => "HTML");
			$telegram->sendMessage($content);
			// $link = "http://bit.ly/2N9NDlH";
			// $content = array('chat_id' => $chat_id, 'text' => "<b>Vuoi anche un piccolo video passo passo? clicca </b>" . $link, 'parse_mode' => "HTML");
			// $telegram->sendMessage($content);
			$log = $today . ",instruction," . $chat_id . "\n";
			$this->create_keyboard($telegram, $chat_id);
			exit;
		} elseif ($text == "UPDATEDB") {
			$statement = "DELETE FROM " . DB_TABLE_GEO . " WHERE username =' '";
			$db->exec($statement);
			exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');
			$this->create_keyboard($telegram, $chat_id);
			exit;
		} elseif ($text == "CANCEL") {
			$this->create_keyboard($telegram, $chat_id);
			exit;
		} elseif ($text == "UPDATE" || $text == "/UPDATE" || $text == "❌UPDATE") {

			$reply = "To update a report select a:reportNumber, e.g. a:699";
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$telegram->sendMessage($content);
			$this->create_keyboard($telegram, $chat_id);
			exit;
		} elseif (strpos($text, 'DELETE:') !== false) {
			$text = str_replace("DELETE:", "", $text);
			$text = str_replace(" ", "", $text);

			if ($username == "") {
				$this->returnUsernameError($today, $telegram, $user_id, $chat_id);
			} else {
				$text1 = strtoupper($username);
				$homepage = "";
				// il GDRIVEGID è il gid per un google sheet dove c'è l'elenco degli username registrati.
				$url =  GOOGLE_URL_BASE . "SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25" . $text1;
				$url .= "%25%27%20&gid=" . GDRIVEGIDADMIN;
				$csv = array_map('str_getcsv', file($url));
				$count = 0;
				foreach ($csv as $data => $csv1) {
					$count = $count + 1;
				}
				if ($count > 1 or $username = "pagaia") // inserire l'admin abilitato alla cancellazione oltre l'utente
				{
					$statement = "DELETE FROM " . DB_TABLE_GEO . " WHERE bot_request_message ='" . $text . "'";
					$db->exec($statement);
					$reply = "The report n° " . $text . " has been deleted";
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');
					$log = $today . ",segnalazione cancellata," . $chat_id . "\n";
				} else {
					$this->returnUnauthorized($today, $username, $telegram, $user_id, $chat_id);
				}
			}
		} elseif (strpos($text, 'E:') !== false) {
			$text = str_replace("E:", "", $text);
			$text = str_replace(" ", "", $text);

			if ($username == "") {
				$this->returnUsernameError($today, $telegram, $user_id, $chat_id);
			} else {
				$text1 = strtoupper($username);
				$homepage = "";
				$url =  GOOGLE_URL_BASE . "SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25" . $text1;
				$url .= "%25%27%20&gid=" . GDRIVEGIDADMIN;
				$csv = array_map('str_getcsv', file($url));
				$count = 0;
				foreach ($csv as $data => $csv1) {
					$count = $count + 1;
				}
				if ($count > 1) {

					$statement = "UPDATE " . DB_TABLE_GEO . " SET aggiornata='evasa' WHERE bot_request_message ='" . $text . "'";
					$db->exec($statement);
					$reply = "Reporting n° " . $text . " has been taken into account";
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');
					$log = $today . ",segnalazione aggiornata," . $chat_id . "\n";
					$db1 = new SQLite3($db_path);
					$q = "SELECT user,username FROM " . DB_TABLE_GEO . " WHERE bot_request_message='" . $text . "'";
					$result =	$db1->query($q);
					$row = array();
					$i = 0;

					while ($res = $result->fetchArray(SQLITE3_ASSOC)) {

						if (!isset($res['user'])) continue;

						$row[$i]['user'] = $res['user'];
						$row[$i]['username'] = $res['username'];

						$i++;
					}
					$content = array('chat_id' => $row[0]['user'], 'text' => "Dear " . $row[0]['username'] . " your reporting has been taken into account, thank you.", 'disable_web_page_preview' => true);
					$telegram->sendMessage($content);
				} else {
					$this->returnUnauthorized($today, $username, $telegram, $user_id, $chat_id);
				}
			}
		} elseif (strpos($text, 'A:') !== false) {
			$text = str_replace("A:", "", $text);
			$text = str_replace(" ", "", $text);

			if ($username == "") {
				$this->returnUsernameError($today, $telegram, $user_id, $chat_id);
			} else {
				$text1 = strtoupper($username);
				$homepage = "";
				$url =  GOOGLE_URL_BASE . "SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25" . $text1;
				$url .= "%25%27%20&gid=" . GDRIVEGIDADMIN;
				$csv = array_map('str_getcsv', file($url));
				$count = 0;
				foreach ($csv as $data => $csv1) {
					$count = $count + 1;
				}
				if ($count > 1) {

					$statement = "UPDATE " . DB_TABLE_GEO . " SET aggiornata='gestita' WHERE bot_request_message ='" . $text . "'";
					$db->exec($statement);
					$reply = "Report n° " . $text . " has been updated";
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');
					$log = $today . ",segnalazione aggiornata," . $chat_id . "\n";
					$db1 = new SQLite3($db_path);
					$q = "SELECT user,username FROM " . DB_TABLE_GEO . " WHERE bot_request_message='" . $text . "'";
					$result =	$db1->query($q);
					$row = array();
					$i = 0;

					while ($res = $result->fetchArray(SQLITE3_ASSOC)) {

						if (!isset($res['user'])) continue;

						$row[$i]['user'] = $res['user'];
						$row[$i]['username'] = $res['username'];

						$i++;
					}
					$content = array('chat_id' => $row[0]['user'], 'text' => $row[0]['username'] . " your report has been taken into account, thank you.", 'disable_web_page_preview' => true);
					$telegram->sendMessage($content);
				} else {
					$this->returnFormToFill($today, $username, $telegram, $user_id, $chat_id);
					$content = array('chat_id' => $chat_id, 'text' => $username . ", you don't seem to be an autorized to update reports.", 'disable_web_page_preview' => true);
					$telegram->sendMessage($content);
					$this->create_keyboard($telegram, $chat_id);
					exit;
				}
			}
		} elseif (strpos($text, '😡') !== false || strpos($text, '📕') !== false || strpos($text, '🎢') !== false || strpos($text, '☕️') !== false || strpos($text, '👨‍👨‍👧‍👧') !== false || strpos($text, '👨‍🎓') !== false || strpos($text, '🤩') !== false || strpos($text, '🏫') !== false || strpos($text, '🏛') !== false || strpos($text, '📗') !== false || strpos($text, '♿️') !== false || strpos($text, '👇') !== false || strpos($text, '👍') !== false || strpos($text, '🌲') !== false || strpos($text, '💡') !== false || strpos($text, '🍺') !== false || strpos($text, '🍕') !== false || strpos($text, '1️⃣') !== false || strpos($text, '🏨') !== false) {

			$string = "";
			if (strpos($text, '📕') !== false) $string = "-";
			if (strpos($text, '📗') !== false) $string = "+";

			$text = str_replace("\n", "", $text);
			$text = str_replace("📕", ":", $text);
			$text = str_replace("📗", ":", $text);
			$text = str_replace("👇", ":", $text);
			$text = str_replace("👍", ":", $text);
			$text = str_replace("🏨", ":", $text);
			$text = str_replace("1️⃣", ":", $text);
			$text = str_replace("🍕", ":", $text);
			$text = str_replace("🍺", ":", $text);
			$text = str_replace("🌲", ":", $text);
			$text = str_replace("💡", ":", $text);
			$text = str_replace("♿️", ":", $text);
			$text = str_replace("👨‍👨‍👧‍👧", ":", $text);
			$text = str_replace("👨‍🎓", ":", $text);
			$text = str_replace("🤩", ":", $text);
			$text = str_replace("🏫", ":", $text);
			$text = str_replace("🏛", ":", $text);
			$text = str_replace("☕️", ":", $text);
			$text = str_replace("🎢", ":", $text);
			$text = str_replace("😡", ":", $text);


			$id = $this->extractString($text, ":", ":");
			$text = str_replace($id, "", $text);
			$text = str_replace(":", "", $text);
			$text = str_replace(",", "", $text);
			$id = $id . $string;
			$statement = "UPDATE " . DB_TABLE_GEO . " SET categoria='" . $id . "' WHERE bot_request_message ='" . $text . "' AND username='" . $username . "'";
			$db->exec($statement);
			$reply = "La mappatura " . $text . " è stata aggiornata con la categoria " . $id;
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$telegram->sendMessage($content);
			exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');
			$log = $today . ",forza_debolezza_aggiornata," . $chat_id . "\n";
			$this->create_keyboard($telegram, $chat_id);
			exit;
		} elseif (strpos($text, 'T:') !== false) {
			$text = str_replace("T:", ":", $text);
			$id = $this->extractString($text, ":", ":");

			$text = str_replace($id, "", $text);
			$text = str_replace(":", "", $text);
			$text = str_replace(",", "", $text);
			$statement = "UPDATE " . DB_TABLE_GEO . " SET text='" . $text . "' WHERE bot_request_message ='" . $id . "' AND username='" . $username . "'";
			//	print_r($reply_to_msg['message_id']);
			$db->exec($statement);
			$reply = "Report n° " . $id . " has been updated with text (only if you are the reporter)";
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$telegram->sendMessage($content);
			exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');
			$log = $today . ",segnalazione aggiornata," . $chat_id . "\n";
			$this->create_keyboard($telegram, $chat_id);
			exit;
		}
		//gestione segnalazioni georiferite
		elseif ($location != null) {
			if ($username == "") {
				$this->returnUsernameError($today, $telegram, $user_id, $chat_id);
			} else {
				$text = strtoupper($username);
				$homepage = "";
				$url =  GOOGLE_URL_BASE . "SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25" . $text;
				$url .= "%25%27%20&gid=" . GDRIVEGID;
				$log = $today . ",GOOGLE: ," . $url . " " . $chat_id . "," . $username . "," . $user_id . "\n";
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

				$csv = array_map('str_getcsv', file($url));
				$count = 0;
				foreach ($csv as $data => $csv1) {
					$count = $count + 1;
				}
				if ($count > 1) {
					$this->location_manager($username, $db, $telegram, $user_id, $chat_id, $location);
					exit;
				} else {
					$this->returnFormToFill($today, $username, $telegram, $user_id, $chat_id);
				}
			}
		} else {
			if ($reply_to_msg != NULL) {

				$response = $telegram->getData();

				$log = $today . ",response," . $chat_id . ", " . $username . "," . $user_id . "," .  print_r($response, TRUE) . "\n";
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

				$type = $response["message"]["video"]["file_id"];
				$text = $response["message"]["text"];
				$risposta = "";
				$file_name = "";
				$file_path = "";
				$file_name = "";


				if ($type != NULL) {

					$file_id = $type;
					//$text="video allegato";
					//$risposta="ID dell'allegato:".$file_id."\n";
					$content = array('chat_id' => $chat_id, 'text' => "Is not possible to send video directly, first you have to select \xF0\x9F\x93\x8E and then File");
					$telegram->sendMessage($content);
					$this->create_keyboard($telegram, $chat_id);
					$statement = "DELETE FROM " . DB_TABLE_GEO . " where bot_request_message = '" . $reply_to_msg['message_id'] . "'";
					$db->exec($statement);
					exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');

					exit;
				}

				$file_id = $response["message"]["photo"][2]["file_id"];

				if ($file_id != NULL) {
					$rawData = file_get_contents("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getFile?file_id=" . $file_id);
					$obj = json_decode($rawData, true);
					$file_path = $obj["result"]["file_path"];
					$caption = $response["message"]["caption"];
					if ($caption != NULL) $text = $caption;
					$risposta = "Attachment ID: " . $file_id . "\n";
				}
				$typed = $response["message"]["document"]["file_id"];

				if ($typed != NULL) {
					$file_id = $typed;
					$file_name = $response["message"]["document"]["file_name"];
					$text = "attached document : " . $file_name;
					$risposta = "attachment ID:" . $file_id . "\n";
				}

				$typev = $response["message"]["voice"]["file_id"];

				if ($typev != NULL) {
					$file_id = $typev;
					$text = "attached audio";
					$risposta = "attachment ID:" . $file_id . "\n";
					$content = array('chat_id' => $chat_id, 'text' => "It is not possible to send audio file");
					$telegram->sendMessage($content);
					$this->create_keyboard($telegram, $chat_id);
					$statement = "DELETE FROM " . DB_TABLE_GEO . " where bot_request_message = '" . $reply_to_msg['message_id'] . "'";
					$db->exec($statement);
					exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');

					exit;
				}

				$csv_path = CSV_PATH;
				$db_path = DB_PATH;
				//echo $db_path;
				$username = $response["message"]["from"]["username"];
				$first_name = $response["message"]["from"]["first_name"];

				$db1 = new SQLite3($db_path);
				$q = "SELECT lat,lng FROM " . DB_TABLE_GEO . " WHERE bot_request_message='" . $reply_to_msg['message_id'] . "'";
				$result =	$db1->query($q);
				$row = array();
				$i = 0;

				while ($res = $result->fetchArray(SQLITE3_ASSOC)) {

					if (!isset($res['lat'])) continue;

					$row[$i]['lat'] = $res['lat'];
					$row[$i]['lng'] = $res['lng'];
					$i++;
				}

				if ($row[0]['lat'] == "") {
					$content = array('chat_id' => $chat_id, 'text' => "Geocoding error. Please try again.");
					$telegram->sendMessage($content);
					exit;
				}
				//inserisce la segnalazione nel DB delle segnalazioni georiferite
				$statement = "UPDATE " . DB_TABLE_GEO . " SET text='" . $text . "',file_id='" . $file_id . "',filename='" . $file_name . "',first_name='" . $first_name . "',file_path='" . $file_path . "',username='" . $username . "' WHERE bot_request_message ='" . $reply_to_msg['message_id'] . "'";
				print_r($reply_to_msg['message_id']);
				$db->exec($statement);

				$reply = "Report n° " . $reply_to_msg['message_id'] . " has been registered.\nThank you!\n";
				$reply .= "You can see it at :\n" . SERVER . "/#18/" . $row[0]['lat'] . "/" . $row[0]['lng'];
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);

				$log = $today . ",information for maps recorded," . $chat_id . "\n";

				exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');
				$mappa = "You can see it on :\n" . SERVER . "/#18/" . $row[0]['lat'] . "/" . $row[0]['lng'];
				$linkfile = "\nDownload image:\n" . SERVER . "/allegato.php?id=" . $file_id;
				if ($file_id == null) $linkfile = "";
				$content = array('chat_id' => GRUPPO, 'text' => "A new report is coming n. " . $reply_to_msg['message_id'] . " from user  @" . $username . " on " . $today . "\n" . $mappa . $linkfile . "\n" . $text);
				$telegram->sendMessage($content);

				// STANDARD //
				$option = array(["😡Vandalismo\n:" . $reply_to_msg['message_id'] . ":", "♿️Buche\n:" . $reply_to_msg['message_id'] . ":"], ["🌲Rifiuti\n:" . $reply_to_msg['message_id'] . ":", "💡Palo luce\n:" . $reply_to_msg['message_id'] . ":"], ["Cancel"]);
				$keyb = $telegram->buildKeyBoard($option, $onetime = true);
				$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[see the reports map at " . SERVER . " or add a category:]");
				$telegram->sendMessage($content);
			}
			//comando errato
			else {

				$reply = "Command not recognized. Please send first your position";
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);

				$log = $today . ",wrong command sent," . $chat_id . "\n";
			}
		}
		//aggiorna tastiera
		file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
		$statement = "DELETE FROM " . DB_TABLE_GEO . " WHERE username =' '";
		$db->exec($statement);
		exec(' sqlite3 -header -csv ' . $db_path . ' "select * from segnalazioni;" > ' . $csv_path . ' ');
	}

	function extractString($string, $start, $end)
	{
		$string = " " . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return "";
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}


	// Crea la tastiera
	function create_keyboard($telegram, $chat_id)
	{
		$option = array(["❓instruction", "©️info"]);
		$keyb = $telegram->buildKeyBoard($option, $onetime = true);
		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[see reports map on " . SERVER . "/ or send your report selecting \xF0\x9F\x93\x8E]");
		$telegram->sendMessage($content);
	}


	// this function return the username error
	function returnUsernameError($today, $telegram, $user_id, $chat_id)
	{
		$content = array('chat_id' => $chat_id, 'text' => "You have to set your username into Telegram settings", 'disable_web_page_preview' => true);
		$telegram->sendMessage($content);
		$log = $today . ",nousernameset," . $chat_id . ", NULL," . $user_id . "\n";
		file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
		$this->create_keyboard($telegram, $chat_id);
		exit;
	}

	/**
	 * return unauthorised user message and exit
	 */
	function returnUnauthorized($today, $username, $telegram, $user_id, $chat_id)
	{
		$content = array('chat_id' => $chat_id, 'text' => $username . ", you don't seem to be an autorized to update reports.", 'disable_web_page_preview' => true);
		$telegram->sendMessage($content);
		$log = $today . ",unauthorised," . $chat_id . ", " . $username . "," . $user_id . "\n";
		file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
		$this->create_keyboard($telegram, $chat_id);
		exit;
	}

	// this function return the username error
	function returnFormToFill($today, $username, $telegram, $user_id, $chat_id)
	{
		$content = array('chat_id' => $chat_id, 'text' => $username . ", you don't appear to be a user authorized to send reports. Fill out this form: https://forms.gle/mVjCWrdGbETrhZk76", 'disable_web_page_preview' => true);
		$telegram->sendMessage($content);
		$log = $today . ",not autorized," . $chat_id . ", " . $username . "," . $user_id . "\n";
		file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
		$this->create_keyboard($telegram, $chat_id);
		exit;
	}


	function location_manager($username, $db, $telegram, $user_id, $chat_id, $location)
	{
		date_default_timezone_set('Europe/Brussels');
		$today = date("Y-m-d H:i:s");
		if ($username == "") {
			$this->returnUsernameError($today, $telegram, $user_id, $chat_id);
		} else {
			$lng = $location["longitude"];
			$lat = $location["latitude"];


			$reply = "http://nominatim.openstreetmap.org/reverse?email=piersoft2@gmail.com&format=json&lat=" . $lat . "&lon=" . $lng . "&zoom=18&addressdetails=1";
			$json_string = file_get_contents($reply);
			$parsed_json = json_decode($json_string);
			//var_dump($parsed_json);
			$temp_c1 = $parsed_json->{'display_name'};
			if ($parsed_json->{'address'}->{'city'}) {
				//  $temp_c1 .="\ncittà: ".$parsed_json->{'address'}->{'city'};

			}

			$response = $telegram->getData();

			$bot_request_message_id = $response["message"]["message_id"];
			$time = $response["message"]["date"]; //registro nel DB anche il tempo unix

			$h = "1"; // Hour for time zone goes here e.g. +7 or -4, just remove the + or -
			$hm = $h * 60;
			$ms = $hm * 60;
			$timec = gmdate("Y-m-d\TH:i:s\Z", $time + ($ms));
			$timec = str_replace("T", " ", $timec);
			$timec = str_replace("Z", " ", $timec);
			$content = array('chat_id' => $chat_id, 'text' => "What you want to say at  " . $temp_c1 . "? (" . $lat . "," . $lng . ")", 'reply_markup' => $forcehide, 'reply_to_message_id' => $bot_request_message_id);
			$bot_request_message = $telegram->sendMessage($content);
			$forcehide = $telegram->buildForceReply(true);
			$content = array('chat_id' => $chat_id, 'text' => "[write your message]", 'reply_markup' => $forcehide, 'reply_to_message_id' => $bot_request_message_id);
			$bot_request_message = $telegram->sendMessage($content);
			//memorizzare nel DB
			$obj = json_decode($bot_request_message);
			$id = $obj->result;
			$id = $id->message_id;
			$temp_c1 = str_replace(",", "_", $temp_c1);
			$temp_c1 = str_replace("'", "_", $temp_c1);
			$statement = "INSERT INTO " . DB_TABLE_GEO . " (lat,lng,user,username,text,bot_request_message,time,file_id,file_path,filename,first_name,luogo) VALUES ('" . $lat . "','" . $lng . "','" . $user_id . "',' ',' ','" . $id . "','" . $timec . "',' ',' ',' ',' ','" . $temp_c1 . "')";
			$stmt = $db->exec($statement);
			if (!$stmt) {
				$content = array('chat_id' => $chat_id, 'text' => "Error!!!" . $statement, 'disable_web_page_preview' => true);
				$telegram->sendMessage($content);
			}
		}
	}
}
