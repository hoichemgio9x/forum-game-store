<?php
/*======================================================================*\
|| #################################################################### ||
|| #                     Forum Game Store v1.0                        # ||
|| # ---------------------------------------------------------------- # ||
|| # Lap trinh va phat trien boi Lord Kaj. Tac gia bao luu moi quyen. # ||
|| # Ban quyen thuoc Dien dan CNTT Openitvn.Net.                      # ||
|| # Hay ton trong tac gia bang cach khong thay doi credit khi phat   # ||
|| # hanh lai bat cu phan nao cua phan mem nay! Xin cam on!           # ||
|| # ------------- CHAO MUNG DEN VOI DIEN DAN OPENITVN -------------- # ||
|| #    Website: http://openitvn.net | Email: mrlordkaj@gmail.com     # ||
|| #################################################################### ||
\*======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CSRF_PROTECTION', false);
define('CSRF_SKIP_LIST', '');

// #################### PRE-CACHE TEMPLATES AND DATA ######################

// pre-cache templates used by specific actions
//$actiontemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$gameid = (int)$_REQUEST['gid'];
$score = (int)$_REQUEST['sc'];
$security = $_REQUEST['sec'];
$method = (int)$_REQUEST['mt'];

if($vbulletin->userinfo['userid']){
	if(($method = 0 && md5($gameid . '_' . $score . '_openitvn') == $security)
	   || ($method = 1 && md5($gameid . '.' . $score . '.gameta') == $security)){
		$scoredetails = $db->query_read("SELECT * FROM (SELECT score, name, icon, game.gid
														FROM ".TABLE_PREFIX."gamescore gamescore RIGHT JOIN ".TABLE_PREFIX."game game
														ON game.gid = gamescore.gid
														AND uid = {$vbulletin->userinfo['userid']}
														AND gamescore.gid={$gameid}) game
										 WHERE gid = {$gameid}");
		$scoredetail = $db->fetch_array($scoredetails);
		$highscore = (int)$scoredetail['score'];
		$highscoreFormated = number_format($highscore);
		if($highscore < $score){
			if($db->num_rows($scoredetails) && !empty($scoredetail['score'])){
				$db->query_write("UPDATE ".TABLE_PREFIX."gamescore SET score = {$score}, time = ".time()." WHERE gid = {$gameid} AND uid = {$vbulletin->userinfo['userid']}");
			}else{
				$db->query_write("INSERT INTO ".TABLE_PREFIX."gamescore(gid, uid, score, time) VALUES({$gameid}, {$vbulletin->userinfo['userid']}, {$score}, " . time() . ")");
			}
			echo 'Điểm của bạn đã được lưu vào hệ thống!';
			//create log
			/*
			$avatars = $db->query_read("SELECT filedata FROM ".TABLE_PREFIX."customavatar WHERE userid = {$vbulletin->userinfo['userid']}");
			$avatar = $db->fetch_array($avatars);
			*/
			$data = array(
				'userid' => $vbulletin->userinfo['userid'],
				'username' => $vbulletin->userinfo['username'],
				'gid' => $gameid,
				'gamename' => $scoredetail['name'],
				'gameicon' => $scoredetail['icon'],
				'score' => number_format($score)
			);
			//Rename log #3 to #4
			$logContent = file_get_contents('games/gameticker3.log');
			$fp = file_put_contents('games/gameticker4.log', $logContent);
			//Rename log #2 to #3
			$logContent = file_get_contents('games/gameticker2.log');
			$fp = file_put_contents('games/gameticker3.log', $logContent);
			//Rename log #1 to #2
			$logContent = file_get_contents('games/gameticker.log');
			$fp = file_put_contents('games/gameticker2.log', $logContent);
			#1
			$logContent = '[' . json_encode($data) . ']';
			$fp = fopen('games/gameticker.log', 'w+');
			fwrite($fp, $logContent);
			fclose($fp);
			//end create log
		}else{
			echo "Số điểm lần trước của bạn là {$highscoreFormated} điểm, bạn vẫn chưa vượt qua được ngưỡng của chính mình!";
		}
	}else{
		echo 'Yêu cầu gửi điểm không hợp lệ!';
	}
}else{
	echo 'Bạn hãy đăng nhập trước khi chơi để lưu điểm vào hệ thống và tranh tài với những thành viên khác!';
}

/*======================================================================*\
|| ####################################################################
|| # Powered by: Openitvn
|| # Copyright 2011 Openitvn.Net
|| ####################################################################
\*======================================================================*/
?>