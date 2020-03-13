<?php
	
// +------------------------------------------------------------+
// |  	unfollow  	 		          				            |
// | 			Unfollow a friended user			            |
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
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"]))
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	else {
		$answer['unfollow'] = false;
		debug("unfollow","warning","empty session userid value");
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// GET & CHECK POSTED VAR	
	
	$user = "";
	
	if(isset($_POST['user']))
		$user = $_POST['user'];

	if($user ==""){
		debug("unfollow","warning","user is empty");
		return;
	}
	
	$user = strtolower(substr($user,0,256));
	
	// ********************************************************************************
	// GET USER FOLLOWERS 	
	
	$conn = connect_w1();
	w_tx_start($conn);
	
	$sql = "SELECT DATA_S->'$.\"follows\"' as FOLLOWS FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S = '$userid' AND STATUS_ID = 1"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['unfollow'] = false;
		debug("unfollow","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("unfollow","warning","no friends");
		$answer['unfollow'] = false;
		echo json_encode($answer);
		return;
	}
	
	$follows = json_decode($row['FOLLOWS'], true);
	
	// ********************************************************************************
	// CHECK IF FOLLOWERS LIST IS POPULATED
	
	// le listing de followers vide est desormais un array sans données [], faire le checking la dessus	
	if($follows == null){
		debug("unfollow","warning","nobody is followed");
		$answer['unfollow'] = false;
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// CHECK FOLLOWER POSITION
	
	$pos = array_search($user,$follows); 
	
	if($pos!== false){
		
		// ********************************************************************************
		// DELETE FRIEND FROM FOLLOWERS LIST
		
		unset($follows[$pos]);
		
		$temp = array();
		
		foreach($follows as $tm)
			$temp[] = $tm;
		
		$follows = $temp;
		
		$follows = json_encode($follows, JSON_UNESCAPED_UNICODE);

		// ********************************************************************************
		// UPDATE FOLLOWERS LIST IN DB			
		
		$sql = "UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"follows\"', CAST('$follows' AS JSON))  WHERE USER_S = '$userid' AND STATUS_ID = 1 "; 
		$res = mysqli_query($conn,$sql);
		
		if(!$res){
			$answer['unfollow'] = false;
			debug("unfollow","warning","delete friend no ok mysql error: ".mysqli_error($conn));
			
			tx_rollback();
			
			echo json_encode($answer);
			return;
		}
		
		$answer['unfollow'] = true;
		tx_end($conn);
		
	} else {
	
		debug("unfollow","warning","followed id not found");
		$answer['unfollow'] = false;
		echo json_encode($answer);
		return;
		
	}
	
	// ********************************************************************************
	// WRAP & SEND	
	
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));
	
?>