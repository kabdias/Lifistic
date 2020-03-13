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
		
	include '../../headers/tools.h';
	
	include '../../headers/sessions.h';
	session_start();

	if(!php_req_valid()) return;
	
	//secure_inputs();
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"]))
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	else {
		debug("get_chat_list","warning","session empty userid value");
		$answer['get_chatlist'] = false;
		echo json_encode($answer);
		return;
	}
	
	// ********************************************************************************
	// 	

	$conn = connect_r1();
	
	$sql = "SELECT USER_S, NAME_S FROM LIFISTIC_1.LIFISTIC_USERS"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['results'] = false;
		debug("get_newsfeed","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	while(true){
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
	
		$name[$row['USER_S']] = $row['NAME_S'];		
	
	}

	// ********************************************************************************
	// 
	
	$conn = connect_w1();
	
	$sql = "SELECT C_LIST as CHATLIST FROM LIFISTIC_1.LIFISTIC_CHAT WHERE C_ID = '$userid'"; 

	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['get_chatlist'] = false;
		debug("get_chat_list","warning","mysql error: ".mysqli_error($conn));
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("get_chat_list","warning","user not found");
		$answer['get_chatlist'] = false;
	}
	
	$chatRow = $row['CHATLIST'];
	
	$chatList = json_decode($chatRow, true);	
	
	// ********************************************************************************
	// 
	
	$conn = connect_w1();
	
	$sql = "SELECT C_MESS as MESSLIST FROM LIFISTIC_1.LIFISTIC_CHAT WHERE C_ID = '$userid'"; 

	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['get_chatlist'] = false;
		debug("get_chat_list","warning","mysql error: ".mysqli_error($conn));
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("get_chat_list","warning","user not found");
		$answer['get_chatlist'] = false;
	}
	
	$messList = json_decode($row['MESSLIST'], true);
	
	// ********************************************************************************
	// GET USER FRIEND LIST
	
	$conn = connect_w1();
	w_tx_start($conn);
	
	$sql = "SELECT DATA_S->'$.\"friends\"' as FRIENDS FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S = '$userid' AND STATUS_ID = 1"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['delete_friend'] = false;
		debug("delete_friend","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("delete_friend","warning","no friends");
		$answer['delete_friend'] = false;
		echo json_encode($answer);
		return;
	}
	
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