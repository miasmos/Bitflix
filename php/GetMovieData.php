<?php
include 'includes/dbConnect.php';
include 'includes/queryMovieAPI.php';
include 'includes/curl.php';

$db = dbConnect();
$querycount=0;
$starttime = microtime(true);

$last = $db->query("SELECT * FROM settings WHERE name='lastMovie' LIMIT 1");	//fetch last movie searched
$last = mysqli_fetch_array($last);
$last = intval($last['value']);
if ($last === null) {$last = 0;}

$ids = $db->query("SELECT id FROM movie");
$errors=0;
while ($row = mysqli_fetch_array($ids)) {
	if ($row['id'] >= $last) {
		$i = $row['id'];
		//echo($time.'<br/>');
		$movie = queryMovieAPI(strval($i),'movie');
		
		if ($movie !== null) {
			$querycount+=1;
			elog("updating:".$i);
			$errors=0; 
			addMovie($movie,$i);
			
			$endtime = microtime(true);
			$time=$endtime-$starttime;
			if ($time >= 10) {$starttime = microtime(true); $querycount=0;}
			elseif ($querycount >= 30 && $time < 10 && $time > 0) {$time=11-$time; elog('sleeping for '.$time.' seconds'); $querycount=0; sleep($time); $starttime = microtime(true);
			$db->query("INSERT INTO `settings` (`name`,`value`) VALUES ('lastMovie','".$i."') ON DUPLICATE KEY UPDATE value='".$i."'");}	//maximum of 30 requests per 10 seconds
		}
		else {$errors++;}
		if ($errors > 4) {elog("Operation aborted due to server being unresponsive"); exit;}
	}
}
$db->query("DELETE FROM `settings` WHERE name='lastMovie'");
$db->close();
$timediff = $endtime - $starttime;
elog("execution time: ".$timediff."s");

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

/*function addMovieRT($movie) {	//rotten tomatoes api
	global $db;
	createMovieTable($db,$movie->id);
	$poster=substr($movie->posters->thumbnail,strrpos($movie->posters->thumbnail,"/")+1);
	$poster=substr($poster,0,-8);
	if ($poster == "poster_d") {$poster = "-1";}
	
	if ($movie->ratings->audience_score == "100" && $movie->ratings->critics_score == "-1") {$audience_score = "-1"; $critics_score = "-1";}
	else {$audience_score = $movie->ratings->audience_score; $critics_score = $movie->ratings->critics_score;}
	if ($movie->ratings->audience_score == "0" || $movie->ratings->audience_score == "") {$audience_score = "-1";}
	if ($movie->ratings->critics_score == "") {$critics_score = "-1";}
	
	$db->query("INSERT INTO `movies` (`id`,`title`,`critic_score`,`audience_score`,`synopsis`,`critic_consensus`,`year`,`mpaa_rating`,`runtime`,`theatre_release`,`dvd_release`,`poster`,`imdb`,`lastupdate`) VALUES
	('".$movie->id."','".$movie->title."','".$critics_score."','".$audience_score."','".$movie->synopsis."','".$movie->critics_consensus."','".$movie->year."','".$movie->mpaa_rating."','".
	$movie->runtime."','".$movie->release_dates->theater."','".$movie->release_dates->dvd."','".$poster."','".$movie->alternate_ids->imdb."',Now())");
	
	foreach($movie->abridged_cast as $index=>$actor) {	//"abridged_cast":{"name","id","characters"{}}
		createActorTable($db,$actor->id);
		$db->query("INSERT INTO `".$actor->id."_actor` (`id`) VALUES ('".$movie->id."')");
		$db->query("INSERT INTO `actors` (`id`,`name`) VALUES ('".$actor->id."','".$actor->name."')");
		$db->query("INSERT INTO `".$movie->id."_movie` (`id`,`actor`) VALUES ('".$actor->id."','".$actor->name."')");
	}
	getTrailer($movie->id,$movie->title."+".$movie->year);
}*/

function elog($string) {
	//echo ($string."<br/>");
	error_log(date("Y-m-d H:i:s").": ".$string."\r\n", 3, dirname(dirname(__FILE__)).'/logs/GetMovieData.log');
}
?>

