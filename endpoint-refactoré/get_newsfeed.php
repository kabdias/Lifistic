<?php
    $endpoint = 'get_newsfeed.php';

    // ********************************************************************************
	// INCLUDE HEADERS
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
	//

    $sql = sprintf("SELECT USER_S, NAME_S 
                    FROM LIFISTIC_1.LIFISTIC_USERS"); 

    $res = standardQuery("write",$sql,$endpoint);

    while(true){
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
	
		$name[$row['USER_S']] = $row['NAME_S'];		
	
	}

    // ********************************************************************************
	//
    
    unset($res);
    unset($row);

    $sql = sprintf("SELECT PIN_ID as PINS 
                    FROM LIFISTIC_1.LIFISTIC_PINS 
                    WHERE PIN_USER = '$userid'"); 
    
    $res = standardQuery("write",$sql,$endpoint);

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
    unset($res);
    unset($row);

    $sql = sprintf("SELECT DATA_S->'$.follows' as FOLLOWS 
                    FROM LIFISTIC_1.LIFISTIC_USERS 
                    WHERE USER_S='$userid'");
    
    $res = standardQuery("write",$sql,$endpoint);

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

    unset($res);
    unset($row);

    $newsfeed = "";

    $sql = sprintf("SELECT ID, NEWS_C, PICTURES as AVATAR, IMAGES as GALLERY
                    FROM LIFISTIC_1.LIFISTIC_NEWSFEED 
                    ORDER BY REG_DATE DESC LIMIT 50"); 
    
    $res = standardQuery("write",$sql,$endpoint);

    while(true){
		
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
			
		$convert = json_decode($row['NEWS_C'],true);

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