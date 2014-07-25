<?php
include 'includes/aria2.class.php';
include 'includes/dbConnect.php';
include 'includes/curl.php';

$elog=1;
$starttime = microtime(true);

$db = dbConnect();

$ids = $db->query("SELECT DISTINCT movieid FROM `torrent` WHERE confirmed=1");

while ($rows = mysqli_fetch_object($ids)) {
	$movie = $rows->movieid;
	
	$ranks = $db->query("SELECT score,link,quality FROM `torrent` WHERE movieid='{$movie}' ORDER BY score DESC");
	$a=0;$b=0;$c=0;$d=0;$temp=0;$links='';
	while ($rows1 = mysqli_fetch_object($ranks)) {
		switch($rows1->quality) {
			case 'cam':
				$a++;
				$temp=$a;
				break;
			case 'dvdrip':
				$b++;
				$temp=$b;
				break;
			case '720':
				$c++;
				$temp=$c;
				break;
			case '1080':
				$d++;
				$temp=$d;
				break;
		}
		echo $temp.'<br/>';
		$db->query("UPDATE `torrent` SET rank='{$temp}' WHERE movieid='{$movie}' AND link='{$rows1->link}'".$links);
		$links.=" AND link != '{$rows1->link}'";
		if ($db->error) {echo $db->error;exit;}
	}
}




$db->close();
$endtime = microtime(true);
$timediff = $endtime - $starttime;
echo("execution time: ".$timediff."s");
?>

