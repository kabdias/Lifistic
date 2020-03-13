<?php
	$endpoint = 'send_message';
// +------------------------------------------------------------+
// |  send_message	        						            |
// | 			 	         						            |
// |		send PM (id + text + time + profilePic + status)    |
// | 			 	         						            |
// |	 - Get user profile picture                     	    |
// |	 - Get user chatlist                        	        |
// |     - Retrieve receiver name w/ chatlist & chatid POST     |
// |	 - Create and Append message object to user chatroom    |
// |	 - Fetch receiver chatlist                       	    |
// |	 - Check conversation existence @receiver chatlist  	|
// |	 - If no conversation @receiver create one       	    |
// |	 - Create and append message object to receiver chatroom|
// | 	 - Send notification   						            |
// | 			 	         						            |
// +------------------------------------------------------------+

	// ********************************************************************************
	// INCLUDE HEADERS + SECURE 
	include '../../headers/tools.h';
	include '../../headers/sessions.h';
	include '../../headers/functions/sessionCheck.h';	
	include '../../headers/functions/notifications.h';	
	include '../../poc/token-manager.php';
	include '../../poc/notification-manager.php';

	// ********************************************************************************
	// SESSION + SECURITY	
	session_start();
	if(!php_req_valid()) return;
	secure_inputs();
	$userid = checkSession($_SESSION[$GLOBALS['APP']."USERID"], $endpoint);	
	
	// ********************************************************************************
	// CHECK POST VARs
	
	if(isset($_POST['message']))
		$message = $_POST['message'];

	$emotissons = array();
	if(isset($_POST['emotissons']))
		$emotissons = $_POST['emotissons'];
		

	if($message == ""){
		debug("send_message","warning","Message is empty");
		return;
	}else{
		// Those 2 lines will be removed upon secure_inputs() reactivation 
		$message = htmlspecialchars($message,ENT_COMPAT,"UTF-8");
		$message = addslashes($message);
	}
	
	if(isset($_POST['chatId']))
		$chatId = $_POST['chatId']; // Current user chat id
	
	if($chatId == ""){
		debug("send_message","warning","ChatId is empty");
		return;
	}	

	
	// ********************************************************************************
	// GET USER PROFILE PICTURE TO SHOW UP ON receiver NEW CHATROOM & receiver NOTIFICATION

	$conn = connect_w1();
	
	$sql = "SELECT PICTURES->'$.\"profilePicture\"' as PICTURE, NAME_S as NAME FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S='$userid' AND STATUS_ID = 1"; 

	$res = mysqli_query($conn,$sql);
		
	if(!$res){
		$answer['send_message'] = false;
		debug("send_message","warning","mysql error: ".mysqli_error($conn));
	}
		
	$row = mysqli_fetch_array($res);
		
	if(!$row){
		debug("send_message","warning","AChat data not found");
		$answer['send_message'] = false;
		return;
	}

	// ********************************************************************************
	// ARRAYFY JSON CHATLIST DATA	
	
	$profilePicture = json_decode($row['PICTURE']);	
	$userName = $row['NAME'];

	// ********************************************************************************
	// INIT NEEDED VARs

	$receiver = ""; // With whom userid is talking
	
	// ********************************************************************************
	// GET USER CHATLIST 
	
	$conn = connect_w1();
	
	$sql = "SELECT C_LIST as CHATLIST FROM LIFISTIC_1.LIFISTIC_CHAT WHERE C_ID = '$userid'"; 

	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['send_message'] = false;
		debug("send_message","warning","mysql error: ".mysqli_error($conn));
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("send_message","warning","user not found");
		$answer['send_message'] = false;
		return;
	}	
	
	
	// ********************************************************************************
	// ARRAYFY JSON CHATLIST DATA
	
	$chatArray = json_decode($row['CHATLIST'],true);

	// ********************************************************************************
	// RETRIEVE receiver NAME BY CHECKING IF POSTED ID EXIST IN CHATLIST
	
	for($i=0;$i<count($chatArray['chatlist']);$i++){

		if(intval($chatArray['chatlist'][$i]['user']['id']) == intval($chatId)){
		
			$receiver = $chatArray['chatlist'][$i]['user']['name'];
			
		}
		
	}	
	
	// ********************************************************************************
	// CREATE & JSONIFY MESSAGE OBJECT

	date_default_timezone_set('Europe/Paris');

	$data ='{"time":"'.date('Y-m-d').'T'.date('H:i:s.v').'Z", "status": "read", "message": "'.trim(preg_replace('/\s+/', ' ', $message)).'", "emotissons":'.json_encode($emotissons, JSON_UNESCAPED_UNICODE).', "chatId":"'.$chatId.'", "fromUserName":"'.$userid.'"}';

	// ********************************************************************************
	// APPEND MESSAGE OBJECT TO USER CHATROOM MESSAGES
	
	$conn = connect_r1();
	
	$sql = "UPDATE LIFISTIC_1.LIFISTIC_CHAT SET C_MESS = JSON_ARRAY_APPEND(C_MESS, '$.chatroom', CAST('$data' AS JSON)) WHERE C_ID='$userid'";  
	
	$res = mysqli_query($conn,$sql);

	if(!$res){
		$answer['send_message'] = false;
		//$answer['send_message'] = json_encode($emotissons);
		debug("send_message","warning","Amysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}


	// ********************************************************************************
	// FETCH receiver CHATLIST 
	
	$conn = connect_w1();
	
	$sql = "SELECT C_LIST as CHATLISTB FROM LIFISTIC_1.LIFISTIC_CHAT WHERE C_ID = '$receiver'"; 

	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['send_message'] = false;
		debug("send_message","warning","mysql error: ".mysqli_error($conn));
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("send_message","warning","Auser not found : ".$receiver);
		$answer['send_message'] = false;
		return;
	}	
	
	
	// ********************************************************************************
	// checker le cas de figure où la personne envoie un message alors qu'elle n'est plus amie [pour intégrité]
	// si suppression des amis entre temps, mettre en place un protocol
	// ********************************************************************************
	// CHECK IF A CONVERSATION ID EXIST @receiver CHATLIST, IF NOT WE CREATE ONE
	
	$chatArrayB = json_decode($row['CHATLISTB'],true);
	
	$index = -1;
	
	if(isset($chatArrayB['chatlist'][0]['user']['name'])){
	
		for($i=0;$i<count($chatArrayB['chatlist']);$i++){
		
			if(intval($chatArrayB['chatlist'][$i]['user']['id']) > $index){
				
				$index = intval($chatArrayB['chatlist'][$i]['user']['id']);
				
			}

			if($chatArrayB['chatlist'][$i]['user']['name'] == $userid){
				
				$cId = intval($chatArrayB['chatlist'][$i]['user']['id']);
				
			}
			
		}		
		
		if($index >= 0 && !isset($cId)){
		
			$newId = $index + 1;
		
		}
	
	}
	
	// ********************************************************************************
	// NO CONVERSATION FOR receiver ? LET'S CREATE ONE
	
	if(!isset($cId)){
		
		// ********************************************************************************
		// CREATE receiver NEW CHATLIST OBJECT 
		
		date_default_timezone_set('Europe/Paris');
	
		if(isset($newId)){
			$cId = $newId;
		}else{
			$cId = 0;
		}
		
		$tmp['user']['id'] = $cId;
		$tmp['user']['name'] = $userid;
		$tmp['user']['avatar'] = $profilePicture;
		$tmp['lastMessage']['date'] = date('Y-m-d').'T'.date('H:i:s.v').'Z';
		$tmp['lastMessage']['text'] = ''.trim(preg_replace('/\s+/', ' ', $message)).'';
		$tmp['newMessageCount'] = 1;
		$data = $tmp;

		// ********************************************************************************
		// UPDATE receiver NEW CHATLIST OBJECT
		
		$conn = connect_r1();
			
		$sql = "UPDATE LIFISTIC_1.LIFISTIC_CHAT SET C_LIST = JSON_ARRAY_APPEND(C_LIST, '$.chatlist', CAST('".addslashes(json_encode($data))."' AS JSON)) WHERE C_ID='$receiver'";  
			
		$res = mysqli_query($conn,$sql);
		
		if(!$res){
			$answer['send_message'] = false;
			debug("send_message","warning","Bmysql error: ".mysqli_error($conn));
			echo json_encode($answer);
			return;
		}		

	}

	// ********************************************************************************
	// CREATE receiver NEW CHATROOM OBJECT
	
	$data ='{"time":"'.date('Y-m-d').'T'.date('H:i:s.v').'Z", "status": "pending", "message": "'.trim(preg_replace('/\s+/', ' ', $message)).'", "chatId":"'.$cId.'", "fromUserName":"'.$userid.'"}';
	
	
	// ********************************************************************************
	// UPDATE receiver NEW CHATROOM OBJECT	
	
	$conn = connect_w1();
	
	$sql = "UPDATE LIFISTIC_1.LIFISTIC_CHAT SET C_MESS = JSON_ARRAY_APPEND(C_MESS, '$.chatroom', CAST('$data' AS JSON)) WHERE C_ID='$receiver'";  
	
	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['send_message'] = false;
		debug("send_message","warning","Cmysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}	

	// ********************************************************************************
	// NOTIFICATIONS
	$notification = createNotification($userid, $receiver, $profilePicture, false, "message", $endpoint);
	sendInAppNotification($notification, $receiver, $endpoint);
	sendFirebaseNotification($userid, $receiver, $message, $endpoint, "message");
	
	// ********************************************************************************
	// WRAP & SEND	
	$answer['send_message'] = true;
	$answer['token'] = get_token();	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));

?>


