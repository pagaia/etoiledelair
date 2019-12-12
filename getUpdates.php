#!/usr/bin/php

<?php
/**
 * Telegram Bot Example whithout WebHook.
 * It uses getUpdates Telegram's API.
 * designed starting from https://github.com/Eleirbag89/TelegramBotPHP
 */

include("./main.php");

//aggiorna con getUpdates
function getUpdates($telegram)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	$update_manager = new mainloop();
	$db = new PDO(DB_NAME);

	// Get all the new updates and set the new correct update_id
	$req = $telegram->getUpdates();

	for ($i = 0; $i < $telegram->UpdateCount(); $i++) {
		// You NEED to call serveUpdate before accessing the values of message in Telegram Class
		$telegram->serveUpdate($i);
		$username = $telegram->Username();
		$first_name = $telegram->FirstName();
		$text = $telegram->Text();
		$chat_id = $telegram->ChatID();
		$user_id = $telegram->User_id();
		$location = $telegram->Location();
		$reply_to_msg = $telegram->ReplyToMessage();
		$update_manager->shell($username, $telegram, $db, $first_name, $text, $chat_id, $user_id, $location, $reply_to_msg);
	}
}

?>