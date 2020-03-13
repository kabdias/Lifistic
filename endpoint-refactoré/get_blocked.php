<?php
    $endpoint = 'get_blocked.php';

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
    
    //// ******************************************************************************** 
	
    $sql = sprintf("SELECT DATA_S->'$.\"blocked\"' as BLOCKED 
                    FROM LIFISTIC_1.LIFISTIC_USERS 
                    WHERE USER_S = '$userid' AND STATUS_ID = 1"); 
    
    // ********************************************************************************
    
    if($users == null){		
		$answer['blocked'] = array();
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
    
    // ********************************************************************************
	// 
	
	$str = "("; 
	$i = 0; 
	
	foreach($users as $tm)		
		$i++==0 ? $str .= "'$tm'" : $str .= ",'$tm'";

	$str .= ")";
	
	$sql = "SELECT USER_S, NAME_S, PICTURES->'$.\"profilePicture\"' as PICTURE FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S IN $str AND STATUS_ID = 1"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['blocked'] = false;
		debug("get_blocked","warning","mysql error: ".mysqli_error($conn));
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
		$tmp['picture'] = $row['PICTURE'];
		
		$names[] = $tmp;

	}

	// ********************************************************************************
	// WRAP & SEND
	
	$answer['blocked'] = $names;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	
?>