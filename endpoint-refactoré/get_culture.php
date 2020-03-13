<?php
    $endpoint = 'get_culture.php';

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
	// RETRIEVE RANDOM CULTURE FROM DATABASE

    $sql = sprintf("SELECT * FROM LIFISTIC_1.LIFISTIC_APP_CULTURES ORDER BY RAND() LIMIT 10");
    
    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

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