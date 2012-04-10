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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'gamestore');
define('CSRF_PROTECTION', true);
define('CSRF_SKIP_LIST', '');
session_start();

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('holiday');

// get special data templates from the datastore
$specialtemplates = array(
	'userstats',
	'birthdaycache',
	'maxloggedin',
	'iconcache',
	'eventcache',
	'mailqueue',
	'activeblocks',
);

// pre-cache templates used by all actions
$globaltemplates = array(
	'ad_board_after_forums',
	'ad_board_below_whats_going_on',
	'block_blogentries',
	'block_cmsarticles',
	'block_newposts',
	'block_sgdiscussions',
	'block_tagcloud',
	'block_threads',
	'block_html',
	'FORUMHOME',
	'forumhome_event',
	'forumhome_loggedinuser',
	'forumhome_moderator',
	'forumhome_markread_script',
	'forumhome_birthdaybit',
	'tag_cloud_link',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/functions_forumlist.php');
require_once('./gamefunction.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

//verify_forum_url($vbulletin->options['forumhome']);

($hook = vBulletinHook::fetch_hook('forumhome_start')) ? eval($hook) : false;


// get permissions to view forumhome
if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
{
	print_no_permission();
}

if (in_array($vbulletin->userinfo['usergroupid'], explode(',', '8')))
{
	print_no_permission();
}

$gamecategories = $db->query_read("SELECT name FROM ".TABLE_PREFIX."gamecategory WHERE catid = {$_REQUEST['catid']}");
$gamecategory = $db->fetch_array($gamecategories);
$navbits = array('forum.php' => 'Diễn đàn',
				'gamestore.php' => 'Game Store',
				'' => $gamecategory['name']);

$today = vbdate('Y-m-d', TIMENOW, false, false);

// ### TODAY'S BIRTHDAYS #################################################
if ($vbulletin->options['showbirthdays'])
{
	if (!is_array($vbulletin->birthdaycache)
		OR ($today != $vbulletin->birthdaycache['day1'] AND $today != $vbulletin->birthdaycache['day2'])
		OR !is_array($vbulletin->birthdaycache['users1'])
	)
	{
		// Need to update!
		require_once(DIR . '/includes/functions_databuild.php');
		$birthdaystore = build_birthdays();
		DEVDEBUG('Updated Birthdays');
	}
	else
	{
		$birthdaystore = $vbulletin->birthdaycache;
	}

	switch ($today)
	{
		case $birthdaystore['day1']:
			$birthdaysarray = $birthdaystore['users1'];
			break;

		case $birthdaystore['day2']:
			$birthdaysarray = $birthdaystore['users2'];
			break;

		default:
			$birthdaysarray = array();
	}
	// memory saving
	unset($birthdaystore);

	$birthdaybits = array();

	foreach ($birthdaysarray AS $birthday)
	{
		$templater = vB_Template::create('forumhome_birthdaybit');
			$templater->register('birthday', $birthday);
		$birthdaybits[] = $templater->render();
	}

	$birthdays = implode('', $birthdaybits);

	if (vB_Template_Runtime::fetchStyleVar('dirmark'))
	{
		$birthdays = str_replace('<!--rlm-->', vB_Template_Runtime::fetchStyleVar('dirmark'), $birthdays);
	}

	$show['birthdays'] = iif ($birthdays, true, false);
}
else
{
	$show['birthdays'] = false;
}

// ### TODAY'S EVENTS #################################################
if ($vbulletin->options['showevents'])
{
	require_once(DIR . '/includes/functions_calendar.php');

	$future = gmdate('n-j-Y' , TIMENOW + 86400 + 86400 * $vbulletin->options['showevents']);

	if (!is_array($vbulletin->eventcache) OR $future != $vbulletin->eventcache['date'])
	{
		// Need to update!
		$eventstore = build_events();
		DEVDEBUG('Updated Events');
	}
	else
	{
		$eventstore = $vbulletin->eventcache;
	}

	unset($eventstore['date']);
	$events = array();
	$eventcount = 0;
	$holiday_calendarid = 0;

	foreach ($eventstore AS $eventid => $eventinfo)
	{
		$offset = $eventinfo['dst'] ? $vbulletin->userinfo['timezoneoffset'] : $vbulletin->userinfo['tzoffset'];
		$eventstore["$eventid"]['dateline_from_user'] = $eventinfo['dateline_from_user'] = $eventinfo['dateline_from'] + $offset * 3600;
		$eventstore["$eventid"]['dateline_to_user'] = $eventinfo['dateline_to_user'] = $eventinfo['dateline_to'] + $offset * 3600;
		$gettime = TIMENOW - $vbulletin->options['hourdiff'];
		$iterations = 0;
		$todaydate = getdate($gettime);

		if (!$eventinfo['singleday'] AND !$eventinfo['recurring'] AND $eventinfo['dateline_from_user'] < gmmktime(0, 0, 0, $todaydate['mon'], $todaydate['mday'], $todaydate['year']))
		{
			$sub = -3;
		}
		else if (!empty($eventinfo['holidayid']))
		{
			$sub = -2;
		}
		else if ($eventinfo['singleday'])
		{
			$sub = -1;
		}
		else
		{
			$sub = $eventinfo['dateline_from_user'] - (86400 * (intval($eventinfo['dateline_from_user'] / 86400)));
		}

		if ($vbulletin->userinfo['calendarpermissions']["$eventinfo[calendarid]"] & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar'] OR ($eventinfo['holidayid'] AND $vbulletin->options['showholidays']))
		{
			if ($eventinfo['holidayid'] AND $vbulletin->options['showholidays'])
			{
				if (!$holiday_calendarid)
				{
					$holiday_calendarid = -1; // stop this loop from running again in the future
					if (is_array($eventinfo['holiday_calendarids']))
					{
						foreach ($eventinfo['holiday_calendarids'] AS $potential_holiday_calendarid)
						{
							if ($vbulletin->userinfo['calendarpermissions']["$potential_holiday_calendarid"] & $vbulletin->bf_ugp_calendarpermissions['canviewcalendar'])
							{
								$holiday_calendarid = $potential_holiday_calendarid;
								break;
							}
						}
					}
				}

				if ($holiday_calendarid < 0)
				{
					continue;
				}

				$eventstore["$eventid"]['calendarid'] = $holiday_calendarid;
				$eventinfo['calendarid'] = $holiday_calendarid;
			}

			if ($eventinfo['userid'] == $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['calendarpermissions']["$eventinfo[calendarid]"] & $vbulletin->bf_ugp_calendarpermissions['canviewothersevent'] OR ($eventinfo['holidayid'] AND $vbulletin->options['showholidays']))
			{
				if (!$eventinfo['recurring'] AND !$vbulletin->options['showeventtype'] AND !$eventinfo['singleday'] AND cache_event_info($eventinfo, $todaydate['mon'], $todaydate['mday'], $todaydate['year']))
				{
					$events["$eventid"][] = $gettime . "_$sub";
				}
				else
				{
					while ($iterations < $vbulletin->options['showevents'])
					{
						$addcache = false;

						$todaydate = getdate($gettime);
						if (isset($eventinfo['holidayid']) AND $eventinfo['holidayid'] AND $eventinfo['recurring'] == 6)
						{
							if ($eventinfo['recuroption'] == "$todaydate[mon]|$todaydate[mday]")
							{
								$addcache = true;
							}
						}
						else if (cache_event_info($eventinfo, $todaydate['mon'], $todaydate['mday'], $todaydate['year']))
						{
							$addcache = true;
						}

						if ($addcache)
						{
							if (!$vbulletin->options['showeventtype'])
							{
								$events["$eventid"][] = $gettime . "_$sub";
							}
							else
							{
								$events["$gettime"][] = $eventid;
							}
							$eventcount++;
						}

						$iterations++;
						$gettime += 86400;
					}
				}
			}
		}
	}

	if (!empty($events))
	{
		if ($vbulletin->options['showeventtype'])
		{
			ksort($events, SORT_NUMERIC);
		}
		else
		{
			function groupbyevent($a, $b)
			{
				if ($a[0] == $b[0])
				{
					return 0;
				}
				else
				{
					$values1 = explode('_', $a[0]);
					$values2 = explode('_', $b[0]);
					if ($values1[0] != $values2[0])
					{
						return ($values1[0] < $values2[0]) ? -1 : 1;
					}
					else
					{
						// Same day events. Check the event start time to order them properly (compare number of seconds from 00:00)
						return ($values1[1] < $values2[1]) ? -1 : 1;
					}
				}
			}
			uasort($events, 'groupbyevent');
			// this crazy code is to remove $sub added above that ensures a event maintains its position after the sort
			// if associative values are the same
			foreach($events AS $eventid => $times)
			{
				foreach ($times AS $key => $time)
				{
					$events["$eventid"]["$key"] = intval($time);
				}
			}
		}

		$upcomingevents = '';
		foreach($events AS $index => $value)
		{
			$pastevent = 0;
			$pastcount = 0;

			$comma = $eventdates = $daysevents = '';
			if (!$vbulletin->options['showeventtype'])
			{	// Group by Event // $index = $eventid
				$eventinfo = $eventstore["$index"];
				if (empty($eventinfo['recurring']) AND empty($eventinfo['singleday']))
				{	// ranged event -- show it from its real start and real end date (vbgmdate)
					$fromdate = vbdate($vbulletin->options['dateformat'], $eventinfo['dateline_from_user'], false, true, false, true);
					$todate = vbdate($vbulletin->options['dateformat'], $eventinfo['dateline_to_user'], false, true, false, true);
					if ($fromdate != $todate)
					{
						$eventdates = construct_phrase($vbphrase['event_x_to_y'], $fromdate, $todate);
					}
					else
					{
						$eventdates = vbdate($vbulletin->options['dateformat'], $eventinfo['dateline_from_user'], false, true, false, true);
					}
					$day = vbdate('Y-n-j', $eventinfo['dateline_from_user'], false, false);
				}
				else
				{
					unset($day);
					foreach($value AS $key => $dateline)
					{
						if (($dateline - 86400) == $pastevent AND !$eventinfo['holidayid'])
						{
							$pastevent = $dateline;
							$pastcount++;
							continue;
						}
						else
						{
							if ($pastcount)
							{
								$eventdates = construct_phrase($vbphrase['event_x_to_y'], $eventdates, vbdate($vbulletin->options['dateformat'], $pastevent, false, true, false));
							}
							$pastcount = 0;
							$pastevent = $dateline;
						}
						if (!$day)
						{
							$day = vbdate('Y-n-j', $dateline, false, false, false);
						}
						$eventdates .= $comma . vbdate($vbulletin->options['dateformat'], $dateline, false, true, false);
						$comma = ', ';
					}
					if ($pastcount)
					{
						$eventdates = construct_phrase($vbphrase['event_x_to_y'], $eventdates, vbdate($vbulletin->options['dateformat'], $pastevent, false, true, false));
					}
				}

				if ($eventinfo['holidayid'])
				{
					$callink = '<a href="calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;day=$day&amp;c=$eventinfo[calendarid]\">" . $vbphrase['holiday' . $eventinfo['holidayid'] . '_title'] . "</a>";
				}
				else
				{
					$callink = '<a href="calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;day=$day&amp;e=$eventinfo[eventid]&amp;c=$eventinfo[calendarid]\">$eventinfo[title]</a>";
				}
			}
			else
			{	// Group by Date
				$eventdate = vbdate($vbulletin->options['dateformat'], $index, false, true, false);

				$day = vbdate('Y-n-j', $index, false, false, false);
				foreach($value AS $key => $eventid)
				{
					$eventinfo = $eventstore["$eventid"];
					if ($eventinfo['holidayid'])
					{
						$daysevents .= $comma . '<a href="calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;day=$day&amp;c=$eventinfo[calendarid]\">" . $vbphrase['holiday' . $eventinfo['holidayid'] . '_title'] . "</a>";
					}
					else
					{
						$daysevents .= $comma . '<a href="calendar.php?' . $vbulletin->session->vars['sessionurl'] . "do=getinfo&amp;day=$day&amp;e=$eventinfo[eventid]&amp;c=$eventinfo[calendarid]\">$eventinfo[title]</a>";
					}
					$comma = ', ';
				}
			}

			($hook = vBulletinHook::fetch_hook('forumhome_event')) ? eval($hook) : false;
			$templater = vB_Template::create('forumhome_event');
				$templater->register('callink', $callink);
				$templater->register('daysevents', $daysevents);
				$templater->register('eventdate', $eventdate);
				$templater->register('eventdates', $eventdates);
			$upcomingevents .= $templater->render();
		}
		// memory saving
		unset($events, $eventstore);
		$show['upcomingevents'] = iif ($upcomingevents, true, false);
	}
	$show['todaysevents'] = iif ($vbulletin->options['showevents'] == 1, true, false);
}
else
{
	$show['upcomingevents'] = false;
}

// ### LOGGED IN USERS #################################################
$activeusers = '';
if (($vbulletin->options['displayloggedin'] == 1 OR $vbulletin->options['displayloggedin'] == 2 OR ($vbulletin->options['displayloggedin'] > 2 AND $vbulletin->userinfo['userid'])) AND !$show['search_engine'])
{
	$datecut = TIMENOW - $vbulletin->options['cookietimeout'];
	$numbervisible = 0;
	$numberregistered = 0;
	$numberguest = 0;

	$hook_query_fields = $hook_query_joins = $hook_query_where = '';
	($hook = vBulletinHook::fetch_hook('forumhome_loggedinuser_query')) ? eval($hook) : false;

	$forumusers = $db->query_read_slave("
		SELECT
			user.username, (user.options & " . $vbulletin->bf_misc_useroptions['invisible'] . ") AS invisible, user.usergroupid, user.lastvisit,
			session.userid, session.inforum, session.lastactivity, session.badlocation,
			IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid
			$hook_query_fields
		FROM " . TABLE_PREFIX . "session AS session
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = session.userid)
		$hook_query_joins
		WHERE session.lastactivity > $datecut
			$hook_query_where
		" . iif($vbulletin->options['displayloggedin'] == 1 OR $vbulletin->options['displayloggedin'] == 3, "ORDER BY username ASC") . "
	");

	if ($vbulletin->userinfo['userid'])
	{
		// fakes the user being online for an initial page view of index.php
		$vbulletin->userinfo['joingroupid'] = iif($vbulletin->userinfo['displaygroupid'], $vbulletin->userinfo['displaygroupid'], $vbulletin->userinfo['usergroupid']);
		$userinfos = array
		(
			$vbulletin->userinfo['userid'] => array
			(
				'userid'            =>& $vbulletin->userinfo['userid'],
				'username'          =>& $vbulletin->userinfo['username'],
				'invisible'         =>& $vbulletin->userinfo['invisible'],
				'inforum'           => 0,
				'lastactivity'      => TIMENOW,
				'lastvisit'         =>& $vbulletin->userinfo['lastvisit'],
				'usergroupid'       =>& $vbulletin->userinfo['usergroupid'],
				'displaygroupid'    =>& $vbulletin->userinfo['displaygroupid'],
				'infractiongroupid' =>& $vbulletin->userinfo['infractiongroupid'],
			)
		);
	}
	else
	{
		$userinfos = array();
	}
	$inforum = array();

	while ($loggedin = $db->fetch_array($forumusers))
	{
		$userid = $loggedin['userid'];
		if (!$userid)
		{	// Guest
			$numberguest++;
			if (!isset($inforum["$loggedin[inforum]"]))
			{
				$inforum["$loggedin[inforum]"] = 0;
			}
			if (!$loggedin['badlocation'])
			{
				$inforum["$loggedin[inforum]"]++;
			}
		}
		else if (empty($userinfos["$userid"]) OR ($userinfos["$userid"]['lastactivity'] < $loggedin['lastactivity']))
		{
			$userinfos["$userid"] = $loggedin;
		}
	}

	if (!$vbulletin->userinfo['userid'] AND $numberguest == 0)
	{
		$numberguest++;
	}

	$skipgroups = array(3,4);
	foreach ($userinfos AS $userid => $loggedin)
	{
		if (in_array($loggedin['usergroupid'], $skipgroups))
		{
			$numberguest++;
		}
		else
		{
			$numberregistered++;
			if ($userid != $vbulletin->userinfo['userid'] AND !$loggedin['badlocation'])
			{
				if (!isset($inforum["$loggedin[inforum]"]))
				{
					$inforum["$loggedin[inforum]"] = 0;
				}
				$inforum["$loggedin[inforum]"]++;
			}
			fetch_musername($loggedin);

			($hook = vBulletinHook::fetch_hook('forumhome_loggedinuser')) ? eval($hook) : false;

			if (fetch_online_status($loggedin))
			{
				$numbervisible++;
				$activeusers[] = $loggedin;
			}
		}
	}

	// memory saving
	unset($userinfos, $loggedin);

	$db->free_result($forumusers);

	$totalonline = $numberregistered + $numberguest;
	$numberinvisible = $numberregistered - $numbervisible;

	// ### MAX LOGGEDIN USERS ################################
	if (intval($vbulletin->maxloggedin['maxonline']) <= $totalonline)
	{
		$vbulletin->maxloggedin['maxonline'] = $totalonline;
		$vbulletin->maxloggedin['maxonlinedate'] = TIMENOW;
		build_datastore('maxloggedin', serialize($vbulletin->maxloggedin), 1);
	}

	$recordusers = vb_number_format($vbulletin->maxloggedin['maxonline']);
	$recorddate = vbdate($vbulletin->options['dateformat'], $vbulletin->maxloggedin['maxonlinedate'], true);
	$recordtime = vbdate($vbulletin->options['timeformat'], $vbulletin->maxloggedin['maxonlinedate']);

	$show['loggedinusers'] = true;
}
else
{
	$show['loggedinusers'] = false;
}

// ### GET FORUMS & MODERATOR iCACHES ########################

cache_ordered_forums(1, 1);
if ($vbulletin->options['showmoderatorcolumn'])
{
	cache_moderators();
}
else if ($vbulletin->userinfo['userid'])
{
	cache_moderators($vbulletin->userinfo['userid']);
}
// define max depth for forums display based on $vbulletin->options[forumhomedepth]
/*
define('MAXFORUMDEPTH', $vbulletin->options['forumhomedepth']);

$forumbits = construct_forum_bit($forumid);
$forumhome_markread_script = vB_Template::create('forumhome_markread_script')->render();
*/
$catid = $_REQUEST['catid'];

$forumbits = <<<eot
<link type="text/css" href="games/css/style.css" rel="stylesheet" />
<div class="forumbit_nopost L1">
	<div class="forumhead foruminfo L1 collapse">
		<span style="width:15%;"></span>
		<span style="width:30%;">Trò chơi</span>
		<span style="width:15%;">Đánh giá</span>
		<span style="width:20%;">Cao thủ</span>
		<span style="width:20%;">Kỷ lục</span>
	</div>
	
	<div class="forumbit_nopost L2">
		<div class="forumrow">
			<table class="categorydetail">
				<thead>
					<tr>
						<th width="15%"></th>
						<th width="30%"></th>
						<th width="15%"></th>
						<th width="20%"></th>
						<th width="20%"></th>
					</tr>
				</thead>
				<tbody>
eot;

$navigator = pageNavigation("SELECT *
							 [FROM] (SELECT gid, name, url, icon, width, height, score, username, catid, visible, vote
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
							 AND visible = 1", 20);
$games = $db->query_read($navigator['query']);
while($game = $db->fetch_array($games)){
	$voteData = explode(',', $game['vote']);
	@$voteScore = (array_sum($voteData)/(sizeof($voteData)-1)) * 20;
	$forumbits .= <<<eot
<tr>
	<td style="text-align:center;"><a href="game.php?gid={$game['gid']}"><img src="{$game['icon']}" /></a></td>
	<td><a href="game.php?gid={$game['gid']}">{$game['name']}</a></td>
	<td>
		<div class="vote-bg" style="margin-left: 0;">
			<div class="vote-mark" style="width: {$voteScore}%;"></div>
		</div>
	</td>
	<td>{$game['username']}</td>
	<td>{$game['score']} điểm</td>
</tr>
eot;
}
$forumbits .= <<<eot
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="below_threadlist">
	<div class="threadpagenav">
		<div class="pagination popupmenu nohovermenu" style="text-align: right;">
			{$navigator['pageList']}
		</div>
	</div>
</div>
eot;

// ### BOARD STATISTICS #################################################

// get total threads & posts from the forumcache
$totalthreads = 0;
$totalposts = 0;
if (is_array($vbulletin->forumcache))
{
	foreach ($vbulletin->forumcache AS $forum)
	{
		$totalthreads += $forum['threadcount'];
		$totalposts += $forum['replycount'];
	}
}
$totalthreads = vb_number_format($totalthreads);
$totalposts = vb_number_format($totalposts);

// get total members and newest member from template
$numbermembers = vb_number_format($vbulletin->userstats['numbermembers']);
$newuserinfo = array(
	'userid'   => $vbulletin->userstats['newuserid'],
	'username' => $vbulletin->userstats['newusername']
);
$activemembers = vb_number_format($vbulletin->userstats['activemembers']);
$show['activemembers'] = ($vbulletin->options['activememberdays'] > 0 AND ($vbulletin->options['activememberoptions'] & 2)) ? true : false;

$ad_location['board_after_forums'] = vB_Template::create('ad_board_after_forums')->render();
$ad_location['board_below_whats_going_on'] = vB_Template::create('ad_board_below_whats_going_on')->render();

// ### SIDEBAR #################################################
$gcats = $db->query_read("SELECT catid, name
							FROM " . TABLE_PREFIX . "gamecategory
							WHERE visible = 1
							ORDER BY `order`");
while($gcat = $db->fetch_array($gcats)){
	if($gcat['catid'] == $_SESSION['gcat']) $listcategory .= "<option selected value={$gcat['catid']}>{$gcat['name']}</option>";
	else $listcategory .= "<option value={$gcat['catid']}>{$gcat['name']}</option>";
}

$show['sidebar'] = true;
$sidebar = <<<eot
<link href="games/css/jquery.notice.css" type="text/css" media="screen" rel="stylesheet" />
<script src="games/js/jquery.notice.js" type="text/javascript"></script>
<script type="text/javascript" src="games/js/game.js"></script>
<li>
	<div class="block smaller">
		<div class="blocksubhead">
			<a href="forum.php#top" id="collapse_block_html_1" class="collapse"><img id="collapseimg_html_1" src="images/buttons/collapse_40b.png" alt=""></a>
			<span class="blocktitle">
				Vừa chơi xong
			</span>
		</div>
		<div class="widget_content blockbody floatcontainer">
			<div class="blockrow" id="block_html_2">
				<iframe src="gameticker.php" frameborder=0 width="100%" height="424px"></iframe>
			</div>
		</div>
	</div>
	<div class="underblock"></div>
</li>
eot;

// ### ALL DONE! SPIT OUT THE HTML AND LET'S GET OUTTA HERE... ###
($hook = vBulletinHook::fetch_hook('forumhome_complete')) ? eval($hook) : false;

$navbar = render_navbar_template(construct_navbits($navbits));
$templater = vB_Template::create('FORUMHOME');
	$templater->register_page_templates();
	$templater->register('activemembers', $activemembers);
	$templater->register('activeusers', $activeusers);
	$templater->register('ad_location', $ad_location);
	$templater->register('birthdays', $birthdays);
	$templater->register('forumbits', $forumbits);
	$templater->register('forumhome_markread_script', $forumhome_markread_script);
	$templater->register('navbar', $navbar);
	$templater->register('newuserinfo', $newuserinfo);
	$templater->register('numberguest', $numberguest);
	$templater->register('numbermembers', $numbermembers);
	$templater->register('numberregistered', $numberregistered);
	$templater->register('recorddate', $recorddate);
	$templater->register('recordtime', $recordtime);
	$templater->register('recordusers', $recordusers);
	$templater->register('template_hook', $template_hook);
	$templater->register('today', $today);
	$templater->register('totalonline', $totalonline);
	$templater->register('totalposts', $totalposts);
	$templater->register('totalthreads', $totalthreads);
	$templater->register('upcomingevents', $upcomingevents);
	$templater->register('sidebar', $sidebar);
print_output($templater->render());

/*======================================================================*\
|| ####################################################################
|| # Powered by: Openitvn
|| # Copyright 2011 Openitvn.Net
|| ####################################################################
\*======================================================================*/
