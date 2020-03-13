<?php
    $endpoint = 'delete_news.php';

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

	if(isset($_POST['id']))
		$pinId = $_POST['id'];

	if($pinId == ""){
		$answer['pin_news'] = false;
		debug("pin_news","warning","PinId is empty");
		echo json_encode($answer);		
		return;
	}

    // *****************************************************
	// INSERT PINNED NEWS
	
	$sql = sprintf("INSERT INTO LIFISTIC_1.LIFISTIC_PINS (PIN_USER, PIN_ID) 
                    VALUES('$userid', '$pinId')"); 

	$res = standardQuery("write", $sql, $endpoint);
	
	// ********************************************************************************
	// WRAP & SEND
	
	$answer['pin_news'] = true;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	
?>