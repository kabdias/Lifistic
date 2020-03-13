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
		debug("get_contacts","warning","session empty userid value");
		$answer['contacts'] = false;
		echo json_encode($answer);
		return;
	}
	
	// *****************************************************
	// GET ACTIVE CHATS
	
	$conn = connect_w1();
	
	$sql = "SELECT C_LIST as CHATLIST FROM LIFISTIC_1.LIFISTIC_CHAT WHERE C_ID = '$userid'"; 

	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['chatroom'] = false;
		debug("get_message","warning","mysql error: ".mysqli_error($conn));
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("get_message","warning","Chat data not found");
		$answer['chatroom'] = false;
	}
	
	$chatArray = json_decode($row['CHATLIST'],true);

	// ********************************************************************************
	// 
	
	$userName = array();
	
	for($i=0;$i<count($chatArray['chatlist']);$i++){
		$userName[$i] = $chatArray['chatlist'][$i]['user']['name'];
	}
	
	// ********************************************************************************
	// 	
	
	$conn = connect_w1();
	
	$sql = "SELECT DATA_S->'$.\"friends\"' as FRIENDS FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S = '$userid' AND STATUS_ID = 1"; 

	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['contacts'] = false;
		debug("get_contacts","warning","mysql error: ".mysqli_error($conn));
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("get_contacts","warning","user not found");
		$answer['contacts'] = false;
	}
	
	$friends = json_decode($row['FRIENDS'], JSON_INVALID_UTF8_SUBSTITUTE);
	//echo var_dump($friends);

	// ********************************************************************************
	// 	
	
	if($friends == null){		
		$answer['contacts'] = array();
		echo json_encode($answer);
		return;
	}
	
	$str = "("; 
	$i = 0; 
	
	foreach($friends as $tm)		
		$i++==0 ? $str .= "'$tm'" : $str .= ",'$tm'";

	$str .= ")";
	
	$sql = "SELECT USER_S, NAME_S, PICTURES->'$.\"profilePicture\"' as PICTURE FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S IN $str AND STATUS_ID = 1 ORDER BY NAME_S ASC"; 

	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['contacts'] = false;
		debug("get_contacts","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// 
	
	$names = array();
	
	while(true){
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;

		$tmp['id'] = $row['USER_S'];
		$tmp['name'] = $row['NAME_S'];
		$tmp['picture'] = substr($row['PICTURE'], 1, -1);
		
		
		if(in_array($row['USER_S'], $userName)){
			$tmp['hasRoom'] = true;
			}else{
			$tmp['hasRoom'] = false;
		}
		
		
		$names[] = $tmp;

	}

	// ********************************************************************************
	// WRAP & SEND
	
	$answer['contacts'] = $names;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	
?>