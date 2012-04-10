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
session_start();

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
//$phrasegroups = array('banning', 'cpuser', 'cpglobal');

// get special data templates from the datastore
//$specialtemplates = array();

// pre-cache templates used by all actions
/*
$globaltemplates = array(
	'bannedusers',
	'bannedusers_bit'
);
*/

// pre-cache templates used by specific actions
//$actiontemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

if (empty($_REQUEST['do']) OR !in_array($_REQUEST['do'], array('perm', 'temp')))
{
	$_REQUEST['do'] = 'perm';
}

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

switch($_REQUEST['act']){
	case 'highscore':
		$gid = (int)$_REQUEST['gid'];
		
		$topplayers = $db->query_read("SELECT uid, username, score, time, opentag, closetag, ucash
									   FROM ".TABLE_PREFIX."gamescore AS gamescore, ".TABLE_PREFIX."user AS user, ".TABLE_PREFIX."usergroup AS usergroup
									   WHERE gamescore.uid = user.userid
									   AND user.usergroupid = usergroup.usergroupid
									   AND gid = {$gid}
									   ORDER BY score DESC
									   LIMIT 0, 10");
		$highscore = "";
		$i = 0;
		while($topplayer = $db->fetch_array($topplayers)){
			$i++;
			$score = number_format($topplayer['score']);
			$time = gmdate('H:i', $topplayer['time'] + 7*3600) . ' ngày ' . gmdate('d/m/Y', $topplayer['time'] + 7*3600);
			$ucash = number_format((int)$topplayer['ucash']);
			echo <<<eot
<tr>
	<td>{$i}</td>
	<td>{$topplayer['opentag']}{$topplayer['username']}{$topplayer['closetag']}</td>
	<td>{$score}</td>
	<th>{$ucash} xu</th>
	<td>{$time}</td>
</tr>
eot;
		}
		break;
	case 'rating':
		$gid = (int)$_REQUEST['gid'];
		$mark = (int)$_REQUEST['m'];
		if(!$vbulletin->userinfo['userid']){
			echo 'Bạn không có quyền thực hiện thao tác này!';
		}else{
			$query = "SELECT COUNT(*) AS count FROM ".TABLE_PREFIX."game WHERE CONCAT(',', uservote, ',') LIKE '%,{$vbulletin->userinfo['userid']},%' AND gid = {$gid}";
			$checks = $db->query_read($query);
			$check = $db->fetch_array($checks);
			if($check['count'] == 0){
				$query = "UPDATE ".TABLE_PREFIX."game
						  SET vote = CONCAT(vote, ',{$mark}'),
							  uservote = CONCAT(uservote, ',{$vbulletin->userinfo['userid']}')
						  WHERE gid = {$gid}";
				$db->query_write($query);
				$votes = $db->query_read("SELECT vote, uservote FROM ".TABLE_PREFIX."game WHERE gid = {$gid}");
				$vote = $db->fetch_array($votes);
				$voteData = explode(',', $vote['vote']);
				$voteCount = sizeof($voteData) - 1;
				@$voteScore = intval(array_sum($voteData)/$voteCount);
				echo <<<eot
<div id="rating" class="vote">
	<div style="float: left; line-height: 20px; font-weight: bold;">Đánh giá: &nbsp;</div>
	<div class="rating star_{$voteScore}"></div>
	<div style="float: left; line-height: 20px;">&nbsp;&nbsp;Cám ơn bạn đã đánh giá game!</div>
</div>
eot;
			}else{
				$votes = $db->query_read("SELECT vote, uservote FROM ".TABLE_PREFIX."game WHERE gid = {$gid}");
				$vote = $db->fetch_array($votes);
				$voteData = explode(',', $vote['vote']);
				$voteCount = sizeof($voteData) - 1;
				@$voteScore = intval(array_sum($voteData)/$voteCount);
				echo <<<eot
<div id="rating" class="vote">
	<div style="float: left; line-height: 20px; font-weight: bold;">Đánh giá: &nbsp;</div>
	<div class="rating star_{$voteScore}"></div>
	<div style="float: left; line-height: 20px;">&nbsp;&nbsp;Bạn đã đánh giá game này rồi!</div>
</div>
eot;
			}
		}
		break;
	default:
		break;
}

/*======================================================================*\
|| ####################################################################
|| # Powered by: Openitvn
|| # Copyright 2011 Openitvn.Net
|| ####################################################################
\*======================================================================*/
?>