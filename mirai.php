<?php
class Mirai {
	
	public function processMessage($result, $db) {
		$msg = $result["message"]["text"];
		if($msg{0} == '/') {
			$c = explode(' ', $msg);
			switch(strtolower(trim($c[0]))) {
				case '/start':
					$text = "Greetings human.\n\nMy name is Mirai, and my function is to keep you updated with the new releases at the animeflv website. If you register (with the /register command), as soon as I check something new over there, I'll tell you about it. See you soon\n\n<i>Mata ne!</i>~\n________\n\n";
					$text .= "Saludos human@\n\nMy nombre es Mirai y mi función es mantenerte informad@ con las nuevas cosas que aparezcan en el sitio web de animeflv. Si te registras (usando el comando /register), tan pronto como yo vea algo nuevo, te lo haré saber. Nos veremos.\n\n<i>Mata ne!</i>~";
					return $text;
					break;
					
				case '/help':
					$text = "My main task is to alert about new anime episodes coming up at the animeflv website and give details about it (as much as I can, though). To register, use the /register command. Don't worry, soon I'll have more options, my master is working on it.\n________\n\n";
					$text .= "Mi principal tarea es avisar acerca de los nuevos episodios de anime que salgan en el sitio de animeflv y dar detalles sobre él (al menos tantos como pueda). Para registrarte usa el comando /register. No te preocupes, dentro de poco tendré más opciones, mi maestro está trabajando en ello.";
					return $text;
					break;
					
				case '/register':
					$text = '';
					if($result["message"]["chat"]["type"] == "private"){
						$query = "SELECT * FROM `users` WHERE `telegram_id` = '{$result["message"]["chat"]["id"]}'";
						$sql = $db->query($query);
						if($sql->num_rows > 0) {
							$text = "Sorry @{$result["message"]["chat"]["username"]}, I already have you registered.\n________\n\n";
							$text .= "Disculpa @{$result["message"]["chat"]["username"]}, ya te tengo registrado.";
						}else{
							$telegram_id = $db->real_escape_string($result["message"]["chat"]["id"]);
							$telegram_name = $db->real_escape_string($result["message"]["chat"]["username"]);
							$query = "INSERT INTO `users` VALUES (NULL, '{$telegram_id}', '{$telegram_name}')";
							$db->query($query);
							$text = "Done @{$result["message"]["chat"]["username"]}, I'll update you when new anime comes out.\n________\n\n";
							$text .= "Listo @{$result["message"]["chat"]["username"]}, te avisaré cuando salgan nuevos episodios de anime.";
						}
					}else{
						$text = "I'm sorry, I can't be on groups.\n________\n\n";
						$text .= "Lo siento, no puedo estar en grupos.";
					}
					return $text;
					break;
					
				default:
					$text = "I.. can't understand that command.\n\n<i>Sumimasen</i>~\n________\n\n";
					$text .= "No puedo entender ese comando.. \n\n<i>Sumimasen</i>~";
					return $text;
					break;
			}
		}
		else{
			$text = "I'm sorry, my master doesn't let me talk with strangers at this moment :(\n________\n\n";
			$text .= "Lo siento, mi maestro no me deja hablar con extraños en este momento :(";
			return $text;
		}		
	}
	
	public function routineCheck(){
		$content = @file_get_contents(AFLV_URL);
		if($content === FALSE) { 
			echo "乁( ◔ ౪◔)ㄏ Oh no, the lolcats went completely bonkers ༼☯﹏☯༽ Specifically at the very beginning <hr>";
		}
		else{
			preg_match_all("/<a href=\"\/ver\/(.*?)\" title=\"(.*?)\">/i", $content, $results);
			array_shift($results);
			$check = file_get_contents("checked.txt");
			$list = $this->getList($results, $check);
			$details = $this->getDetails($list);
			return $details;
		}
	}
	
	public function getMessage($array){
		$response = array();
		foreach($array as $key=>$item){
				if (date('H') > 2 && date('H') < 8)
						$text = "Ohaiyo~\n";
					elseif(date('H') >= 8 && date('H') < 14)
						$text = "Konnichiwa~\n";
					else
						$text = "Konbanwa~\n";
				if($array["episode"] == "1")
					$text .= "Subarashii!\n ¡Master, lo siguiente acaba de estrenarse!:\n\n";
				else
					$text .= "Master, lo siguiente acaba de salir:\n\n";
				$anime_title = $this->sp_char($item["title"]);
				$text .= "• {$anime_title} {$item["episode"]}\n";
				$genres = "";
				foreach($item["genre"] as $g){
					$genres .= $g.", ";
				}
				$genres = rtrim($genres, ', ');
			
				if($item["genre"] != "")
					$text .= "• {$genres}\n";
				if($item["type"] != "")
					$text .= "• {$item["type"]}\n";
				$response[$key]["keyboard"] = [
                	'inline_keyboard' => [
						[
							[
							'text' =>  "AnimeFLV", 
							'url' => "http://animeflv.net/ver/{$item["link"]}"
							]
						],
           			]
				];
				if(!empty($item["mal"]) || $item["mal"] == "N/A"){
					$response[$key]["keyboard"]["inline_keyboard"][] = [
							[
							'text' =>  "MyAnimeList", 
							'url' => $item["mal"],
							]
						];
				}
				$response[$key]["text"] = $text;
			}
		return $response;
	}
	
