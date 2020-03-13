<?php
 $endpoint = 'exist.php';
    
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
		
	if(isset($_POST['latitude']))
		$latitude = $_POST['latitude'];		
		
	if(isset($_POST['longitude']))
		$longitude = $_POST['longitude'];				
		
	if(isset($_POST['radius']))
		$radius = $_POST['radius'];				
		
	if(isset($_POST['styles'])){
		$styles = $_POST['styles'];		
	}	
		
	if(isset($_POST['skills'])){
		$instruments = $_POST['skills'];	
	}
		
	if($name != "")
		$name = strtolower(substr($name,0,256));
		
	// ********************************************************************************	 

	$sql = sprintf("SELECT DATA_S->\"$.friends\" 
                    as FRIENDS 
                    FROM LIFISTIC_USERS 
                    WHERE USER_S = '$userid' 
                    AND STATUS_ID = 1"); 
	
	
	$res = standardQuery("read", $sql, $endpoint);
    $row = mysqli_fetch_array($res);
	
	$row = mysqli_fetch_array($res);
	
    //**********************************************************************************

    $friends = json_decode($row['FRIENDS'], true);
	
	$filters = "";
	$filterA = "";
	$filterB = "";
	$tempA = array();
	$tempB = array();
	


	if($styles == ""){
		$stylesNumber = 0;
	}else{
		/*if(strpos($styles,",") !== false){
			$styles = explode(",", $styles);
		}*/
		$stylesNumber = count($styles);
	}

	
	if($instruments == ""){	
		$instrumentsNumber = 0;
	}else{
		/*if(strpos($instruments,",") !== false){
			$instruments = explode(",", $instruments);
		}*/
		$instrumentsNumber = count($instruments);
	}	
	
		
	
	if($stylesNumber > 0){
		if($stylesNumber == 1){
			$tempA[] = "JSON_CONTAINS(DATA_S, JSON_QUOTE('".$styles[0]."'), '$.\"private-data\".styles')";
		}else{
			for($i=0;$i<$stylesNumber;$i++){
				$tempA[] = "JSON_CONTAINS(DATA_S, JSON_QUOTE('".$styles[$i]."'), '$.\"private-data\".styles')";
			}
		}
		$filterA = join(' AND ',$tempA);
		$filters = $filterA." AND ";
	}
	
	if($instrumentsNumber > 0){
		if($instrumentsNumber == 1){
			$tempB[] = "JSON_CONTAINS(DATA_S, JSON_QUOTE('".$instruments[0]."'), '$.\"private-data\".skills')";
		}else{
			for($i=0;$i<$instrumentsNumber;$i++){	
				$tempB[] = "JSON_CONTAINS(DATA_S, JSON_QUOTE('".$instruments[$i]."'), '$.\"private-data\".skills')";	
			}
		}
		$filterB = join(' AND ',$tempB);		
		$filters = $filterB." AND ";
	}	
	
	if($instrumentsNumber > 0 && $stylesNumber > 0){
		$filters = $filterA .' AND '.$filters;
	}
	
	$filters = str_replace('"', '\"', $filters);
	
	debug("exist","warning","requete: ".$filters);

	
	// ********************************************************************************
	//

    if($name!=""){	
		$sql = sprintf("SELECT USER_S,NAME_S, DATA_S->'$.\"private-data\".backgroundPicture' 
                        as BACKGROUND, PICTURES->'$.\"profilePicture\"' 
                        as PICTURE, DATA_S->'$.\"pending_invites\"' 
                        as PENDING, DATA_S->'$.\"private-data\".topSong' 
                        as TOPSONG, DATA_S->'$.\"private-data\".skills' 
                        as SKILL, DATA_S->'$.\"private-data\".location.latitude' 
                        as LATITUDE, DATA_S->'$.\"private-data\".location.longitude' 
                        as LONGITUDE, DATA_S->'$.\"private-data\".isPrivate' 
                        as PRIVATE 
                        FROM LIFISTIC_1.LIFISTIC_USERS 
                        WHERE $filters NAME_S COLLATE LATIN1_GENERAL_CI LIKE '%$name%' 
                        AND STATUS_ID = 1 LIMIT 500"); 
	}else{
		$sql = sprintf("SELECT USER_S,NAME_S, DATA_S->'$.\"private-data\".backgroundPicture' 
                        as BACKGROUND, PICTURES->'$.\"profilePicture\"' 
                        as PICTURE, DATA_S->'$.\"pending_invites\"' 
                        as PENDING, DATA_S->'$.\"private-data\".topSong' 
                        as TOPSONG, DATA_S->'$.\"private-data\".skills' 
                        as SKILL, DATA_S->'$.\"private-data\".location.latitude' 
                        as LATITUDE, DATA_S->'$.\"private-data\".location.longitude' 
                        as LONGITUDE, DATA_S->'$.\"private-data\".isPrivate' 
                        as PRIVATE 
                        FROM LIFISTIC_1.LIFISTIC_USERS 
                        WHERE $filters STATUS_ID = 1 LIMIT 500"); 
	}	
    
    unset($res);
   
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
				
			$tmp['id'] = ucfirst($row['USER_S']);
			$tmp['name'] = ucfirst($row['NAME_S']);
			$tmp['picture'] = json_decode($row['PICTURE']);
			$tmp['skills'] = json_decode($row['SKILL'],true);	
			$tmp['background'] = json_decode($row['BACKGROUND'],true);
			
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
			
			if($row['USER_S'] == $userid){	
				$tmp['pending'] = false;
				$tmp['isFriend'] = true;
			}
						

			// ******************** Location
			if(isset($latitude)){
			
				$lat1 = floatval($row['LATITUDE']);
				$lat2 = floatval($latitude);
				$lon1 = floatval($row['LONGITUDE']);
				$lon2 = floatval($longitude);
				
				$yourPlanetRadius = 6371; // change here if you're on another planet :)
				
				$dLat = deg2rad($lat2-$lat1);
				$dLon = deg2rad($lon2-$lon1);
				$a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
				$c = 2 * atan2(sqrt($a), sqrt(1-$a));
				$d = $yourPlanetRadius * $c;
				
				if($d <= $radius){
				
					$names[] = $tmp;
					
				}				
			
			}else{
			
			// ******************** Wrap & send
			$names[] = $tmp;
			
			}
			
		}
		
	}
	
    // ********************************************************************************
	// WRAP & SEND
	
	$answer['results'] = $names;
	$answer['token'] = get_token();
	
	echo json_encode($answer);	
	debug("action-verbose-2","info",session_id().": ".json_encode($answer)." - ".$row['PENDING']);
	
?>
