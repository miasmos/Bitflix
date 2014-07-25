<?php
include 'includes/dbConnect.php';
include 'includes/queryMovieAPI.php';
include 'includes/curl.php';

$starttime = microtime(true);
$db = dbConnect();

configCheck();
//deleteUnconfirmedTorrents();
listUpdates();
movieUpdates();
$db->close();

function configCheck() {	//check for movie db url changes
	global $db;
	$datas = queryMovieAPI(0,'configuration');
	print_r($datas);
	if ($datas !== null) {
		$db->query("INSERT into `settings` (`name`,`value`) VALUES ('baseURL','".$datas->images->base_url."') ".
			"ON DUPLICATE KEY UPDATE `settings` SET value='".$datas->images->base_url."'");
		$db->query("INSERT into `settings` (`name`,`value`) VALUES ('baseURLSecure','".$datas->images->secure_base_url."') ".
			"ON DUPLICATE KEY UPDATE `settings` SET value='".$datas->images->secure_base_url."'");
	}
}

function deleteUnconfirmedTorrents() {
	global $db;
	$result = $db->query("SELECT magnet FROM `torrent` WHERE confirmed='0' AND date(torrent.lastupdate) < DATE_SUB(NOW(), INTERVAL 1 DAY)");

	while ($row = mysqli_fetch_object($result)) {
		$magnet = getMagnetLink($row->magnet);
		$db->query("INSERT IGNORE INTO `badtorrents` (`id`) VALUES ('{$magnet}')");
		elog($db->error);
	}
	$db->query("DELETE FROM `torrent` WHERE confirmed='0' AND date(torrent.lastupdate) < DATE_SUB(NOW(), INTERVAL 1 DAY)");
}

