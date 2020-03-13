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
		$answer['accept_invitation'] = false;
		debug("accept_invitation","warning","session empty userid value");
		echo json_encode($answer);
		return;
	}

	// ********************************************************************************
	// INIT VARIABLES

	$user = "";
	
	if(isset($_POST['user']))
		$user = $_POST['user'];

	if($user ==""){
		debug("accept_invitation","warning","user is empty");
		return;
	}

	$user = strtolower(substr($user,0,256));
	
	// ********************************************************************************
	// GET USER NAME, PENDINGS, FRIENDS AND FOLLOWS DATA
	
	$conn = connect_w1();
	w_tx_start($conn);	
	
	$sql = "SELECT NAME_S as NAME, DATA_S->'$.\"friends\"' as FRIENDS, DATA_S->'$.\"pending_invites\"' as PENDING_INVITES, DATA_S->'$.\"follows\"' as FOLLOWS FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S = '$userid' AND STATUS_ID = 1"; 

	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['accept_invitation'] = false;
		debug("accept_invitation","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("accept_invitation","warning","user not found for some reason: ".$userid);
		$answer['accept_invitation'] = false;
		echo json_encode($answer);
		return;
	}
	
	$pending_invitations = json_decode($row['PENDING_INVITES'],true);
	$friends = json_decode($row['FRIENDS'],true);
	$follows = json_decode($row['FOLLOWS'],true);
	
	$userName = $row['NAME'];

	// ********************************************************************************
	// GET FRIEND NAME, PENDINGS, FRIENDS AND FOLLOWS DATA
	
	$sql = "SELECT DATA_S->'$.\"friends\"' as FRIENDS,DATA_S->'$.\"blocked\"' as BLOCKED, DATA_S->'$.\"pending_invites\"' as PENDING_INVITES, DATA_S->'$.\"follows\"' as FOLLOWS FROM LIFISTIC_1.LIFISTIC_USERS WHERE USER_S = '$user' AND STATUS_ID = 1"; 

	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['accept_invitation'] = false;
		debug("accept_invitation","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}
	
	$row = mysqli_fetch_array($res);
	
	if(!$row){
		debug("accept_invitation","warning","user not found for some reason: ".$user);
		$answer['accept_invitation'] = false;
		echo json_encode($answer);
		return;
	}

	$pending_requester = json_decode($row['PENDING_INVITES'],true);
	$friends_requester = json_decode($row['FRIENDS'],true);
	$blocked = json_decode($row['BLOCKED'],true);
	$follows_requester = json_decode($row['FOLLOWS'],true);
	
	// ********************************************************************************
	// DETECT IF MANDATORY ARRAY ARE POPULATED AND RETURN OR CHANGE VALUES
	
	if($pending_invitations == null){
		debug("accept_invitation","warning","empty pending invitation json");
		$answer['accept_invitation'] = false;
		echo json_encode($answer);
		return;
	}
	
	if($friends == null){
		$friends = [];
	}
	
	if($friends_requester == null){
		$friends_requester = [];
	}
	
	$pos = array_search($user,$pending_invitations); 
	
	$posr = array_search($userid,$pending_requester); 
	
	if($blocked==null)
		$pos2 = false;
		else $pos2 = array_search($userid,$blocked); 

	// ********************************************************************************
	// user blocked the userid after it sent the invitation - should not happen but, for integrity we take the case into account
		
	if($pos2!== false){
		
		if($pos!== false){
		
		unset($pending_invitations[$pos]);
		
		$pend = array();
		
		foreach($pending_invitations as $pending)
			$pend[] = $pending;
		
		$pending_invitations = $pend;
		
		$pending_invitations = json_encode($pending_invitations, JSON_UNESCAPED_UNICODE);
		
		$sql = "UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"pending_invites\"', CAST('$pending_invitations' AS JSON)) WHERE USER_S = '$userid' AND STATUS_ID = 1 "; 
		$res = mysqli_query($conn,$sql);
		
		if(!$res){
			
			debug("accept_invitation","warning","blocked, can't remove invitation mysql error: ".mysqli_error($conn));
			tx_end($conn);
			
			$answer['accept_invitation'] = false;
			echo json_encode($answer);
			return;
		}
		
		debug("accept_invitation","warning","apparently, the user you want to accept has blocked you");
		$answer['accept_invitation'] = false;
		echo json_encode($answer);
		return;
		}
	}
	
	// ********************************************************************************
	// IF FRIEND IS IN USER PENDING INVITATION, DELETE HIM FROM ARRAY AND USER FROM HIS ARRAY
	
	if($pos !== false){
		
		unset($pending_invitations[$pos]);
		
		unset($pending_requester[$posr]);		
		
		$pend = array();
		$pend2 = array();
		
		foreach($pending_invitations as $pending)
			$pend[] = $pending;

		foreach($pending_requester as $pending2)
			$pend2[] = $pending2;
		
		$pending_invitations = $pend;
		$pending_requester = $pend2;
		
		$friends [] = $user;
		$friends_requester [] = $userid;
		
		$follows [] = $user;
		$follows_requester [] = $userid;

		
		
		
		$pending_invitations = json_encode($pending_invitations, JSON_UNESCAPED_UNICODE);
		$pending_requester = json_encode($pending_requester, JSON_UNESCAPED_UNICODE);
		$friends = json_encode($friends, JSON_UNESCAPED_UNICODE);
		$friends_requester = json_encode($friends_requester, JSON_UNESCAPED_UNICODE);
		$follows = json_encode($follows, JSON_UNESCAPED_UNICODE);
		$follows_requester = json_encode($follows_requester, JSON_UNESCAPED_UNICODE);
		
		
		$sql = "UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"pending_invites\"', CAST('$pending_invitations' AS JSON), '$.\"friends\"', CAST('$friends' AS JSON), '$.\"follows\"', CAST('$follows' AS JSON)) WHERE USER_S = '$userid' AND STATUS_ID = 1 "; 
		$sql2 = "UPDATE LIFISTIC_1.LIFISTIC_USERS SET DATA_S = JSON_SET(DATA_S, '$.\"pending_invites\"', CAST('$pending_requester' AS JSON), '$.\"friends\"', CAST('$friends_requester' AS JSON), '$.\"follows\"', CAST('$follows_requester' AS JSON)) WHERE USER_S = '$user' AND STATUS_ID = 1 "; 
		
		$res = mysqli_query($conn,$sql);
		$res2 = mysqli_query($conn,$sql2);
		
		if(!$res || !$res2){
			debug("accept_invitation","warning","double friend transaction didn't work mysql error: ".mysqli_error($conn));
			
			tx_rollback($conn);
			
			$answer['accept_invitation'] = false;
			echo json_encode($answer);
			return;
		}
		
		$answer['accept_invitation'] = true;
		tx_end($conn);
		
	} else {
		debug("accept_invitation","warning","pending invitation not found for user : ".json_decode($user));
		$answer['accept_invitation'] = false;
		echo json_encode($answer);
		return;
	}
	
	// ********************************************************************************
	// CREATE NOTIFICATION
	
	//$data = '{"title" : "'.ucfirst($user).'","text" :"'.ucfirst($userid).' a accepté votre invitation.","user" : {"avatar" : "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAMAAACdt4HsAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyFpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NDkxMSwgMjAxMy8xMC8yOS0xMTo0NzoxNiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpBQzQ5Q0E2QTE2NDIxMUU4QjQyQUYyNkY1QzFEQ0Q2MiIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpBQzQ5Q0E2QjE2NDIxMUU4QjQyQUYyNkY1QzFEQ0Q2MiI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOkFDNDlDQTY4MTY0MjExRThCNDJBRjI2RjVDMURDRDYyIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOkFDNDlDQTY5MTY0MjExRThCNDJBRjI2RjVDMURDRDYyIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+bapbCgAAAGBQTFRF/JWI/ImG/fHx+6KL/HuE+a2q41t4+9HP42F531Vm/G6C85+t+2V/43V9+4p34WZ197rA+3d0/cO23klg/Jyb6XWJ6oqW42p7/ImZ6pih5oKJ/Gtw/OHg319n////////Wbt7YgAAACB0Uk5T/////////////////////////////////////////wBcXBvtAAAEGklEQVR42oyXi3KrIBCGEQVEo6AmEiPa93/Ls6AJC5iebjrtjM3/7UXYBfLzxagV48hYWZaMjaOl375HrtViLE8j7gPGRvpngI3UIPcEYIj9LwDBymBvrbeCkHH/HyB4f0dfBnkBH/ErYEfy03+JAiicMfodQFksLyM56A+E+AYQifc4/+KtLwp9DYj0aQIF+chdGleAK//llbyCT5sDRJnmn9QfAYqqYikg0ROC609C+occgmAxgGJ53ztxD38u0vcZAKISGLD7TXN675mwlFI73j0iy786jSIAWj99afe3iXKI8q8ixDMALNKPOzY2JPGHAKpKfwDsmx4TqmFd1yEi0BMQAujZnhoZvHwYirvWjFSOcVjj3wSJAiA0A1gPWIk4/mV1s771jQuBRAGMe25LB3oUmS1up7xptAeEV9DbC4DYilXjB7RaD0LTVA5A0fq70O+025bkyXr4hx8BAPFbCZ29trQyWlbOP1gLgPGzf74AuiV7ZFbvH37thJb/Bei8sPKMoLHE4v55XYO8tHoyh/6miUANoKeXbyF/qqU6CS0ZUf+7XAfsCjAZT7jdnh7wbsAFucpA5imwSRmlbh7AcP8dRPbddtvyIs4SIqgdoSJRC++ztSi2bpuz/SHrGvRAuDXx9CB9EROEUl23pXHNkzG1Nwcg0QzpBx0VEPSdUjQtIVTgIMQR+PY3zOPxfapfsvO2dTR6h/xUgxnCovbtGvigumVZ5m6bVHfapkIW7cRd/ofxh3sL2fjq5PZaWDtvxqnhl5qmjrlWLZiZVNA7wJj4dzN0eB2lHJWPQcluXl5GQuoc3GM9nwMg9P+hCC8R3EujfQnsMrkFqBTS84XYfP4N4VUy2cn5U0FRy9g/54zQbPwNd7SQlcTLiOIX4PTckh+WnD/iDtjFW8ntIqyvKXQkVL4coNqkF0ms57NrqugAdQAYTiHZHO10yE+Adm39Hg1gGF1DiFpv6eaakH/OdwcQ6fxHIdQmBUiO9K0fLHvsH4bnm0BfU5dFwIOeH6PtR+Djy0GYtaWWGegbNO1FSD+fw3WPzi/eVtjHG3ROMyXt6DEFPbfv84HACZwIWLSmM2YzWQYf/RKOOPf0/AP2MN4mPJcoxwHsAUDzI9QVgZo0gc8xT6RqP3+VA6jpIc4OJacg52180NRejlNohuahlCuFlI+WtUs9SeR/To+6LPF/TF8HgA4uJzCJ3HNDs8M2q5IEnMHeO3cfr1H9+WO/OO6zIH8Pb5h+Uf/56OnlhSMQPhFADHUd7T+f//7lyiOi8I8IIIbEv2y/X7roE1fQy5tboq/tr9c+jfWeAIbDb+l/Lp5UV4n8dkPL3/7h6kv1M9a7GHzwrf3r5Vu0VaQHQL3o/e+3d+gRVrfP55FM9WzZ1/v/PwEGABWWBRmwTeDOAAAAAElFTkSuQmCC"},"canLike" : false,"liked" : false,"isImportant" : true}';
	$data = '{"title" : "'.ucfirst($userName).'","text" :" a accepté votre invitation","user" : {"avatar" : "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAMAAACdt4HsAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyFpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NDkxMSwgMjAxMy8xMC8yOS0xMTo0NzoxNiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpBQzQ5Q0E2QTE2NDIxMUU4QjQyQUYyNkY1QzFEQ0Q2MiIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpBQzQ5Q0E2QjE2NDIxMUU4QjQyQUYyNkY1QzFEQ0Q2MiI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOkFDNDlDQTY4MTY0MjExRThCNDJBRjI2RjVDMURDRDYyIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOkFDNDlDQTY5MTY0MjExRThCNDJBRjI2RjVDMURDRDYyIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+bapbCgAAAGBQTFRF/JWI/ImG/fHx+6KL/HuE+a2q41t4+9HP42F531Vm/G6C85+t+2V/43V9+4p34WZ197rA+3d0/cO23klg/Jyb6XWJ6oqW42p7/ImZ6pih5oKJ/Gtw/OHg319n////////Wbt7YgAAACB0Uk5T/////////////////////////////////////////wBcXBvtAAAEGklEQVR42oyXi3KrIBCGEQVEo6AmEiPa93/Ls6AJC5iebjrtjM3/7UXYBfLzxagV48hYWZaMjaOl375HrtViLE8j7gPGRvpngI3UIPcEYIj9LwDBymBvrbeCkHH/HyB4f0dfBnkBH/ErYEfy03+JAiicMfodQFksLyM56A+E+AYQifc4/+KtLwp9DYj0aQIF+chdGleAK//llbyCT5sDRJnmn9QfAYqqYikg0ROC609C+occgmAxgGJ53ztxD38u0vcZAKISGLD7TXN675mwlFI73j0iy786jSIAWj99afe3iXKI8q8ixDMALNKPOzY2JPGHAKpKfwDsmx4TqmFd1yEi0BMQAujZnhoZvHwYirvWjFSOcVjj3wSJAiA0A1gPWIk4/mV1s771jQuBRAGMe25LB3oUmS1up7xptAeEV9DbC4DYilXjB7RaD0LTVA5A0fq70O+025bkyXr4hx8BAPFbCZ29trQyWlbOP1gLgPGzf74AuiV7ZFbvH37thJb/Bei8sPKMoLHE4v55XYO8tHoyh/6miUANoKeXbyF/qqU6CS0ZUf+7XAfsCjAZT7jdnh7wbsAFucpA5imwSRmlbh7AcP8dRPbddtvyIs4SIqgdoSJRC++ztSi2bpuz/SHrGvRAuDXx9CB9EROEUl23pXHNkzG1Nwcg0QzpBx0VEPSdUjQtIVTgIMQR+PY3zOPxfapfsvO2dTR6h/xUgxnCovbtGvigumVZ5m6bVHfapkIW7cRd/ofxh3sL2fjq5PZaWDtvxqnhl5qmjrlWLZiZVNA7wJj4dzN0eB2lHJWPQcluXl5GQuoc3GM9nwMg9P+hCC8R3EujfQnsMrkFqBTS84XYfP4N4VUy2cn5U0FRy9g/54zQbPwNd7SQlcTLiOIX4PTckh+WnD/iDtjFW8ntIqyvKXQkVL4coNqkF0ms57NrqugAdQAYTiHZHO10yE+Adm39Hg1gGF1DiFpv6eaakH/OdwcQ6fxHIdQmBUiO9K0fLHvsH4bnm0BfU5dFwIOeH6PtR+Djy0GYtaWWGegbNO1FSD+fw3WPzi/eVtjHG3ROMyXt6DEFPbfv84HACZwIWLSmM2YzWQYf/RKOOPf0/AP2MN4mPJcoxwHsAUDzI9QVgZo0gc8xT6RqP3+VA6jpIc4OJacg52180NRejlNohuahlCuFlI+WtUs9SeR/To+6LPF/TF8HgA4uJzCJ3HNDs8M2q5IEnMHeO3cfr1H9+WO/OO6zIH8Pb5h+Uf/56OnlhSMQPhFADHUd7T+f//7lyiOi8I8IIIbEv2y/X7roE1fQy5tboq/tr9c+jfWeAIbDb+l/Lp5UV4n8dkPL3/7h6kv1M9a7GHzwrf3r5Vu0VaQHQL3o/e+3d+gRVrfP55FM9WzZ1/v/PwEGABWWBRmwTeDOAAAAAElFTkSuQmCC"},"canLike" : false,"liked" : false,"isImportant" : true}';
	
	// ********************************************************************************
	// ADD NOTIFICATION
	
	$conn = connect_w1();
	
	$sql = "UPDATE LIFISTIC_1.LIFISTIC_NOTIFICATIONS SET N_LIST = JSON_ARRAY_INSERT(N_LIST, '$.notifications[0]', CAST('$data' AS JSON)) WHERE N_ID='$user'";  
	
	$res = mysqli_query($conn,$sql);
	if(!$res){
		$answer['send_message'] = false;
		debug("send_message","warning","mysql error: ".mysqli_error($conn));
		echo json_encode($answer);
		return;
	}	
	
	// ********************************************************************************
	// WRAP & SEND
	
	$answer['token'] = get_token();
	
	echo json_encode($answer);
	debug("action-verbose-2","info",session_id().": ".json_encode($answer));
	
?>