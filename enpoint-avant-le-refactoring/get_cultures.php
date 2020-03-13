<?php
	
// +------------------------------------------------------------+
// |  get_cultures	 		          				            |
// | 			 	         						            |
// |		Get random cultures								    |
// | 			 	         						            |
// |	 - Get data from DB                             	    |
// |	 - Create formatted object                    	        |
// |     - Send object to App                                   |
// | 			 	         						            |
// +------------------------------------------------------------+

	// ********************************************************************************
	// INCLUDE HEADERS + SECURE 

	include '../../headers/tools.h';

	include '../../headers/sessions.h';
	session_start();

	if(!php_req_valid()) return;
	
	secure_inputs();
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"]))
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	else {
		debug("culture","warning","session empty userid value");
		$answer['culture'] = false;
		echo json_encode($answer);
		return;
	}



	// ********************************************************************************
	// RETRIEVE RANDOM CULTURE FROM DATABASE

	$conn = connect_w1();
	
	$sql = "SELECT * FROM LIFISTIC_1.LIFISTIC_APP_CULTURES ORDER BY RAND() LIMIT 10"; 
	
	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['culture'] = false;
		debug("culture","warning","mysql error: ".mysqli_error($conn));
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("culture","warning","Culture data not found");
		$answer['culture'] = false;
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