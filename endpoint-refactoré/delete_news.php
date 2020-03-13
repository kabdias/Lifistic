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
	// CHECK POST VARs
	
	if(isset($_POST['id']))
		$newsId = $_POST['id'];
		
	if($newsId ==""){
		debug("delete_news","warning","newsId is empty");
		return;
	}

	// ********************************************************************************
	// DELETE JAM
	
	$sql = sprintf("DELETE FROM LIFISTIC_1.LIFISTIC_NEWSFEED 
                    WHERE ID='$newsId'"); 
	
	$res = standardQuery("write", $sql, $endpoint);

	// ********************************************************************************
	// WRAP & SEND	
	
	$answer['delete_news'] = true;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));

?>