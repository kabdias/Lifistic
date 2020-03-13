<?php
 $endpoint = 'get_chat_list.php';
    
    // ********************************************************************************
	// INCLUDE HEADERS
	include '../../headers/tools.h';	
	include '../../headers/sessions.h';
	include '../../headers/functions/sessionCheck.h';
	include '../../headers/functions/simpleChecks.h';	
    include '../../headers/functions/queries.h';
    
     // ********************************************************************************
	// SESSION + SECURITY	
	session_start();
	if(!php_req_valid()) return;
	secure_inputs();
	$userid = checkSession($_SESSION[$GLOBALS['APP']."USERID"], $endpoint);		

    // *********************************************************************************
    
    $sql = sprintf("SELECT USER_S, NAME_S 
                     FROM LIFISTIC_1.LIFISTIC_USERS"); 

    $res = standardQuery("read", $sql, $endpoint);
    while(true){
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
	
		$name[$row['USER_S']] = $row['NAME_S'];		
	
    }
   
    // *********************************************************************************
    unset($res);
    unset($row);

    $sql = sprintf("SELECT C_LIST as CHATLIST 
                    FROM LIFISTIC_1.LIFISTIC_CHAT 
                    WHERE C_ID = '$userid'"); 
    
    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

    $chatRow = $row['CHATLIST'];
	
	$chatList = json_decode($chatRow, true);	
	
	// ********************************************************************************
    // 
    unset($res);
    unset($row);

    $sql = sprintf("SELECT C_MESS as MESSLIST 
                    FROM LIFISTIC_1.LIFISTIC_CHAT 
                    WHERE C_ID = '$userid'");

    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

    $messList = json_decode($row['MESSLIST'], true);

    // ********************************************************************************
    // GET USER FRIEND LIST

    $sql = spritcf("SELECT DATA_S-> $.\'friends'\ as FRIENDS 
                    FROM LIFISTIC_1.LIFISTIC_USERS 
                    WHERE USER_S = '$userid' AND STATUS_ID = 1"); 
    
    unset($res);
    unset($row);
    
    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

    $friends = json_decode($row['FRIENDS'],true);	

    // ********************************************************************************
	// 
	
	if(isset($chatList['chatlist'])){
		
		$newMessageCount = array();
		
		for($j=0;$j<count($chatList['chatlist']);$j++){

			if($chatList['chatlist'][$j]['user']['name'] != "Meta"){ 
			
				$newMessageCount[$j] = 0;
				
				$userName = $chatList['chatlist'][$j]['user']['name'];
					
				$chatList['chatlist'][$j]['user']['name'] = $name[$userName];
				
				$chatList['chatlist'][$j]['user']['ident'] = $userName;
		
				for($i=0;$i<count($messList['chatroom']);$i++){	
					
					if($messList['chatroom'][$i]['chatId'] == $chatList['chatlist'][$j]['user']['id']){
					
						$newMessageText[$j] = html_entity_decode($messList['chatroom'][$i]['message']);
						
						$newMessageDate[$j] = $messList['chatroom'][$i]['time'];
						
						if($messList['chatroom'][$i]['status'] == 'pending'){
						
							$newMessageCount[$j]++;
							
						}
						
					}
					
				}
						
				$chatList['chatlist'][$j]['newMessageCount'] = $newMessageCount[$j];
				if(isset($newMessageDate[$j])){
					$chatList['chatlist'][$j]['lastMessage']['date'] = $newMessageDate[$j];
				}
				if(isset($newMessageText[$j])){			
					$chatList['chatlist'][$j]['lastMessage']['text'] = html_entity_decode($newMessageText[$j]);	
				}
			
			}
		
		}
	
	}
	
	// ********************************************************************************
	// WRAP & SEND
	
	$answer['get_chatlist'] = $chatList;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	
?>