	private function getList($array, $check){
		$list = array();
		$len = count($array[0]);
		for($count = 0; $count < $len; $count++){
			$link = $array[0][$count]; 
			$title = html_entity_decode($array[1][$count]);
			if($title == $check){
				break;
			}
			$linkcont = @file_get_contents(AFLV_URL."/ver/{$link}");
			preg_match_all("/<a href=\"(.*?)\" class=\"lista_episodios\"/i", $linkcont, $animedata);
			$animelink = AFLV_URL."{$animedata[1][0]}";
			$anime = @file_get_contents($animelink);
			preg_match_all("/<h1>(.*?)<\/h1>/i", $anime, $animeresult);
			$titleurl = urlencode($this->sp_char($animeresult[1][0]));
			$type = explode("/", $animedata[1][0]);
			switch($type[1]){
				case "anime":
					$atype = "TV";
					break;
				case "pelicula":
					$atype = "Movie";
					break;
				case "ova":
					$atype = "OVA|ONA";
					break;
				default: 
					$atype = "TV";
			}
			$title = explode(" ", $title);
			$ep_num = array_reverse($title);
			$ep_num = $ep_num[0];
			array_pop($title);
			$title = implode(" ", $title);
			$list[] = [
				"title" => $title,
				"link" => $link,
				"type" => $atype,
				"episode" => $ep_num
			];
		}
		file_put_contents("checked.txt", html_entity_decode($array[1][0]));
		return $list;
	}
	
	private function getDetails($array){
		$mal_base = "https://myanimelist.net/anime.php?q=";
		$list = array();
		foreach($array as $item){
			$t = $item["title"];
			$y = $item["type"];
			$l = $item["link"];
			$e = $item["episode"];

			$search_title = urlencode(str_replace(":", "", $t));
			$content = @file_get_contents($mal_base.$search_title);
			
			if($content === FALSE) { 
				echo "乁( ◔ ౪◔)ㄏ Oh no, the lolcats went completely bonkers ༼☯﹏☯༽ Specifically at the MAL part ({$mal_base}{$t}) <hr>";
				$list[] = [
					"title" => $t,
					"episode" => $e,
					"link" => $l,
					"mal" => "N/A",
					"genre" => "N/A",
					"type" => $y,
					"img" => "/***** LOCAL SERVER ADDRESS *****//images/placeholder.jpg"
				];
			}
			else{
				$content = str_replace(array("\t","\r","\n"), "", $content);
				preg_match_all("/<div class=\"js-categories-seasonal js-block-list list\">(.*?)<\/div><\/div><\/div>/i", $content, $results);
				array_shift($results);
				preg_match_all("/<tr>.*?<td.*?>.*?<\/td>.*?<td.*?>.*?<\/div><a.*?href=\"(.*?)\".*?<strong>(.*?)<\/strong>.*?<\/td>.*?<td.*?>(.*?)<\/td>.*?<td.*?>.*?<\/td>.*?<td.*?>.*?<\/td>.*?<\/tr>/i", $results[0][0], $results);
				array_shift($results);

				$count = count($results[0]);
				for($i=0;$i<$count;$i++){
					if(strpos($results[2][$i], $y) !== false && $this->sp_char($t) == $this->sp_char($results[1][$i]) || strpos($this->sp_char($t), $this->sp_char($results[1][$i])) !== false){
						$result_link = $results[0][$i];
						$result_title = $results[1][$i];
						break;
					}
					else{
						$result_link = $results[0][0];
						$result_title = $results[1][0];
					}
				}
				$mal_content = @file_get_contents($result_link);
				$mal_content = str_replace(array("\t","\r","\n"), "", $mal_content);

				preg_match_all("/<span class=\"dark_text\">Genres:<\/span>(.*?)<\/div>/i", $mal_content, $genres_d);
				array_shift($genres_d);
				preg_match_all("/<a.*?>(.*?)<\/a>/i", $genres_d[0][0], $genres);
				array_shift($genres);
				$genres = $genres[0];

				preg_match_all("/<div style=\"text-align: center;\">.*?<a href=.*?>.*?<img src=\"(.*?)\".*?>.*?<\/a>.*?<\/div>/i", $mal_content, $result_img);
				array_shift($result_img);
				$result_img = $result_img[0][0];

				$list[] = [
					"title" => $result_title,
					"episode" => $e,
					"link" => $l,
					"mal" => $result_link,
					"genre" => $genres,
					"type" => $y,
					"img" => $result_img
				];
				
				unset($result_link);
				unset($result_title);
				unset($genres);
				unset($result_img);
			}

		}
		return $list;
	}
	
	public function sp_char($string) {
		$string = preg_replace_callback('/&#(\d+);/', function($char) {
			return chr(intval($char[1]));
		}, $string);
		return html_entity_decode($string);
	}
	
	public function saveimg($img,$name){
		$split_image = pathinfo($img);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL , $img);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$response= curl_exec ($ch);
		curl_close($ch);
		$file_name = "images/".$name.".".$split_image['extension'];
		$file = fopen($file_name , 'w') or die("Big nope, no img for you.");
		fwrite($file, $response);
		fclose($file);
		return $split_image;
	}
	
	public function replace_sp_chars($string){
		$string = trim($string);
		$string = str_replace(array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'), array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'), $string);
		$string = str_replace(array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'), array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'), $string);
		$string = str_replace(array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'), array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),	$string);
		$string = str_replace(array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'), array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'), $string);
		$string = str_replace(array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'), array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'), $string);
		$string = str_replace(array('ñ', 'Ñ', 'ç', 'Ç'), array('n', 'N', 'c', 'C',), $string);

		//Esta parte se encarga de eliminar cualquier caracter extraño
		$string = str_replace(
		array("º", "-", "|", "!", '"', "·", "$", "%", "&", "/", "(", ")", "?", "'", "[", "^", "<code>", "]", "+", "}", "{", "¨", "´", ">", "< ", ";", ",", ":", "."), '', $string);
		return $string;
	}
}
?>