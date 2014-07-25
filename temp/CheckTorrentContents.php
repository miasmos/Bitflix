<?php

//what if files are within a folder?
include 'includes/bencoded.php';
include 'includes/dbConnect.php';

//while (true) {
	$torrent = checkDir();

	if ($torrent) {
		$check = checkContents($torrent);
		//echo $check[0].":".$check[1];
		
		$db = dbConnect();
		if (!$check[1]) {	//failed verification
			$db->query("INSERT IGNORE INTO `badtorrents` (`id`) VALUES ('{$check[0]}')");
			$db->query("DELETE FROM `torrent` WHERE magnet='{$check[0]}'");
		}
		else {
			$db->query("UPDATE `torrent` SET confirmed='1' WHERE magnet='{$check[0]}'");
			$return = $db->query("SELECT movieid FROM `torrent` WHERE magnet='{$check[0]}'");
			$return = mysqli_fetch_object($return);
			$return = $return->movieid;
			if (!empty($return)) {UpdateRanks($return);}
		}
		$db->close();
	}
	//else {sleep(10);}
//}

function checkSize($string,$size) {return round($string/100000) < $size;}
function getExt($string) {return substr($string,strrpos($string, ".")+1);}
function hasExt($string) {$ret = strlen(getExt($string)); return $ret <= 4 && $ret > 0;}
function checkExt($string, $extentions) {
	$ext = getExt($string);
	foreach($extentions as $index=>$value) {
		if ($value==$ext) {return $value;}
	}
	return false;
}

function UpdateRanks($movie) {
	global $db;
	if ($movie < 0 || empty($movie)) {return 0;}
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

		$db->query("UPDATE `torrent` SET rank='{$temp}' WHERE movieid='{$movie}' AND link='{$rows1->link}'".$links);
		$links.=" AND link != '{$rows1->link}'";
		if ($db->error) {return 1;echo $db->error;}
	}
	return 1;
}

function checkContents($file) {	//returns array(hash,status)
	$good = array('mpg', 'avi', 'divx', 'xvid', 'mp4', 'mov', 'wmv', 'ogg', 'mpeg', 'm4p', '3gp', 'mkv');
	$bad = array('exe');
	$previewSize = 100; //maximum size of preview video in MB
	
	$be = new BEncoded;
	$be->NewNode('dict', 'child');
	$be->Set('child/key', 'value');
	$be->Set('child/_key', '_val');
	$be->FromFile($file);
	$files=$be->Get('info/files/');
	
	if (empty($files)) {	//no file structure, just the file itself added to torrent
		if (checkExt($be->Get('info/name/'), $good) != false) {
			elog($be->InfoHash()." : ".$be->Get('info/name/')." : ".round($be->Get('info/piece length')/1000)."MB : PASS");
			unlink($file);
			return array($be->InfoHash(),1);
		}	//file is a video
		else {
			elog($be->InfoHash()." : ".$be->Get('info/name/')." : ".round($be->Get('info/piece length')/1000)."MB : FAIL"); 
			unlink($file);
			return array($be->InfoHash(),0);
		}	//file is not a video
	}
	else {	//file structure exists, check it
		$maxSize = $files[0];
		$videoCount=0; $videoSizeCount=0;
		foreach($files as $index=>$value) {
			if (hasExt($value['path'][0])) {$badCheck = checkExt($value['path'][0], $bad);}	
			if ($badCheck != false) { //check for potentially bad files, if found discard torrent
				unlink($file);
				elog($be->InfoHash()." : found ".$badCheck." : FAIL");
				return array($be->InfoHash(),0);
			}
			
			if (checkExt($value['path'][0], $good) != false) {	//check for multiple video files, max of 2 to allow for previews
				$videoCount+=1;							
			}
			if ($videoCount > 3) {	//if 3 or more videos discard torrent
				unlink($file);
				elog($be->InfoHash()." : ".$value['path'][0]." : More than 3 videos (".$videoCount.") : FAIL");
				return array($be->InfoHash(),0);
			}
			
			if ($value['length'] > $maxSize['length'] && hasExt($value['path'][0])) {$maxSize = $value;}
		}
	}
	
	unlink($file);
	$goodCheck=checkExt($maxSize['path'][0], $good);
	$size = round($maxSize['length']/100000);
	
	if ($goodCheck != false) {	//largest file is a video
		elog($be->InfoHash()." : ".$maxSize['path'][0]." : ".$size."MB : PASS");
		return array($be->InfoHash(),1);
	} 
	else {	//largest file is not a video
		elog($be->InfoHash()." : ".$maxSize['path'][0]." : ".$size."MB : FAIL");
		return array($be->InfoHash(),0);
	}
}

function checkDir() {
	$path = dirname(dirname(__FILE__))."/torrents";
	$dir = scandir($path);
	$do = false;

	if (count($dir) < 3) {return false;}
	foreach($dir as $index=>$value) {
		if (!is_dir($value) && pathinfo($value, PATHINFO_EXTENSION) == "torrent") {	//skip folders and files without torrent extension
			if ($do == false) {$do = $path."/".$dir[$index];}
			else {
				if (filemtime($path."/".$dir[$index]) < filemtime($do)) {$do = $path."/".$dir[$index];}
			}
		}	
	}
	return $do;
}

function elog($string) {
	//echo ($string."<br/>");
	error_log(date("Y-m-d H:i:s").": ".$string."\r\n", 3, dirname(dirname(__FILE__)).'/logs/confirmTorrentContents.log');
}
?>

