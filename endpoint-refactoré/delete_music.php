<?php
	$endpoint = 'delete_music';
	// ********************************************************************************
	// INCLUDE HEADERS + SECURE 
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

	// ********************************************************************************
	// CHECK POST VARs	
	$musicId = checkPost('id', 'int', $endpoint);

	// ********************************************************************************
	// GET TOPSONG ID
	$sql = sprintf("SELECT 
										DATA_S->'$.\"private-data\".topSong.id' AS TOPSONG 
									FROM 
										LIFISTIC_1.LIFISTIC_USERS 
									WHERE 
										USER_S = '$userid' AND STATUS_ID = 1"); 
	
	$res = standardQuery("read", $sql, $endpoint); 

	$row = mysqli_fetch_array($res);
	
	$topSong = $row['TOPSONG'];		
	
	// ********************************************************************************
	// IF MUSIC IS THE TOPSONG UPDATE TOPSONG DATA	
	if($topSong == $musicId){
		$sql = sprintf("UPDATE 
											LIFISTIC_1.LIFISTIC_USERS 
										SET 
											DATA_S = JSON_SET(DATA_S, '$.\"private-data\".topSong', false) 
										WHERE 
											USER_S='$userid' AND STATUS_ID = 1");
		
		$res = standardQuery("write", $sql, $endpoint); 
	}

	// ********************************************************************************
	// DELETE MUSIC OBJECT FROM MUSIC
	unset($sql);
	$sql = sprintf("DELETE FROM 
										LIFISTIC_1.LIFISTIC_MOD_MUSIC 
									WHERE 
										MUSIC_M_ID ='$musicId' AND USER_S = '$userid'
									");
	
	unset($res);
	$res = standardQuery("delete", $sql, $endpoint);

	// ********************************************************************************
	// DELETE MUSIC OBJECT FROM FOLDERS
	unset($sql);
	unset($res);
	$sql = sprintf("SELECT 
										JSON_SEARCH(MUSIC_ID, 'one', '$musicId') 
									FROM 
										lifistic_mod_music_folders 
									WHERE 
										FOLDER_D_ID = '$folderId'
									");
	
	$res = standardQuery("write", $sql, $endpoint);

	unset($sql);
	unset($res);
	$sql = sprintf("UPDATE 
										LIFISTIC_1.LIFISTIC_MOD_MUSIC_FOLDERS 
									SET  
										MUSIC_M = JSON_REMOVE(MUSIC_ID, $res) 
									WHERE 
										FOLDER_D_ID = '$folderId'
									");
	$res = standardQuery("write", $sql, $endpoint);


	// ********************************************************************************
	// WRAP & SEND	
	
	$answer[$endpoint] = true;
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	
?>