<?php
	
// +------------------------------------------------------------+
// |  			  	 		          				            |
// | 			 	         						            |
// |		    											    |
// | 			 	         						            |
// |	 								                  	    |
// | 			 	         						            |
// +------------------------------------------------------------+

	// ********************************************************************************
	// INCLUDE HEADERS + SECURE 
	
	include '../headers/tools.h';
	
	include '../headers/sessions.h';
	session_start();

	if(!php_req_valid()) return;
	
	//secure_inputs();
	
	$posted_news = "";
	$picture = "";
	$posted_video = "";
	$video = "";
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"])){
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	}else {
		debug("send_news","warning","session empty userid value");
		$answer['news_sent'] = false;
		echo json_encode($answer);
		return;
	}
	
	if(isset($_POST['data']))
		$posted_news = $_POST['data'];
	
	if($posted_news ==""){
		debug("send_news","warning","data is empty");
		return;
	}
	
	// ********************************************************************************
	// GET USER NAME / DATA / PROFILE PICTURE / TOPSONG
	
	$conn = connect_w1();	
	
	$sql = "SELECT NAME_S, DATA_S, PICTURES->'$.\"profilePicture\"' as PICTURE, DATA_S->'$.\"private-data\".topSong' as TOPSONG FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S='$userid' AND STATUS_ID = 1";	
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
			debug("login","warning","mysql error: ".mysqli_error($conn));
			$answer['auth'] = false;
			echo json_encode($answer);
			return;
		}	
	
	$row = mysqli_fetch_array($res);
	if(!$row){
			$answer['auth'] = false;
			$answer['reason'] = 1;
			echo json_encode($answer);
			debug("send_news","warning","user not found");
			return;
		}

	// ********************************************************************************
	// INIT VARIABLES
	
	$parms = json_decode($row['DATA_S'],true);	
	
	$picture = substr($row['PICTURE'], 1, -1);
	
	$topSong = json_decode($row['TOPSONG'], true);		
	
	$name = $row['NAME_S'];	
	
	$text = $posted_news['text'];
	
	$posted_video =	$posted_news['videoId'];
	
	if($posted_video!=""){
	
		$video = '"videoId":"'.$posted_video.'",';
		
	}
	
	// ********************************************************************************
	// CREATE IMAGE GALERIE
	
	$galerie = "";
		
	if($posted_news['images'][0] != null){
		$galerie = $posted_news['images'][0];
	}
	
	if(isset($posted_news['images'][1])){
		$galerie .= '#splt#'.$posted_news['images'][1];
	}
	if(isset($posted_news['images'][2])){
		$galerie .= '#splt#'.$posted_news['images'][2];
	}	
	
	
	
	
	if($posted_news['images'][0] != null){
	
			// ********************************************************************************
			// GENERATE PATH TO MEDIA
			
			$keyLength = 16;
			$folderKey = date('dmY');
			$folderKey .= bin2hex(random_bytes($keyLength));
			$prflKey = bin2hex(random_bytes($keyLength));
			$prflDirectory = "../images/".$folderKey;
			$prflFullPath = $prflDirectory.'/'.$prflKey.'.jpg';
			
			$absolutePath = 'https://ws.lifistic.com/app/freemium/images/'.$folderKey.'/'.$prflKey.'.jpg';
			
			// ********************************************************************************
			// CREATE DEDICATED FOLDER
			
			if(!mkdir($prflDirectory, 0777, true)){
				$answer['update'] = false;
				$answer['reason'] = 3;			
				debug("update_profile_picture","warning","Cannot create folder");
				echo json_encode($answer);
				return;
			}	
			
			// ********************************************************************************	
			// DECODE B64
			
			$image_parts = explode(";base64,", $posted_news['images'][0]);
			$image_type_aux = explode("image/", $image_parts[0]);
			$image_type = $image_type_aux[1];
			$image_base64 = base64_decode($image_parts[1]);
			
			// ********************************************************************************	
			// MOVE FILE TO DEDICATED FOLDER

			if(!file_put_contents($prflFullPath, $image_base64)){
				$answer['update'] = false;
				$answer['reason'] = 4;			
				debug("update_profile_picture","warning","Cannot move file");
				echo json_encode($answer);
				return;	
			}
			
			$finalPath = $absolutePath;
	
	}
	
	if(isset($posted_news['images'][1])){
	
			// ********************************************************************************
			// GENERATE PATH TO MEDIA
			
			$keyLength = 16;
			$folderKey = date('dmY');
			$folderKey .= bin2hex(random_bytes($keyLength));
			$prflKey = bin2hex(random_bytes($keyLength));
			$prflDirectory = "../images/".$folderKey;
			$prflFullPath = $prflDirectory.'/'.$prflKey.'.jpg';
			
			$absolutePath = 'https://ws.lifistic.com/app/freemium/images/'.$folderKey.'/'.$prflKey.'.jpg';
			
			// ********************************************************************************
			// CREATE DEDICATED FOLDER
			
			if(!mkdir($prflDirectory, 0777, true)){
				$answer['update'] = false;
				$answer['reason'] = 3;			
				debug("update_profile_picture","warning","Cannot create folder");
				echo json_encode($answer);
				return;
			}	
			
			// ********************************************************************************	
			// DECODE B64
			
			$image_parts = explode(";base64,", $posted_news['images'][1]);
			$image_type_aux = explode("image/", $image_parts[0]);
			$image_type = $image_type_aux[1];
			$image_base64 = base64_decode($image_parts[1]);
			
			// ********************************************************************************	
			// MOVE FILE TO DEDICATED FOLDER

			if(!file_put_contents($prflFullPath, $image_base64)){
				$answer['update'] = false;
				$answer['reason'] = 4;			
				debug("update_profile_picture","warning","Cannot move file");
				echo json_encode($answer);
				return;	
			}
			
			$finalPath .= '#splt#'.$absolutePath;
	
	}
	
	
	if(isset($posted_news['images'][2])){
	
			// ********************************************************************************
			// GENERATE PATH TO MEDIA
			
			$keyLength = 16;
			$folderKey = date('dmY');
			$folderKey .= bin2hex(random_bytes($keyLength));
			$prflKey = bin2hex(random_bytes($keyLength));
			$prflDirectory = "../images/".$folderKey;
			$prflFullPath = $prflDirectory.'/'.$prflKey.'.jpg';
			
			$absolutePath = 'https://ws.lifistic.com/app/freemium/images/'.$folderKey.'/'.$prflKey.'.jpg';
			
			// ********************************************************************************
			// CREATE DEDICATED FOLDER
			
			if(!mkdir($prflDirectory, 0777, true)){
				$answer['update'] = false;
				$answer['reason'] = 3;			
				debug("update_profile_picture","warning","Cannot create folder");
				echo json_encode($answer);
				return;
			}	
			
			// ********************************************************************************	
			// DECODE B64
			
			$image_parts = explode(";base64,", $posted_news['images'][2]);
			$image_type_aux = explode("image/", $image_parts[0]);
			$image_type = $image_type_aux[1];
			$image_base64 = base64_decode($image_parts[1]);
			
			// ********************************************************************************	
			// MOVE FILE TO DEDICATED FOLDER

			if(!file_put_contents($prflFullPath, $image_base64)){
				$answer['update'] = false;
				$answer['reason'] = 4;			
				debug("update_profile_picture","warning","Cannot move file");
				echo json_encode($answer);
				return;	
			}
			
			$finalPath .= '#splt#'.$absolutePath;
	
	}	
	
	// ********************************************************************************
	// GENERATE NEWS OBJECT
	
	date_default_timezone_set('Europe/Paris');
	
	if(isset($topSong['id'])){
		$data ='{'.$video.'"topSong":{"id":'.$topSong['id'].',"title":"'.$topSong['title'].'","url":"'.$topSong['url'].'","image":"","duration":"'.$topSong['duration'].'"},"date":"'.date('Y-m-d').'T'.date('H:i:s.v').'Z", "text": "'.htmlentities(addslashes($text)).'", "user": {"id": 0, "name": "'.$userid.'", "avatar": "", "groups": [], "isLive": false}, "audio": "", "image": "", "liked": false, "friend": false, "comments": {"next": "ws.lifistic.com/api/post/2345/comments?page=2&count=3", "items": [{"text": "", "userId": 1, "commentId": 1}, {"text": "", "userId": 1, "commentId": 1}, {"text": "", "userId": 1, "commentId": 1}]}, "location": "Paris, France", "likeCount": 0, "isLifistic": true, "commentCount": 0}';
	}else{
		$data ='{'.$video.'"topSong":false,"date":"'.date('Y-m-d').'T'.date('H:i:s.v').'Z", "text": "'.htmlentities(addslashes($text)).'", "user": {"id": 0, "name": "'.$userid.'", "avatar": "", "groups": [], "isLive": false}, "audio": "", "image": "", "liked": false, "friend": false, "comments": {"next": "ws.lifistic.com/api/post/2345/comments?page=2&count=3", "items": [{"text": "", "userId": 1, "commentId": 1}, {"text": "", "userId": 1, "commentId": 1}, {"text": "", "userId": 1, "commentId": 1}]}, "location": "Paris, France", "likeCount": 0, "isLifistic": true, "commentCount": 0}';	
	}
	
	$data = trim(preg_replace('/\s+/', ' ', $data));

	// ********************************************************************************
	// INSERT NEWS OBJECT IN DB
	
	$conn = connect_r1();
	
	$sql = "INSERT INTO LIFISTIC_1.LIFISTIC_NEWSFEED (NEWS_C, PICTURES, IMAGES, USER_S, LIKES) VALUES('$data','$picture','$finalPath', '$userid', '[]')";  
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['news_sent'] = false;
		debug("send_news","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// RETRIEVE USER LEVEL DATA

	$conn = connect_w1();
	
	$sql = "SELECT LEVEL, THRESHOLD, EXP FROM LIFISTIC_1.LIFISTIC_LEVEL WHERE USER_S = '$userid '"; 
	
	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['progress'] = false;
		debug("progress","warning","mysql error: ".mysqli_error($conn));
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("progress","warning","progress data not found");
		$answer['progress'] = false;
		return;
	}

	// ********************************************************************************
	// CALCULATE USER PROGRESS
	
	$up = false;
	$exp = $row['EXP'];
	$threshold = $row['THRESHOLD'];
	$lvl = $row['LEVEL'];

	
	$exp++;
	
	if($exp >= $threshold){
		$exp = 0;
		$lvl++;
		$threshold = floor(pow(2, ($lvl/4)) + pow($lvl,3));
		$up = true;
	}	
	
	
	
	// ********************************************************************************
	// GET REWARD FROM POST

	$conn = connect_w1();
	
	$sql = "UPDATE LIFISTIC_1.LIFISTIC_LEVEL SET LEVEL=$lvl, THRESHOLD=$threshold, EXP=$exp WHERE USER_S = '$userid '"; 
	
	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['news_sent'] = false;
		debug("news_sent","warning","mysql error: ".mysqli_error($conn));
		return;
	}	
	
	// **************************************************************************************************************************
	// ************************************************   NOTIFICATIONS  ********************************************************


	// ********************************************************************************
	// CREATE TALKER NEW NOTIFICATION OBJECT	
	if($up){
	
		$tmp['title'] = "";
		$tmp['text'] = "Niveau $lvl atteint";
		$tmp['user']['avatar'] = "./assets/logo/logo_only.png";
		$tmp['canLike'] = false;
		$tmp['liked'] = false;
		$tmp['isImportant'] = true;	
		
		$data = $tmp;
		
		debug("send_message","warning",json_encode($data));
		
		// ********************************************************************************
		// SEND NOTIFICATION TO TALKER	
		
		$conn = connect_w1();
		
		$sql = "UPDATE LIFISTIC_1.LIFISTIC_NOTIFICATIONS SET N_LIST = JSON_ARRAY_INSERT(N_LIST, '$.notifications[0]', CAST('".addslashes(json_encode($data))."' AS JSON)) WHERE N_ID='$userid'";  
		
		$res = mysqli_query($conn,$sql);
		if(!$res){
			$answer['send_message'] = false;
			debug("send_message","warning","Dmysql error: ".mysqli_error($conn));
			echo json_encode($answer);
			return;
		}		
	
	}
	
	
	// ********************************************************************************
	// WRAP & SEND
	
	$answer['news_sent'] = true;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));

?>


