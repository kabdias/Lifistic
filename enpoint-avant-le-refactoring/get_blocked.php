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
	
	secure_inputs();
	
	//$_SESSION[$GLOBALS['APP']."USERID"] = "phil"; 
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"]))
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	else {
		debug("get_blocked","warning","session empty userid value");
		$answer['blocked'] = false;
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// 
	
	$conn = connect_w1();
	
	$sql = "SELECT DATA_S->'$.\"blocked\"' as BLOCKED FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S = '$userid' AND STATUS_ID = 1"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['blocked'] = false;
		debug("get_blocked","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("get_blocked","warning","user not found");
		$answer['blocked'] = false;
		echo json_encode($answer);
		return;
	}
	
	$users = decode($row['BLOCKED']);

	// ********************************************************************************
	// 
	
	if($users == null){		
		$answer['blocked'] = array();
		echo json_encode($answer);
		return;
	}

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