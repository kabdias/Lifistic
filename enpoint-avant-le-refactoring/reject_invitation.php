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
	
	include '../headers/tools.h';
	
	include '../headers/sessions.h';
	session_start();
	
	if(!php_req_valid()) return;
	
	secure_inputs();
	
	$user = "";
	
	if(isset($_POST['user']))
		$user = $_POST['user'];

	if($user ==""){
		debug("reject_invitation","warning","user is empty");
		return;
	}
	
	//$_SESSION[$GLOBALS['APP']."USERID"] = "grogcw"; 
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"]))
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	else {
		$answer['reject_invitations'] = false;
		debug("reject_invitation","warning","empty session userid value");
		echo json_encode($answer);
		return;
	}
	
	$user = strtolower(substr($user,0,256));

	// ********************************************************************************
	//
	
	$conn = connect_w1();
	w_tx_start($conn);
	$sql = "SELECT DATA_S->'$.\"pending_invites\"' as PENDING_INVITES FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S = '$userid' AND STATUS_ID = 1"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['reject_invitations'] = false;
		debug("reject_invitation","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("reject_invitation","warning","no pending invitation");
		$answer['reject_invitations'] = false;
		echo json_encode($answer);
		return;
	}
	
	$pending_invitations = json_decode($row['PENDING_INVITES'],true);

	// ********************************************************************************
	//	
	
	if($pending_invitations == null){
		debug("reject_invitation","warning","bad pending invitation json");
		$answer['reject_invitations'] = false;
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	//
	
	$pos = array_search($user,$pending_invitations); 
	
	if($pos!== false){
		
		unset($pending_invitations[$pos]);
		
		$pend = array();
		
		foreach($pending_invitations as $pending)
			$pend[] = $pending;
		
		$pending_invitations = $pend;
				
		$pending_invitations = json_encode($pending_invitations, JSON_UNESCAPED_UNICODE);
		
		if($pending_invitations == null){
			$pending_invitations = [];
		}
	
		$sql = "UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"pending_invites\"', CAST('$pending_invitations' AS JSON)) WHERE USER_S = '$userid' AND STATUS_ID = 1 "; 
		$res = mysqli_query($conn,$sql);
		if(!$res){
			$answer['reject_invitations'] = false;
			debug("reject_invitation","warning","mysql error: ".mysqli_error($conn));
			echo json_encode($answer);
			return;
		}
		$answer['reject_invitations'] = true;
		tx_end($conn);
		
	} else {
		debug("reject_invitation","warning","pending invitation not found");
		$answer['reject_invitations'] = false;
		echo json_encode($answer);
		return;
	}
	
	// ********************************************************************************
	// WRAP & SEND	
	
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));
	
?>