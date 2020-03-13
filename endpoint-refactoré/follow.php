<?php
    $endpoint = 'follow.php';

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
	$user = "";
	
	if(isset($_POST['user']))
		$user = $_POST['user'];

	if($user ==""){
		debug("follow","warning","user is empty");
		return;
	}
    $user = strtolower(substr($user,0,256));
    
    // ********************************************************************************

	if($user == $userid){
		$answer['follow'] = false;
		debug("block","warning","can't self follow");
		echo json_encode($answer);
		return;
	}	

	// ********************************************************************************
    
    $sql = sprintf("SELECT DATA_S->'$.\"follows\"' as FOLLOWS, DATA_S->'$.\"friends\"' as FRIENDS 
                    FROM LIFISTIC_1.LIFISTIC_USERS 
                    WHERE USER_S = '$userid' AND STATUS_ID = 1");

    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res); 

    $follows = json_decode($row['FOLLOWS']);
	$friends = json_decode($row['FRIENDS']);

    // ********************************************************************************
	//

    if(in_array($user,$follows)){
		$answer['follow'] = true;
		echo json_encode($answer);
		return;
	}	
	
	if($follows == null)
		$follows = array();		
	
	if($friends == null){
		debug("follow","warning","no friends so nobody can be followed");
		$answer['follow'] = false;
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	//

    $pos = array_search($user,$friends); 
	
	if($pos!== false){
		
		if(!in_array($user,$follows))
			$follows [] = $user;
			else {
				$answer['follow'] = true;
				tx_end($conn);
				echo json_encode($answer);
				debug("action-verbose-2","info",session_id().": ".json_encode($answer));
				return;
				}
		
		$follows = json_encode($follows, JSON_UNESCAPED_UNICODE);

    // ********************************************************************************
    
    unset($res);

    $sql = sprintf("UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"follows\"', CAST('$follows' AS JSON))  
            WHERE USER_S = '$userid' AND STATUS_ID = 1 ");

    $res = standardQuery("write", $sql, $endpoint);

    $answer['follow'] = true;
		tx_end($conn);
		
	} else {
		debug("follow","warning","followed id not found");
		$answer['follow'] = false;
		echo json_encode($answer);
		return;
	} 
    
    // ********************************************************************************
	// ARRAYFY CULTURE

	$data = json_decode($row['DATA'],true);	
	
	$culture = array();
	
	$culture['id'] = intval($row['ID']);
	$culture['instrument'] = $row['INSTRUMENT'];
	$culture['image'] = $data['image'];
	$culture['text'] = $data['text'];
	

	// ********************************************************************************
	// WRAP & SEND
	
	$answer['culture'] = $culture;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	
?>
	