function listUpdates() {	//id, list (name of list), name (name of entry), value (value of entry), rank (rank of entry in list), date added
	global $db;
	sleep(10);
	$starttime = microtime(true); $querycount=0;
	$searches = array('upcoming','now_playing','top_rated','popular_actors');
	
	$errors=0;
	foreach($searches as $id=>$value) {
		$pages=1;
		$delete=false; $break=false;
		$error=0;
		for($i=1;$i<=$pages;$i++) {
			$datas = queryMovieAPI(0,$value,$i);
			$endtime = microtime(true);
			$time=$endtime-$starttime;
			if ($time >= 10) {$starttime = microtime(true); $querycount=0;}
			elseif ($querycount >= 30 && $time < 10 && $time > 0) {$time=11-$time; elog('sleeping for '.$time.' seconds'); $querycount=0; sleep($time); $starttime = microtime(true);}	//maximum of 30 requests per 10 seconds

			$pages = $datas->total_pages;
			if ($datas !== NULL) {
				$errors=0;
				$datas=$datas->results;
				foreach($datas as $id2=>$value2) {
					if ($value2->adult != 'true') {
						print_r($value2); echo('<br/>');
						$db->query("INSERT INTO list (`listname`,`value`,`date_added`) VALUES ('".$value."','".$value2->id."',Now()) 
						ON DUPLICATE KEY UPDATE value='{$value2->id}',date_added=Now()");
						if (!$db->error) {$delete=true;$error=0;}
						else {elog($db->error);$error++;}
						if ($error >= 5) {$break=true; break;}
					}
				}
			}
			else {$errors++;}
			
			if ($errors >= 5) {elog("Operation aborted due to server being unresponsive"); return 0;}
			if ($delete) {$db->query("DELETE FROM list WHERE listname='".$value."' AND DATE(date_added) <> DATE(NOW())");}
			if ($break) {break;}
		}
	}
}


function movieUpdates() {	//check for movie db changes
	global $db,$starttime;
	$pages=1;
	$querycount=1;
	$last = $db->query("SELECT * FROM settings WHERE name='lastMovieUpdate' LIMIT 1");	//fetch day of last successful update
	$last = mysqli_fetch_array($last);
	$last = $last['value'];
	if ($last == date('Y-m-d') || $last === null) {$last = 0;}
	
	$errors=0;
	for($i=1;$i<=$pages;$i++) {
		$datas = queryMovieAPI(0,'changes',$i,$last);
		if ($datas !== NULL) {
			$endtime = microtime(true);
			$changeIDs = $datas->results;
			$pages = $datas->total_pages;
			foreach($changeIDs as $id=>$value) {
				elog('updating: '.$value->id);
				if ($value->adult != 'true') {
					$endtime = microtime(true);
					$time=$endtime-$starttime;
					if ($time >= 10) {$starttime = microtime(true); $querycount=0;}
					elseif ($querycount >= 30 && $time < 10 && $time > 0) {$time=11-$time; elog('sleeping for '.$time.' seconds'); $querycount=0; sleep($time); $starttime = microtime(true);}	//maximum of 30 requests per 10 seconds
					$movie = queryMovieAPI($value->id,'movie');
					$querycount+=1;
					if ($movie !== null) {$errors=0; addMovie($movie,$value->id);}
					else {$errors++;}
					//if ($errors>=5) {elog("Operation aborted due to server being unresponsive"); return 0;}
				}
			}
			$db->query("UPDATE `settings` SET value='".date('Y-m-d')."' WHERE name='lastMovieUpdate'");
		}
		else {elog("Operation aborted due to server being unresponsive"); return 0;}
	}
}

function getDayDeficit($last) {
	$last = strval((strtotime(date('Y-m-d H:i:s'))-strtotime($last))/60/60/24);
	$last = substr($last,0,strpos($last,'.'));
	$last = date('Y-m-d', strtotime('-'.$last.' days', strtotime(date('Y-m-d'))));
	return $last;
}

function addMovie($movie,$id) {
	global $db;
	if ($movie->adult != 'true') {	//family friendly y'all
		$year = intval(substr($movie->release_date,0,4));
		$trailer = $movie->trailers->youtube;
		if (!empty($trailer[0]->source)) {$trailer = $trailer[0]->source;}
		else {$trailer = "";}

		//insert basic movie data
		$db->query("INSERT INTO `movie` (`id`,`title`,`vote_average`,`vote_count`,`overview`,`runtime`,`release_date`,`year`,`poster_image`,`backdrop_image`,`imdb`,`budget`,`revenue`,`trailer`,`popularity`,`lastupdate`) VALUES
		('".$id."','".mysqli_real_escape_string($db,$movie->title)."','".$movie->vote_average."','".$movie->vote_count."','".mysqli_real_escape_string($db,$movie->overview)."','".$movie->runtime."','".$movie->release_date."','".$year."','".
		$movie->poster_path."','".$movie->backdrop_path."','".$movie->imdb_id."','".$movie->budget."','".$movie->revenue."','".$trailer[0]->source."','".$movie->popularity."',Now()) ".
			"ON DUPLICATE KEY UPDATE title='".mysqli_real_escape_string($db,$movie->title)."',vote_average='".$movie->vote_average."',vote_count='".$movie->vote_count."',overview='".mysqli_real_escape_string($db,$movie->overview)."',runtime='".
			$movie->runtime."',release_date='".$movie->release_date."',year='".$year."',poster_image='".$movie->poster_path."',backdrop_image='".$movie->backdrop_path."',imdb='".$movie->imdb_id."',budget='".$movie->budget."',revenue='".
			$movie->revenue."',trailer='".$trailer."',popularity='".$movie->popularity."',lastupdate=Now()");
		if ($db->error) {elog($id.' movie insert:'.$db->error."");}
		
		//insert genre data
		foreach($movie->genres as $in=>$genre) {
			$db->query("INSERT IGNORE INTO `genre` (`id`,`genre`) VALUES ('".$genre->id."','".mysqli_real_escape_string($db,$genre->name)."')");
			if ($db->error) {elog($id.' genre insert:'.$db->error."");}
			$db->query("INSERT IGNORE INTO `movie_genre` (`movieid`,`genreid`) VALUES ('".$id."','".$genre->id."')");
			if ($db->error) {elog($id.' movie_genre insert:'.$db->error."");}
		}
		
		//insert cast data
		foreach($movie->casts->cast as $in=>$person) {
			$db->query("INSERT INTO `actor` (`id`,`name`,`picture`) VALUES ('".$person->id."','".mysqli_real_escape_string($db,$person->name)."','".$person->profile_path."') ".
				"ON DUPLICATE KEY UPDATE name='".mysqli_real_escape_string($db,$person->name)."',picture='".$person->profile_path."'");
			if ($db->error) {elog($id.' actor insert:'.$db->error."");}
			$db->query("INSERT IGNORE INTO `movie_actor` (`movieid`,`actorid`,`character`) VALUES ('".$id."','".$person->id."','".mysqli_real_escape_string($db,$person->character)."')");
			if ($db->error) {elog($id.' movie_actor insert:'.$db->error."");}
		}
		
		//insert keyword data
		foreach($movie->keywords->keywords as $in=>$keyword) {
			$db->query("INSERT IGNORE INTO `keyword` (`id`,`keyword`) VALUES ('".$keyword->id."','".mysqli_real_escape_string($db,$keyword->name)."')");
			if ($db->error) {elog($id.' keyword insert:'.$db->error."");}
			$db->query("INSERT IGNORE INTO `movie_keyword` (`movieid`,`keywordid`) VALUES ('".$id."','".$keyword->id."')");
			if ($db->error) {elog($id.' movie_keyword insert:'.$db->error."");}
		}
		
		//insert similar movie data
		foreach($movie->similar_movies->results as $in=>$smovie) {
			if ($smovie->adult != 'true') {
				$db->query("INSERT IGNORE INTO `moviesimilar` (`movieid`,`movieid_similar`) VALUES ('".$id."','".$smovie->id."')");
				if ($db->error) {elog($id.' similar movie insert:'.$db->error."");}
			}
		}
		
		//insert production company data
		foreach($movie->production_companies as $in=>$company) {
			$db->query("INSERT IGNORE INTO `company` (`id`,`company`) VALUES ('".$company->id."','".mysqli_real_escape_string($db,$company->name)."')");
			if ($db->error) {elog($id.' company insert:'.$db->error."");}
			$db->query("INSERT IGNORE INTO `movie_company` (`movieid`,`companyid`) VALUES ('".$id."','".$company->id."')");
			if ($db->error) {elog($id.' movie_company insert:'.$db->error."");}
		}
		
		//insert language data
		foreach($movie->spoken_languages as $in=>$lan) {
			$db->query("INSERT IGNORE INTO `language` (`language`) VALUES ('".mysqli_real_escape_string($db,$lan->name)."')");
			if ($db->error) {elog($id.' language insert:'.$db->error."");}
			$db->query("INSERT IGNORE INTO `movie_language` (`movieid`,`languageid`) VALUES ('".$id."','".mysqli_real_escape_string($db,$lan->name)."')");
			if ($db->error) {elog($id.' movie_language insert:'.$db->error."");}
		}
	}
}

function getMagnetLink($link) {
	if (strlen($link) < 40) {return false;}
	$explode = explode(':',$link);
	foreach($explode as $ind=>$val) {
		if(strlen($val) >= 40) {return substr($val,0,40);}
	}
	return false;
}

function elog($string) {
	//echo ($string."<br/>");
	error_log(date("Y-m-d H:i:s").": ".$string."\r\n", 3, dirname(dirname(__FILE__)).'/logs/DailyUpdate.log');
}


?>