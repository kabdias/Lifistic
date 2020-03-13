<?php
 $endpoint = 'artist.php';
    
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

	// *********************************************************************************
    
	$name = "";	
	$styles = "";
	$instruments = "";
	
	if(isset($_POST['name']))
		$name = $_POST['name'];
			
	// ********************************************************************************
	// 

    $sql = sprintf("SELECT DATA_S->\"$.friends\" 
                    as FRIENDS 
                    FROM LIFISTIC_USERS 
                    WHERE USER_S = '$userid' AND STATUS_ID = 1"); 

    $res = standardQuery("read", $sql, $endpoint);
    $row = mysqli_fetch_array($res);
    $friends = json_decode($row['FRIENDS'], true);
    
    // ********************************************************************************
	// 
    
    $sql = sprintf("SELECT USER_S,NAME_S, PICTURES->'$.\"profilePicture\"' 
                    as PICTURE, DATA_S->'$.\"private-data\".backgroundPicture' 
                    as BGPICTURE, DATA_S->'$.\"pending_invites\"' 
                    as PENDING, DATA_S->'$.\"private-data\".topSong' 
                    as TOPSONG, DATA_S->'$.\"private-data\".skills' 
                    as SKILL, DATA_S->'$.\"private-data\".styles' 
                    as STYLES, DATA_S->'$.\"private-data\".biography' 
                    as BIO, DATA_S->'$.\"private-data\".isPrivate' 
                    as PRIVATE 
                    FROM LIFISTIC_1.LIFISTIC_USERS 
                    WHERE USER_S='$name' AND STATUS_ID = 1"); 

    
    unset($res); 
    unset($row); 
    $res = standardQuery("read", $sql, $endpoint);

    // ********************************************************************************
	// 
	
	$names = array();

	while(true){
	
		$row = mysqli_fetch_array($res);
		if($row == null)
			break;
		// ******************** Is user
		if($row['PRIVATE'] != '"true"'){
		
			if($row['PENDING']!= null){
				$pending = json_decode($row['PENDING'],true);	
			}
				
			debug("artist","warning","user:".$row['USER_S']);	
				
			$tmp['id'] = ucfirst($row['USER_S']);
			$tmp['name'] = ucfirst($row['NAME_S']);
			$tmp['picture'] = json_decode($row['PICTURE']);
			$tmp['background'] = json_decode($row['BGPICTURE']);
			$tmp['skills'] = json_decode($row['SKILL'],true);	
			$tmp['styles'] = json_decode($row['STYLES'],true);	
			$tmp['bio'] = json_decode($row['BIO'],true);
			
			if($row['USER_S'] != $userid){			
			// ******************** is friend

				if($friends != ""){
				
					if(is_array($friends)){
					
						if(in_array($row['USER_S'],$friends)){
						
							$tmp['isFriend'] = true;
							
						}else{
						
							$tmp['isFriend'] = false;
						
						}
						
					}elseif(is_string($friends)){
					
						if($row['USER_S'] == $friends){
						
							$tmp['isFriend'] = true;
							
						}		
						
					}else{
					
						$tmp['isFriend'] = false;
						
					}
					
				}else{
				
				$tmp['isFriend'] = false;
				
				}
				
			// ******************** Pending invitation

				if($pending != ""){
	
					if(is_array($pending)){
						
						if(in_array($userid,$pending)){
							
							$tmp['pending'] = true;
								
						}else{
							
							$tmp['pending'] = false;
							
						}
							
					}elseif(is_string($pending)){
								
						if($row['USER_S'] == $pending){
							
							$tmp['pending'] = true;

						}							
							
					}else{
						
						$tmp['pending'] = false;
						
					}
						
				}else{
				
				$tmp['pending'] = false;
				
				}

			}else{
			
			$tmp['isFriend'] = true;
			
			}

			// ******************** topSong
			$topSong = json_decode($row['TOPSONG'],true);
			
			$tmp['topSong']['url'] = $topSong['url'];
			$tmp['topSong']['id'] = $topSong['id'];
			$tmp['topSong']['duration'] = $topSong['duration'];	
			$tmp['topSong']['title'] = $topSong['title'];	
			$tmp['topSong']['image'] = $topSong['image'];	
			
			if($row['USER_S'] == $userid){	
				$tmp['pending'] = false;
				$tmp['isFriend'] = true;
			}
			
			// ******************** Wrap & send
			$names[] = $tmp;
			
		}else{
		
		$tmp['id'] = $row['USER_S'];
		$tmp['private'] = true;
		
		$names[] = $tmp;
		
		}
		
	}


	// ********************************************************************************
	// WRAP & SEND
	
	$answer['results'] = $names;
	$answer['token'] = get_token();
	
	echo json_encode($answer);	
	debug("action-verbose-2","info",session_id().": ".json_encode($answer)." - ".$row['PENDING']);
	
?>
