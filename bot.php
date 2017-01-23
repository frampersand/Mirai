<?php
header('Content-Type: text/html; charset=UTF-8');
define('AFLV_URL', 'http://185.104.152.204/~animeflv'); 
define('BOT_ID', '/***** TELEGRAM BOT ID *****/');
require("telegram.php");
require("mirai.php");
require("db.php");

$telegram = new Telegram(BOT_ID);
$mirai = new Mirai();

$result = $telegram->getData();

if(empty($result["message"]["text"])){
	$details = $mirai->routineCheck();
	$responses = $mirai->getMessage($details);
	
	foreach($responses as $key=>$item){
		$title = $details[$key]["title"];
		echo "Sending {$title}";

		$query = "SELECT * FROM `anime` WHERE `title` = '{$title}'";
		$sql = $db->query($query);
		if($sql->num_rows > 0) {
			$fetch = $sql->fetch_assoc();
			$img = $fetch["img"];
			$insertnew = false;
		}else{
			$filename = $mirai->replace_sp_chars($title);
			$filename = $mirai->sp_char($filename);
			$fileimg = $mirai->saveimg($details[$key]["img"], $filename);
			$img = curl_file_create('images/'.$filename.'.jpg','image/jpg');
			$insertnew = true;
		}

		$sql->free();
		$keyb = json_encode($item["keyboard"]);

		$content = array('chat_id' => "/*** TELEGRAM RECEIVER ID ***/", 'photo' => $img, 'caption' => $item["text"], "reply_markup" => $keyb);
		$status = $telegram->sendPhoto($content);

		if($insertnew){
			$photoid = $status["result"]["photo"][1]["file_id"];
			$query = "INSERT INTO `anime`(`title`, `img`) VALUES ('{$title}','{$photoid}')";
			$sql = $db->query($query);
		}
 		echo "<hr>";
	}
}
else{
	$response = $mirai->processMessage($result, $db);
	$content = array('chat_id' => $result["message"]["chat"]["id"], 'text' => $response, "parse_mode" => 'html');	
	$telegram->sendMessage($content);
}
?>