<?php
    $endpoint = 'get_chat_contacts.php';

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
    // GET ACTIVE CHATS

    $sql = sprintf("SELECT C_LIST as CHATLIST 
                    FROM LIFISTIC_1.LIFISTIC_CHAT 
                    WHERE C_ID = '$userid'");

    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

    $chatArray = json_decode($row['CHATLIST'],true);

    // ********************************************************************************
	// 
	
	$userName = array();
	
	for($i=0;$i<count($chatArray['chatlist']);$i++){
		$userName[$i] = $chatArray['chatlist'][$i]['user']['name'];
	}
	
	// ********************************************************************************
	//
    
    unset($res);
    unset($row);

    $sql = sprintf("SELECT DATA_S->'$.\"friends\"' as FRIENDS 
                    FROM LIFISTIC_1.LIFISTIC_USERS 
                    WHERE USER_S = '$userid' AND STATUS_ID = 1"); 

    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

    $friends = json_decode($row['FRIENDS'], JSON_INVALID_UTF8_SUBSTITUTE);

    // ********************************************************************************
	// 
	
	unset($res);

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
	
	$sql = spritntf("SELECT USER_S, NAME_S, PICTURES->'$.\"profilePicture\"' as PICTURE 
                    FROM LIFISTIC_1.LIFISTIC_USERS 
                    WHERE USER_S IN $str AND STATUS_ID = 1 ORDER BY NAME_S ASC"); 

	$res = standardQuery("read", $sql, $endpoint);

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
    
