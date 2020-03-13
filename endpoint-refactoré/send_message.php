<?php
	$endpoint = 'send_message';

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
    
	$message = checkPost('message', 'str', $endpoint, true);

    if($message == ""){
		debug("send_message","warning","Message is empty");
		return;
	}else{
		// Those 2 lines will be removed upon secure_inputs() reactivation 
		$message = htmlspecialchars($message,ENT_COMPAT,"UTF-8");
		$message = addslashes($message);
	}

    $emotissons = checkPost('emotissons', 'str', $endpoint, true);
    $$chatId = checkPost('chatId', 'str', $endpoint, true);

    // ********************************************************************************
	// GET USER PROFILE PICTURE TO SHOW UP ON receiver NEW CHATROOM & receiver NOTIFICATION

    $sql = sprintf("SELECT PICTURES->'$.\"profilePicture\"' as PICTURE, NAME_S as NAME 
                    FROM LIFISTIC_1.LIFISTIC_USERS 
                    WHERE USER_S='$userid' AND STATUS_ID = 1"); 
                    
    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

    // ********************************************************************************
	// ARRAYFY JSON CHATLIST DATA	
	
	$profilePicture = json_decode($row['PICTURE']);	
	$userName = $row['NAME'];

	// ********************************************************************************
	// INIT NEEDED VARs

	$receiver = ""; // With whom userid is talking
	
	// ********************************************************************************
	// GET USER CHATLIST

    unset($res);
    unset($row);

    $sql = sprintf("SELECT C_LIST as CHATLIST 
                    FROM LIFISTIC_1.LIFISTIC_CHAT 
                    WHERE C_ID = '$userid'");

    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

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
    unset($res);
    unset($row);

    $sql = sprintf("UPDATE LIFISTIC_1.LIFISTIC_CHAT SET C_MESS = JSON_ARRAY_APPEND(C_MESS, '$.chatroom', CAST('$data' AS JSON)) 
                    WHERE C_ID='$userid'");


    $res = standardQuery("read", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

    // ********************************************************************************
	// FETCH receiver CHATLIST
    
    unset($res);
    unset($row);

    $sql = sprintf("SELECT C_LIST as CHATLISTB 
                    FROM LIFISTIC_1.LIFISTIC_CHAT 
                    WHERE C_ID = '$receiver'"); 
    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

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

        unset($res);

        $sql = sprintf("UPDATE LIFISTIC_1.LIFISTIC_CHAT SET C_LIST = JSON_ARRAY_APPEND(C_LIST, '$.chatlist', CAST('".addslashes(json_encode($data))."' AS JSON)) 
                WHERE C_ID='$receiver'");  
                
        // ********************************************************************************
	    // CREATE receiver NEW CHATROOM OBJECT
	
	    $data ='{"time":"'.date('Y-m-d').'T'.date('H:i:s.v').'Z", "status": "pending", "message": "'.trim(preg_replace('/\s+/', ' ', $message)).'", "chatId":"'.$cId.'", "fromUserName":"'.$userid.'"}';
	
	
	    // ********************************************************************************
	    // UPDATE receiver NEW CHATROOM OBJECT
        unset($res);
        unset($row);
        
        $sql = sprintf("UPDATE LIFISTIC_1.LIFISTIC_CHAT SET C_MESS = JSON_ARRAY_APPEND(C_MESS, '$.chatroom', CAST('$data' AS JSON))
                WHERE C_ID='$receiver'");  

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
	