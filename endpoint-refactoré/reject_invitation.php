<?php
    $endpoint = 'reject_invitation.php';

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
    $user = "";
	
	if(isset($_POST['user']))
		$user = $_POST['user'];

	if($user ==""){
		debug("reject_invitation","warning","user is empty");
		return;
	}	
    $user = strtolower(substr($user,0,256));

	// *********************************************************************************

    $sql = sprintf("SELECT DATA_S->'$.\"pending_invites\"' 
            as PENDING_INVITES 
            FROM LIFISTIC_1.LIFISTIC_USERS 
            WHERE USER_S = '$userid' AND STATUS_ID = 1"); 
    
    $res = standardQuery("read", $sql, $endpoint);
    $row = mysqli_fetch_array($res);
   
    $pending_invitations = json_decode($row['PENDING_INVITES'],true);

    // ********************************************************************************
	//	
	
	if($pending_invitations == null){
		debug("reject_invitation","warning","bad pending invitation json");
		$answer['reject_invitations'] = false;
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	//

    $pos = array_search($user,$pending_invitations); 
	
	if($pos!== false){
		
		unset($pending_invitations[$pos]);
		
		$pend = array();
		
		foreach($pending_invitations as $pending)
			$pend[] = $pending;
		
		$pending_invitations = $pend;
				
		$pending_invitations = json_encode($pending_invitations, JSON_UNESCAPED_UNICODE);
		
		if($pending_invitations == null){
			$pending_invitations = [];
		}

        $sql = sprintf("UPDATE LIFISTIC_1.LIFISTIC_USERS 
                        SET DATA_S = JSON_SET(DATA_S, '$.\"pending_invites\"', 
                        CAST('$pending_invitations' AS JSON)) 
                        WHERE USER_S = '$userid' AND STATUS_ID = 1"); 

        unset($res);

        $res = standardQuery("read", $sql, $endpoint);
        
        // ********************************************************************************
	    // WRAP & SEND	
	
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));
	
?>
