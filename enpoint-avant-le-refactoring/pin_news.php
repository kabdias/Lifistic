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
	
	//secure_inputs();
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"]))
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	else {
		debug("pin_news","warning","session empty userid value");
		$answer['pin_news'] = false;
		echo json_encode($answer);
		return;
	}

	// *****************************************************
	//

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
	
	$conn = connect_w1();
	
	$sql = "INSERT INTO LIFISTIC_1.LIFISTIC_PINS (PIN_USER, PIN_ID) VALUES('$userid', '$pinId')"; 

	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['pin_news'] = false;
		debug("pin_news","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);		
		return;
	}
	
	// ********************************************************************************
	// WRAP & SEND
	
	$answer['pin_news'] = true;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	
?>