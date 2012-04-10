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

function pageNavigation($query, $rows_per_page = 10){
	global $db;
	$count_query = explode('[FROM]', $query);
	$count_query[0] = null;
	$count_query = implode('FROM', $count_query);
	$count_query = 'SELECT COUNT(*) `count` ' . $count_query;
	$rs = $db->fetch_array($db->query_read($count_query));
	$num_rows = $rs['count'];
	
	$max_page = 9;
	
	$page = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1;
	
	$limit_start = ($page - 1) * $rows_per_page;
	$limit_end = $rows_per_page;
	$navigator['query'] = str_replace('[FROM]', 'FROM', $query) . " LIMIT {$limit_start}, {$limit_end}";
	
	$url = str_replace(array("?page={$page}","&page={$page}"), '', $_SERVER['REQUEST_URI']);
	if(str_replace('?', '', $url) != $url)
		$param_prefix = '&';
	else
		$param_prefix = '?';
	
	$numpage = ceil($num_rows / $rows_per_page);
	$half_max_page = intval($max_page/2);
	$navigator['pageList'] = '';
	if($numpage > 1){
		for($i = $page - $half_max_page; $i <= $page + $half_max_page; $i++){
			if($i == $page){
				$navigator['pageList'] .= " <span class=\"selected\"><a href=\"javascript:return();\">{$page}</a></span> ";
			}elseif($i > 0 && $i <= $numpage){
				$navigator['pageList'] .= " <span><a href=\"{$url}{$param_prefix}page={$i}\">{$i}</a></span> ";
			}
		}
		if($page - $half_max_page > 1) $navigator['pageList'] = " <a href={$url}{$param_prefix}page=1>&lt;&lt</a> " . $navigator['pageList'];
		if($page + $half_max_page < $numpage) $navigator['pageList'] .= " <a href={$url}{$param_prefix}page={$numpage}>&gt;&gt</a> ";
	}
	
	return $navigator;
}

/*======================================================================*\
|| ####################################################################
|| # Powered by: Openitvn
|| # Copyright 2011 Openitvn.Net
|| ####################################################################
\*======================================================================*/
?>