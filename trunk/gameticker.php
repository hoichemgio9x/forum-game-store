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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" rev="stylesheet" href="/templates/default/vtip/css/vtip.css" type="text/css" />
<script type="text/javascript" src="/forum/clientscript/jquery-1.6.1.min.js"></script>
<link type="text/css" href="games/css/style.css" rel="stylesheet" />
<script>
var userid;
var gid;
var score;
function get_ticker(){
	// Get the data
	$.ajax({
		type: "GET",
		dataType: "json",
		cache: false,
		url: "games/gameticker.log",
		success: function(data) {
			$.each(data, function(i, j){
				if(userid != j.userid || gid != j.gid || score != j.score){
					$("#ticker-0").hide();
					$("#ticker-4").show();
					$("#ticker-4").html($("#ticker-3").html());
					$("#ticker-3").html($("#ticker-2").html());
					$("#ticker-2").html($("#ticker-1").html());
					$("#ticker-1").html($("#ticker-0").html());
					$("#ticker-0").html('<img class=\"avatar\" src=\"image.php?u='+j.userid+'\" /> <div class=\"oneline\"><b>'+j.username+'</b></div><div class=\"tickercontent\"><img class=\"icon\" src=\"'+j.gameicon+'\" width=40 height=40 /><span class=\"oneline\"><b>'+j.gamename+'</b></span><br/>'+j.score+' điểm<br/><a href=\"game.php?gid='+j.gid+'\" target=\"_parent\"><img src=\"games/images/icons/gamepad.gif\" /> Chơi luôn</a></div>');
					$("#ticker-4").fadeOut(1000);
					$("#ticker-0").slideDown(2000);
					
					userid = j.userid;
					gid = j.gid;
					score = j.score;
				}
			});
		}
	});
}
</script>
</head>

<body style="margin:0;padding:0;">
<ul class="playticker">
<?php
$data[] = json_decode(file_get_contents('games/gameticker.log'), true);
$data[] = json_decode(file_get_contents('games/gameticker2.log'), true);
$data[] = json_decode(file_get_contents('games/gameticker3.log'), true);
$data[] = json_decode(file_get_contents('games/gameticker4.log'), true);
for($i = 0; $i < 4; $i++){
$detail = $data[$i][0];
echo <<<eot
<li id="ticker-{$i}">
	<img class="avatar" src="image.php?u={$detail['userid']}" /> <div class="oneline"><b>{$detail['username']}</b></div>
	<div class="tickercontent">
		<img class="icon" src="{$detail['gameicon']}" width=40 height=40 /> <span class="oneline"><b>{$detail['gamename']}</b></span>
		<br/>
		{$detail['score']} điểm
		<br/>
		<a href="game.php?gid={$detail['gid']}" target="_parent"><img src="games/images/icons/gamepad.gif" /> Chơi luôn</a>
	</div>
</li>
<div class="clear"></div>
eot;
}
echo '<li id="ticker-4"></li><div class="clear"></div>';
?>
<!--
	<li id="ticker-0"></li><div class="clear"></div>
	<li id="ticker-1"></li><div class="clear"></div>
	<li id="ticker-2"></li><div class="clear"></div>
	<li id="ticker-3"></li><div class="clear"></div>
	<li id="ticker-4"></li><div class="clear"></div>
-->
</ul>
<script>
$(document).ready(function(){
	userid = <?php echo $data[0][0]['userid'] ?>;
	gid = <?php echo $data[0][0]['gid']; ?>;
	score = '<?php echo $data[0][0]['score']; ?>';
	setInterval("get_ticker()", 7000);
});
</script>
</body>
</html>
<?php

/*======================================================================*\
|| ####################################################################
|| # Powered by: Openitvn
|| # Copyright 2011 Openitvn.Net
|| ####################################################################
\*======================================================================*/
?>