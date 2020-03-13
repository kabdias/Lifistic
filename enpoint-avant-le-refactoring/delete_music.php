<?php

// +------------------------------------------------------------+
// |  delete_music 		    						            |
// | 			 	         						            |
// |		Delete selected music 							    |
// | 			 	         						            |
// |	 - Get music ID                                   	    |
// |	 - Fetch user audio                    	              	|                  
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
		debug("delete_music","warning","session empty userid value");
		$answer['delete_music'] = false;
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// CHECK POST VARs	
	
	if(isset($_POST['id']))
		$musicId = $_POST['id'];
	
	if($musicId == ""){
		debug("delete_music","warning","Folder id is empty");
		return;
	}	

	
	
	// ********************************************************************************
	// GET TOPSONG ID
	
	$conn = connect_r1();
	
	$sql = "SELECT DATA_S->'$.\"private-data\".topSong.id' as TOPSONG FROM LIFISTIC_USERS WHERE USER_S = '$userid' AND STATUS_ID = 1"; 
	
	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['delete_music'] = false;
		debug("delete_music","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		$answer['delete_music'] = false;
		debug("delete_music","error","Le topSong n'a pas pu être récupéré.");
		echo json_encode($answer);
		return;
	}
	
	$topSong = $row['TOPSONG'];		
	
	
	// ********************************************************************************
	// IF MUSIC IS THE TOPSONG UPDATE TOPSONG DATA	
	
	if($topSong == $musicId){
		
		$sql = "UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"private-data\".topSong', false) WHERE USER_S='$userid' AND STATUS_ID = 1";
		
		$conn = connect_w1();
		
		$res = mysqli_query($conn,$sql);
		
		if(!$res){
			$answer['delete_music'] = false;
			debug("delete_music","warning","mysql error: ".mysqli_error($conn));
			echo json_encode($answer);
			return;
		}
	
	
	}
	

	// ********************************************************************************
	// FETCH USER AUDIO

	$conn = connect_w1();
	
	$sql = "SELECT MUSIC_M_AUDIO as AUDIO FROM LIFISTIC_1.LIFISTIC_MOD_MUSIC WHERE MUSIC_M_ID = '$userid'"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['delete_music'] = false;
		debug("delete_music","warning","mysql error: ".mysqli_error($conn));
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("delete_music","warning","user not found");
		$answer['delete_music'] = false;
	}
	
	$audioArray = json_decode($row['AUDIO'],true);	
	
	// ********************************************************************************
	// DELETE MUSIC OBJECT FROM AUDIO
	
	$countMusic = count($audioArray['audio']);

	$newAudioArray = array();
	
	$newAudioArray['max'] = $audioArray['max'];	
	
	if($countMusic>1){
	
		for($i=0;$i<$countMusic;$i++){
			
			if($audioArray['audio'][$i]['id'] != $musicId){
		
				$newAudioArray['audio'][] = $audioArray['audio'][$i];
			
			}else{
			
				$folderId = $audioArray['audio'][$i]['folderId'];
			
			}
		
		}
		
	}else{
	
		$newAudioArray['audio'] = array();
		$folderId = -1;
	
	}
	
	// ********************************************************************************
	// FETCH USER DISCOGRAPHY

	$conn = connect_w1();
	
	$sql = "SELECT MUSIC_M_DISCO as DISCO FROM LIFISTIC_1.LIFISTIC_MOD_MUSIC WHERE MUSIC_M_ID = '$userid'"; 
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['delete_music'] = false;
		debug("delete_music","warning","mysql error: ".mysqli_error($conn));
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("delete_music","warning","user not found");
		$answer['delete_music'] = false;
	}
	
	$discoArray = json_decode($row['DISCO'],true);	

	$countFolder = count($discoArray['discography']);
	
	
	
	// ********************************************************************************
	// RECREATE DISCOGRAPHY OBJECT
	
	$newDiscoArray = array();
	
	$newDiscoArray['max'] = $discoArray['max'];
	
	$newDiscoArray['discography'] = array();
	
	for($i=0;$i<$countFolder;$i++){
		
			$filesCount = count($discoArray['discography'][$i]['files']);
			
			$tmp = array();
			
			$tmp['id'] = $discoArray['discography'][$i]['id'];
			$tmp['max'] = $discoArray['discography'][$i]['max'];		
			$tmp['name'] = $discoArray['discography'][$i]['name'];
			$tmp['show'] = $discoArray['discography'][$i]['show'];	
			$tmp['files'] = array();
			
			if($filesCount > 0){
			
				debug("delete_music","warning","ok3");
			
				for($j=0;$j<$filesCount;$j++){
				
					if($discoArray['discography'][$i]['files'][$j]['id'] != $musicId){
					
						$tmp['files'][] = $discoArray['discography'][$i]['files'][$j];
					
					}
				
				}
			
			}
			
			$newDiscoArray['discography'][] = $tmp;
	
	}


	// ********************************************************************************
	// UPDATE USER DISCOGRAPHY	
	
	$conn = connect_r1();
	
	$sql = "UPDATE LIFISTIC_1.LIFISTIC_MOD_MUSIC SET MUSIC_M_DISCO = '".json_encode($newDiscoArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."', MUSIC_M_DISCO = JSON_SET(MUSIC_M_DISCO, '$.max', ".$newDiscoArray['max'].") WHERE MUSIC_M_ID='$userid'";  
	
	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['delete_music'] = false;
		debug("delete_music","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}		

	// ********************************************************************************
	// UPDATE USER AUDIO
	
	$conn = connect_r1();
	
	$sql = "UPDATE LIFISTIC_1.LIFISTIC_MOD_MUSIC SET MUSIC_M_AUDIO = '".json_encode($newAudioArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."', MUSIC_M_AUDIO = JSON_SET(MUSIC_M_AUDIO, '$.max', ".$newAudioArray['max'].") WHERE MUSIC_M_ID='$userid'";  
	
	$res = mysqli_query($conn,$sql);
	
	if(!$res){
		$answer['delete_music'] = false;
		debug("delete_music","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}	

	// ********************************************************************************
	// WRAP & SEND	
	
	$answer['delete_music'] = true;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	
?>