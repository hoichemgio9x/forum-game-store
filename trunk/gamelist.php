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

$catid = (int)$_POST['catid'];
$games = $db->query_read("SELECT *
						  FROM(SELECT gid, name, url, icon, width, height, score, username, catid, visible, vote
							   FROM (SELECT game.*, topscore.score, topscore.uid
									FROM ".TABLE_PREFIX."game AS game, (SELECT game.gid, MAX(score) score, uid
																		FROM ".TABLE_PREFIX."game
																		LEFT JOIN (SELECT *
																				   FROM ".TABLE_PREFIX."gamescore
																				   ORDER BY score DESC) AS gamescore
																		ON gamescore.gid = game.gid
																		GROUP BY game.gid) AS topscore
									WHERE game.gid = topscore.gid) AS topone
							   LEFT JOIN ".TABLE_PREFIX."user AS user
							   ON uid = userid) AS gamedata
						  WHERE catid = {$catid}
						  AND visible = 1
						  ORDER BY RAND()
						  LIMIT 0, 20");

if($db->num_rows($games) == 0){
	echo 'Hiện chưa có game trong danh mục này';
}else{
	echo "<ul id=\"gameitem\">";
	while($game = $db->fetch_array($games)){
		$voteData = explode(',', $game['vote']);
		@$voteScore = (array_sum($voteData)/(sizeof($voteData)-1)) * 20;
		$topscore = number_format($game['score']);
		echo <<<eot
<li>
	<a href="game.php?gid={$game['gid']}">
		<img src="{$game['icon']}" />
		<b>{$game['name']}</b>
	</a>
	<br/>
	<div class="vote-bg">
		<div class="vote-mark" style="width: {$voteScore}%;"></div>
	</div>
	Kỷ lục: {$topscore} điểm
	<br/>
	Ghi bởi: {$game['username']}
	<div class="clear"></div>
</li>

eot;
	}
	echo "</ul><div class=\"readmore\"><a href=\"gamecategory.php?catid={$catid}\">Xem tất cả &gt;&gt;</a></div>";
	$_SESSION['gcat'] = $catid;
}

/*======================================================================*\
|| ####################################################################
|| # Powered by: Openitvn
|| # Copyright 2011 Openitvn.Net
|| ####################################################################
\*======================================================================*/
?>