<?php
    $endpoint = 'get_culture.php';

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

    $sql = sprintf("SELECT DATA_S->'$.\"follows\"' as FOLLOWS 
            FROM LIFISTIC_1.LIFISTIC_USERS 
            WHERE USER_S = '$userid' AND STATUS_ID = 1");

    $res = standardQuery("write", $sql, $endpoint);
    $row = mysqli_fetch_array($res);

    $follows = json_decode($row['FOLLOWS'], true);

    / CHECK IF FOLLOWERS LIST IS POPULATED
	
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

        unset($res);
        unset($row);

        $sql = sprintf("UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"follows\"', CAST('$follows' AS JSON))  
                        WHERE USER_S = '$userid' AND STATUS_ID = 1 ");
                        
        $res = standardQuery("write", $sql, $endpoint);
        $row = mysqli_fetch_array($res);

        
	    // ********************************************************************************
	    // WRAP & SEND	
	
	    $answer['token'] = get_token();
	
	    echo json_encode($answer);
	    debug("action-verbose-2","info",session_id().": ".json_encode($answer));
	
?
