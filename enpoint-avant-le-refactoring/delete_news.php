<?php
// +------------------------------------------------------------+
// |  delete_news 	     						                |
// | 			 	         						            |
// |		Delete a news							        |
// | 			 	         						            |
// | 			 	         						            |
// +------------------------------------------------------------+

	// ********************************************************************************
	// INCLUDE HEADERS + SECURE 

	include '../../headers/tools.h';
	
	include '../../headers/sessions.h';
	session_start();
	
	if(!php_req_valid()) return;
	
	//secure_inputs();

	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"])){
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	}else {
		debug("delete_news","warning","session empty userid value");
		$answer['delete_news'] = false;
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// CHECK POST VARs
	
	if(isset($_POST['id']))
		$newsId = $_POST['id'];
		
	if($newsId ==""){
		debug("delete_news","warning","newsId is empty");
		return;
	}

	// ********************************************************************************
	// DELETE JAM
	
	$conn = connect_w1();
	
	$sql = "DELETE FROM LIFISTIC_1.LIFISTIC_NEWSFEED WHERE ID='$newsId'"; 
	
	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['delete_news'] = false;
		debug("delete_news","warning","cmysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	
	// ********************************************************************************
	// WRAP & SEND	
	
	$answer['delete_news'] = true;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));

?>


