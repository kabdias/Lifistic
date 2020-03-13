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
	
	if(isset($_SESSION[$GLOBALS['APP']."USERID"]))
		$userid = $_SESSION[$GLOBALS['APP']."USERID"];
	else {
		$answer['results'] = false;
		debug("get_newsfeed","warning","session empty userid value");
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	//

	$conn = connect_r1();
	
	$sql = "SELECT USER_S, NAME_S FROM LIFISTIC_1.LIFISTIC_USERS"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['results'] = false;
		debug("get_newsfeed","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	while(true){
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
	
		$name[$row['USER_S']] = $row['NAME_S'];		
	
	}
	
	// ********************************************************************************
	//  
	
	$conn = connect_r1();
	
	$sql = "SELECT PIN_ID as PINS FROM LIFISTIC_1.LIFISTIC_PINS WHERE PIN_USER = '$userid'"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['results'] = false;
		debug("get_newsfeed","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
		
	$i = 0;
	
	$pinArray = array();
	
	while(true){
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
			
			$pinArray[] = $row['PINS'];
			
		}

	// ********************************************************************************
	//
		
	$conn = connect_r1();
	
	$sql = "SELECT DATA_S->'$.follows' as FOLLOWS FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S='$userid'"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['results'] = false;
		debug("get_newsfeed","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	// ********************************************************************************
	//	
	
	$n = 0;
	while(true){
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
	
		$follows = json_decode($row['FOLLOWS'],true);		
		$n++;
	}
	
	// ********************************************************************************
	//	
	
	$newsfeed = "";
	
	$conn = connect_r1();
	
	$sql = "SELECT ID, NEWS_C, PICTURES as AVATAR, IMAGES as GALLERY FROM LIFISTIC_1.LIFISTIC_NEWSFEED ORDER BY REG_DATE DESC LIMIT 50"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['results'] = false;
		debug("get_newsfeed","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	//	
	
	while(true){
		
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
			
		$convert = json_decode($row['NEWS_C'],true);
		/*
		if($n == 1){
			$str2array = array();
			$str2array[] = $follows;
			$str2array[] = "lifistic";
			unset($follows);
			$follows = $str2array;
		}
		*/

	// ********************************************************************************
	//
		
		if(in_array($convert['user']['name'], $follows) || $convert['user']['name'] == $userid){
		
			$convert['user']['avatar'] = $row['AVATAR'];
			
			if($row['GALLERY']!= null){
			
			    $imgs = explode('#splt#',$row['GALLERY']);
				
				if(count($imgs)<=1){
					
					$convert['image'] = $row['GALLERY'];
					
				}else{
					if(count($imgs)>1){
						$convert['image'][0] = $imgs[0];
					}
					if(count($imgs)>=2){
						$convert['image'][1] = $imgs[1];
					}
					if(count($imgs)>=3){
						$convert['image'][2] = $imgs[2];
					}
					
				}
				
			}
			
			if(isset($convert['topSong']['id'])){
				
				if($convert['topSong']['url'] != ""){
					
					$convert['topSong']['id'] = $convert['topSong']['id'];
					$convert['topSong']['title'] = $convert['topSong']['title'];
					$convert['topSong']['url'] = $convert['topSong']['url'];
					$convert['topSong']['image'] = $convert['topSong']['image'];
					$convert['topSong']['duration'] = $convert['topSong']['duration'];
					
				}
				
			}else{
				
				$convert['topSong'] = false;
				
			}
			
			
			$convert['id'] = $row['ID'];
			
			if(in_array($row['ID'], $pinArray)){
				$convert['isPinned'] = true;
			}else{
				$convert['isPinned'] = false;
			}
			
			
			$convert['user']['name'] = $name[$convert['user']['name']];
			
			$convert['text'] = stripslashes(html_entity_decode($convert['text']));
			
			
			$newsfeed[] = $convert;
			
		}
	
	}	

	// ********************************************************************************
	// WRAP & SEND
	
	$answer['results'] = $newsfeed;
	$answer['token'] = get_token();
	
	echo json_encode($answer);	
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));

	
?>