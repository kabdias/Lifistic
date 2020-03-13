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

	// ********************************************************************************
	//
	
	$user = "";
	
	if(isset($_POST['user']))
		$user = $_POST['user'];

	if($user ==""){
		debug("follow","warning","user is empty");
		return;
	}
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"]))
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	else {
		$answer['follow'] = false;
		debug("follow","warning","empty session userid value");
		echo json_encode($answer);
		return;
	}
	
	$user = strtolower(substr($user,0,256));

	// ********************************************************************************
	//
	
	if($user == $userid){
		$answer['follow'] = false;
		debug("block","warning","can't self follow");
		echo json_encode($answer);
		return;
	}	

	// ********************************************************************************
	//
	
	$conn = connect_w1();
	w_tx_start($conn);
	
	$sql = "SELECT DATA_S->'$.\"follows\"' as FOLLOWS, DATA_S->'$.\"friends\"' as FRIENDS FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S = '$userid' AND STATUS_ID = 1"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['follow'] = false;
		debug("follow","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("follow","warning","no friends");
		$answer['follow'] = false;
		echo json_encode($answer);
		return;
	}
	
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

		$sql = "UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"follows\"', CAST('$follows' AS JSON))  WHERE USER_S = '$userid' AND STATUS_ID = 1 "; 
		$res = mysqli_query($conn,$sql);
		
		if(!$res){
			$answer['follow'] = false;
			debug("follow","warning","delete friend no ok mysql error: ".mysqli_error($conn));
			
			tx_rollback();
			
			echo json_encode($answer);
			return;
		}
		
		$answer['follow'] = true;
		tx_end($conn);
		
	} else {
		debug("follow","warning","followed id not found");
		$answer['follow'] = false;
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// WRAP & SEND	
	
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));
	
?>