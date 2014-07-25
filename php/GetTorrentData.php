<?php
// TO DO
//
//	program TPB new submission scraping
//  transfer to server environment
//
//
//
include 'includes/aria2.class.php';
include 'includes/dbConnect.php';
include 'includes/curl.php';

$elog=1;
$starttime = microtime(true);

$db = dbConnect();
UpdateTorrents();
$db->close();
$endtime = microtime(true);
$timediff = $endtime - $starttime;
echo("execution time: ".$timediff."s");

function UpdateTorrents() {
	global $db;
	
	if (CheckTimeElapsed("lastNewTorrentUpdate",12)) {
		//movies that were just added to db update every 12 hours
		$result = $db->query("SELECT id,title,year FROM `movie` t1 WHERE NOT EXISTS (SELECT 1 FROM `torrent` t2 WHERE t1.id=t2.movieid) AND year > 0 AND date(t1.release_date) <= date(now())");	//check for new entries and do them first
		if (mysqli_num_rows($result) > 0) {
			elog("updating recently added movies");
			$ret=GetTorrentData($result,"1 DAY",0);
			if ($ret) {$db->query("UPDATE `settings` SET value=now() WHERE name='lastNewTorrentUpdate'");}
		}	//gettorrentdata(results,interval), time to wait before deleting old torrent list
	}
	
	if (CheckTimeElapsed("lastTorrentListUpdate",2)) {
		//now_playing and new dvd releases (within the week) update every 2 hours
		$result = $db->query("SELECT movie.id,movie.title,movie.year FROM `movie` INNER JOIN `torrent` on movie.id = torrent.movieid WHERE movie.year > 0 AND date(movie.release_date) <= date(now()) AND movie.popularity > 1 AND (EXISTS (SELECT 1 FROM `list` WHERE list.listname='now_playing' AND list.value=movie.id) OR date(movie.release_date) >= NOW() - INTERVAL 7 DAY) AND (date(torrent.lastupdate) < NOW() - INTERVAL 2 HOUR)");
		sleep(9);
		if (mysqli_num_rows($result) > 0) {
			elog("updating listed movies");
			$ret=GetTorrentData($result,"2 HOUR");
			if ($ret) {$db->query("UPDATE `settings` SET value=now() WHERE name='lastTorrentListUpdate'");}
		}
		exit;
	}
	
	if (CheckTimeElapsed("lastTorrentPopularUpdate",24)) {
		//popularity > 1 update every day
		$result = $db->query("SELECT movie.id,movie.title,movie.year FROM `movie` INNER JOIN `torrent` on movie.id = torrent.movieid WHERE movie.year > 0 AND date(movie.release_date) <= date(now()) AND movie.popularity > 1 AND (date(torrent.lastupdate) < NOW() - INTERVAL 1 DAY)");
		if (mysqli_num_rows($result) > 0) {
			elog("updating popular movies");
			$ret=GetTorrentData($result,"1 DAY");
			if ($ret) {$db->query("UPDATE `settings` SET value=now() WHERE name='lastTorrentPopularUpdate'");}
		}
		exit;
	}
	
	if (CheckTimeElapsed("lastTorrentUnpopularUpdate",168)) {
		//popularity <= 1 update every week
		$result = $db->query("SELECT movie.id,movie.title,movie.year FROM `movie` INNER JOIN `torrent` on movie.id = torrent.movieid WHERE movie.year > 0 AND date(movie.release_date) <= date(now()) AND movie.popularity <= 1 AND (date(torrent.lastupdate) < NOW() - INTERVAL 7 DAY)");
		if (mysqli_num_rows($result) > 0) {
			elog("updating unpopular movies");
			$ret=GetTorrentData($result,"7 DAY");
			if ($ret) {$db->query("UPDATE `settings` SET value=now() WHERE name='lastTorrentUnpopularUpdate'");}
		}
		exit;
	}
}

function GetTorrentData($result,$interval,$delete=1) {
	global $db;
	$displayList = array();
	$mname=array();
	
	$lastid=-1;
	while ($row = mysqli_fetch_object($result)) {
		if ($lastid != $row->id) {$movies[] = $row;}
		$lastid=$row->id;
	}
	if ($lastid == -1) {return 0;}
	
	foreach ($movies as $movie) {	//normalize list of movie titles
		array_push($mname, normalize($movie->title,false));
	}

	$errors=0;
	foreach ($movies as $index=>$movie) {	//fetch torrents, choose best one for each movie, and append to display list
		if ($mname[$index] != "skipthisone") {
			elog("looking for ".$mname[$index]."<br/>");
			$displayList[$index] = findBestTorrent($movie, $mname, $index, 0);
			
			if ($displayList[$index] == -1) {	//if connection failed
				elog("Connection failed when searching for {$mname[$index]}");
				$errors++;
				if ($errors > 4) {elog("Operation aborted due to server being unresponsive"); return 0;}
			}
			else {
				$errors=0;
				if (empty($displayList[$index]['1080']) && empty($displayList[$index]['720']) && empty($displayList[$index]['dvdrip']) && empty($displayList[$index]['cam'])) {	//no torrents found
					$db->query("DELETE FROM `torrent` WHERE movieid = ".$movie->id);
					$db->query("INSERT INTO `torrent` (movieid,lastupdate,magnet,confirmed) VALUES ('{$movie->id}',Now(),'0','2')");
				}
				else {	//torrents found
					$rank=1;
					foreach ($displayList[$index] as $index1=>$quality) {
						foreach($quality as $index2=>$torrents) {
							if ($torrents["Score"] > 0) {
								//$db->query("DELETE FROM `torrent` WHERE movieid = '".$movie->id."' AND date(lastupdate) < DATE_SUB(NOW(), INTERVAL 8 DAY)");
								$db->query("INSERT INTO `torrent` (movieid,title,link,magnet,magnetend,uploaddate,size,seeds,leeches,score,rank,ratio,quality,lastupdate) VALUES
									('".$torrents["ID"]."','".mysql_real_escape_string($torrents["Title"])."','".mysql_real_escape_string($torrents["Link"])."','".$torrents["MagnetLink"]."','".$torrents["MagnetLinkEnd"]."','".$torrents["DateUploaded"]."','".$torrents["Size"]."','".$torrents["Seeds"]."','".$torrents["Leeches"]."','".$torrents["Score"]."','".$rank."','".$torrents["SeedRatio"]."','".$index1."',Now())
										ON DUPLICATE KEY UPDATE title='".mysql_real_escape_string($torrents["Title"])."',seeds='".$torrents["Seeds"]."',leeches='".$torrents["Leeches"]."',ratio='".$torrents["SeedRatio"]."',score='".$torrents["Score"]."',rank='".$rank."',lastupdate=Now()");
								fetchTorrent($torrents["MagnetLink"],$torrents["MagnetLinkEnd"]);		//get the torrent file to detect contents
								$rank++;
							}
							else {
								$db->query("DELETE FROM `torrent` WHERE movieid = '".$movie->id."'");
								$db->query("INSERT INTO `torrent` (movieid,lastupdate,magnet,confirmed) VALUES ('{$movie->id}',Now(),'0','2')");
								break;
							}
						}
						
						
					}
					
					
				}
			}
		}
	}
	return 1;
}

function findBestTorrent($movie, $mname, $dex, $_quality) {
	elog($_quality);
	$minSeeds=1;	//minimum seeds required to be included
	$minSeedRatio=0.7;
	$seedThreshold=50;	//threshold at which to apply seed ratio filtering
	$minSize=550;	//minimum size movie torrents can be
	$maxSDSize=1000;	//maximum size in MB of the dvdrip quality, also preferred that HD size is above this
	$minCamSize=400;	//minimum size cams can be
	$hiSeed=1000;	//trivial number of seeds
	
	if ($_quality == 0) {$_quality = "";}
	if (strlen($_quality) == 0) {$year = urlencode($movie->year);}
	else {$year = "";}
	
	$torResultSD = newTPBSearch(urlencode($mname[$dex]), $year, "sd", 15);  //returns array of results based on $movies->movie->title
	$torResultHD = newTPBSearch(urlencode($mname[$dex]), $year, "hd", 15);  //returns array of results based on $movies->movie->title
	if ($torResultSD == -1 || $torResultHD == -1) {return -1;}
	
	if (strlen($_quality) == 0) {$qualities = array("1080", "720", "dvdrip", "cam");}	//if passed $_quality of 0, compute all qualities
	else {$qualities = array($_quality);}	//if passed nonzero $_quality, compute only that quality
	$keywords = array("1080"=> array ("bdrip", "brrip", "blu-ray", "bluray", "bdr", "bdscr"), "720"=> array ("bdrip", "brrip", "blu-ray", "bluray", "bdr", "bdscr"), "dvdrip"=> array ("480", "dvd-rip", "dvdrip", "dvdr", "dvd", "screener", "scr", "dvdscr", "dvdscreener", "r5", "telecine", "tc", "ddc"), "Cam"=> array ("telesync", "camrip", "cam", "pdvd", "predvd", "pre-dvd"));
	$blackList = array("3d", "trilogy", "duology", "quadrilogy", "quintrilogy", "spanish", "french", "brazilian", "russian", "portugese", "dutch", "german", "swedish", "saga", "anthology", "swesub");
	
	$return = array();
	$movieTitle=normalize($movie->title);
	
	foreach ($qualities as $ind=>$quality) {
		if (strlen($_quality) == 0) {elog("<div><br/><br/><br/>looking for ".$quality." using year search<br/>");}
		else {elog("<br/>looking for ".$quality." using non-year search<br/>");}
		
		if ($quality == "1080" || $quality == "720") {$torResult = $torResultHD;}
		else {$torResult = $torResultSD;}
		
		if ($torResult) {
			$hiScore=0;	//highest score among all accepted torrents
			$hiSeedCount=0;	//number of torrents having more than $hiSeed, used to derive more granular results given absolute results
			$tname=array();
			
			foreach ($torResult as $index=>&$curTor) {	//normalize the titles and search for blacklisted words
				$titleCheck = strtolower(str_replace($movieTitle, "", $tname[$index]));
				$tname[$index]=normalize($curTor["Title"]);
				if (containsOneOf($blackList, $titleCheck)) {
					elog($titleCheck." contains blacklisted word, skipping<br/>");
					unset($torResult[$index]);
				}
				elseif (trim($curTor["Title"]) == "") {unset($curTor);}
			}
			
			//print_r($torResult); elog("<br/>");
			foreach ($torResult as $index=>&$curTor) {
				if (badTorrent($curTor["MagnetLink"])) {$curTor["Score"]=0; continue;}
				if (strlen($tname[$index]) == 0) {$curTor["Score"]=0; continue;}
				if (!checkTitle($tname[$index], $movieTitle)) {$curTor["Score"]=0; continue;}
				if (!checkSize($curTor["Size"], $minSize)) {elog($tname[$index]." failed size check (".$curTor["Size"]."/".$minSize."MB</br>"); $curTor["Score"]=0; continue;}
				if ($curTor["Seeds"] < $minSeeds) {elog($tname[$index]." seeds less than minimum (".$curTor["Seeds"]."/".$minSeeds.")</br>"); $curTor["Score"]=0; continue;}
				$curTor["Score"]+=1;
				$titleCheck = strtolower(str_replace($movieTitle, "", $tname[$index]));
				
				$qualityCheck=false;
				if (stripos($titleCheck, $quality)) {$qualityCheck=true; $curTor["Score"]+=20;}
				elseif (!stripos($titleCheck, $quality) && $hiScore >= 20 && $quality != "cam") {elog($tname[$index]." quality is ensured elsewhere and not here, skipping<br/>"); $curTor["Score"]=0; continue;}	//if quality is ensured in other torrents, then skip those we're unsure of
				elseif (!stripos($titleCheck, $quality) && $hiScore < 20 && ($quality == "1080" || $quality == "720")) {elog($tname[$index]." is hd and does not contain correct quality, skipping<br/>"); $curTor["Score"]=0; continue;}
				
				$con=0;
				foreach ($qualities as $t=>$qua) {
					if ($qua != $quality) {	//if year isn't included in search, discard torrent if it contains keywords from other qualities. Also discard torrent if it contains keywords that are absolutely of another quality
						if ((containsOneOf($keywords[$qua], $titleCheck) && strlen($_quality) > 0) || stripos($titleCheck,$qua)) {	
							$curTor["Score"]=0; $con=1;
							elog($titleCheck." contains blacklisted quality word, skipping<br/>"); 
						}
					}
				}
				if ($con) {$curTor["Score"]=0; continue;}
				
				$match = explode(' ', $titleCheck);
				if ((trim($match[0]) > 1 && trim($match[0]) < 6) || isNumeral(trim($match[0]))) {elog($tname[$index]." is a sequel, skipping<br/>"); $curTor["Score"]=0; continue;}	//check if it's a sequel
				if ($curTor["Seeds"] <= $seedThreshold && $curTor["SeedRatio"] > $minSeedRatio) {$curTor["Score"]-=4;} //if there's a low amount of seeds and the ratio is high, penalize score
				if ($quality == "dvdrip" && getSizeMB($curTor["Size"] > $maxSDSize)) {elog($tname[$index]." is dvdrip quality and is more than ".$maxSDSize."MB."); $curTor["Score"] = 0; continue;}	//if quality is 480, restrict size to < maxSDSize
				
				$con=0;
				foreach ($mname as $key) {	//if a longer movie title is contained within the current torrent, skip it
					if (containsAllOfR($tname[$index], $key) && $key != $movieTitle && strlen($tname[$index]) < strlen($key) && substr($tname[$index],0,4) != "the ") {
						$con=1;
						elog($tname[$index]." contains ".$key.", skipping<br/>"); 
						break;
					}
				}
				if ($con) {$curTor["Score"]=0; continue;}
				preg_match_all('@(19[8,9][0-9]|2[0,1][0-9][0-9])@', $tname[$index], $match);	//check for 1800 < year < 2100
				if (count($match[0]) == 0 && strlen($_quality) > 0) {elog($tname[$index]." doesn't contain a year, skipping<br/>"); $curTor["Score"]=0; continue;}	//if year included in search and year wasn't found ignore torrent
				elseif (count($match[0]) > 0 && strlen($_quality) == 0) {$curTor["Score"]+=2;}	//if year wasn't included in search and year was found, add to score
				
				if ($quality == "1080" || $quality == "720") {
					if (getSizeMB($curTor["Size"] > $maxSDSize)) {$curTor["Score"] += 2;}
					if ($qualityCheck == false) {
						if (stripos($titleCheck, "brrip") || stripos($titleCheck, "bdrip") || stripos($titleCheck, "blu-ray") || stripos($titleCheck, "bluray") || stripos($titleCheck, "bdr")) {
							$qualityCheck=true;
							$curTor["Score"]+=4;
						}
						elseif (stripos($titleCheck, "bdscr")) {
							$qualityCheck=true;
							$curTor["Score"]+=2;
						}
					}
				}
				elseif ($quality == "dvdrip") {
					$sizeCheck=getSizeMB($curTor["Size"]) < $maxSDSize;
					if (strlen($_quality) == 0 && $sizeCheck) {$curTor["Score"] += 2;}
					elseif (strlen($_quality) > 0 && $sizeCheck) {$curTor["Score"] = 0; continue;}	
					
					if ($qualityCheck == false) {
						if (stripos($titleCheck, "dvd-rip") || stripos($titleCheck, "dvdrip") || stripos($titleCheck, "dvdr") || stripos($titleCheck, "dvd") || (stripos($titleCheck, "webrip") && $sizeCheck)) {
							$qualityCheck=true;
							$curTor["Score"]+=4;
						}
						elseif (stripos($titleCheck, "screener") || stripos($titleCheck, "scr") || stripos($titleCheck, "dvdscr") || stripos($titleCheck, "dvdscreener")) {
							$qualityCheck=true;
							$curTor["Score"]+=3;
						}
						elseif (stripos($titleCheck, "r5")) {
							$qualityCheck=true;
							$curTor["Score"]+=2;
						}
						elseif (stripos($titleCheck, "telecine") || stripos($titleCheck, "tc") || stripos($titleCheck, "ddc")) {
							$qualityCheck=true;
							$curTor["Score"]+=1;
						}
					}
				}
				else {
					if ($qualityCheck == false) {
						if (stripos($titleCheck, "ts") || stripos($titleCheck, "telesync")) {
							$qualityCheck=true;
							$curTor["Score"]+=4;
						}
						elseif (stripos($titleCheck, "camrip") || stripos($titleCheck, "cam")) {
							$qualityCheck=true;
							$curTor["Score"]+=3;
						}
						elseif (stripos($titleCheck, "pdvd") || stripos($titleCheck, "pre-dvd")) {
							$qualityCheck=true;
							$curTor["Score"]+=2;
						}
					}
				}
				
				if ($qualityCheck == false) {elog($tname[$index]." failed quality check, skipping<br/>"); $curTor["Score"]=0; continue;}
				
				if ($curTor["Seeds"]>$hiSeed) {$hiSeedCount+=1;}	//track number of torrents having a trivial number of seeds
				if ($curTor["SeedRatio"] > 0) {$curTor["Score"] += .01/$curTor["SeedRatio"];}
				if ($curTor["Seeds"] > 0) {$curTor["Score"] += $curTor["Seeds"]/100000;}
				//$curTor["Quality"]=$quality;
				$curTor["Score"]=round($curTor["Score"],3);
				$curTor["Size"]=getSizeGB($curTor["Size"]);
				elog($tname[$index]." passed filters with a score of ".$curTor["Score"]."<br/>");
				if ($curTor["Score"] > $hiScore) {$hiScore = $curTor["Score"];}
			}
			
			elog(strlen($_quality) == 0);
			/*if (($hiScore <= 1 || count($torResult) == 0) && strlen($_quality) == 0) {	//outer search failed, try without year stipulated
				$noYearSearch = findBestTorrent($movie, $mname, $dex, $quality);
				
				if ($noYearSearch != null) {
					$return[$quality]=assignMatches($noYearSearch[0], 1, array());
				}
				else {
					elog("failed to find a match<br/>");
					$return[$quality]=assignMatches($movie, 0, array());
				}
				continue;
			}
			elseif (($hiScore <= 1 || count($torResult) == 0) && strlen($_quality) > 0) {return null;}	//inner search failed, no matches possible*/
			elog("hiscore: ".$hiScore."<br/>");
			if ($hiScore <= 1 || count($torResult) == 0) {$return[$quality]=assignMatches($movie, 0, array()); continue;}
			
			$tempKeys = array();
			if ($hiScore > 20) {	//if matched quality in title of any torrent, we can be sure of authenticity. Ignore scoring and sort by seeds.
				elog("sorting by seeds<br/>");
				foreach($torResult as $index=>$curTor) {
					if ($curTor["Score"] < 21) {unset($torResult[$index]);}	//Exclude torrents of questionable quality
					else {$tempKeys[$index] = $curTor["Seeds"];}
				}
			}
			else {
				elog("sorting by score<br/>");
				foreach($torResult as $index=>$curTor) {	//sort descending by score
					$tempKeys[$index] = $curTor["Score"];
				}
			}
			array_multisort($tempKeys, SORT_DESC, $torResult);
			
			if ($hiSeedCount > 1 && $hiScore > 20 && ($quality == "1080" || $quality == "720")) {		//if a trivial number of seeds exists for more than one torrent, prefer higher filesizes for HD torrents
				$tempKeys = array(); $tempSize = array(); $tempResult = array(); $tempSeeds = array();
				
				foreach($torResult as $index=>$curTor) {
					if ($curTor["Seeds"] >= $hiSeed) {
						array_push($tempSize, $curTor);
						array_push($tempKeys, $curTor["Size"]);	//put all torrents with trivial seeds into tempSize array
						array_push($tempSeeds, $curTor["Seeds"]);
					}
					else {
						array_push($tempResult, $curTor);	//put the rest into a another tempResult array
					}
				}
				
				elog("calculated stddev to be ".standard_deviation($tempSeeds)."<br/>");
				if (standard_deviation($tempSeeds) < $hiSeed*0.8) {	//if there's a large deficit in seeds among torrents with trivial seed counts, prefer more seeds to larger file
					elog("sorting by size<br/>");
					array_multisort($tempKeys, SORT_DESC, $tempSize);	//sort the tempSize array by size

					foreach($tempResult as $index=>$curTor) {	//push the tempResult array onto the tempSize array
						array_push($tempSize, $curTor);
					}
					$torResult = $tempSize;	//make it so
				}
			}	
			//print_r($torResult);
			
			if (strlen($_quality == 0)) {	//if outer search, set match found
				if ($torResult[0]["Score"]>0 && $torResult[0]["Seeds"]>$minSeeds) {
					$return[$quality]=assignMatches($movie, 1, $torResult);	//make it so
				}
				else {$return[$quality]=assignMatches($movie, 0, array());}
			}
			else {	//if inner search, send it upwards
				return $torResult;
			}
		}
		else {
			/*if (($hiScore <= 1 || count($torResult) == 0) && strlen($_quality) == 0) {	//outer search failed, try without year stipulated
				$noYearSearch = findBestTorrent($movie, $mname, $dex, $quality);
				
				if ($noYearSearch != null) {
					$return[$quality]=assignMatches($noYearSearch[0], 1, array());
				}
				else {
					elog("failed to find a match<br/>");
					$return[$quality]=assignMatches($movie, 0, array());
				}
				continue;
			}
			elseif (($hiScore <= 1 || count($torResult) == 0) && strlen($_quality) > 0) {return null;}	//inner search failed, no matches possible*/
			$return[$quality]=assignMatches($movie, 0, array());
		}
		elog("</div>");
	}
	return $return;
}

function CheckTimeElapsed($query,$interval) { //$interval = number of hours to check for
	global $db;
	$result = $db->query("SELECT value FROM `settings` WHERE name='".$query."'");
	while ($row = mysqli_fetch_object($result)) {
		if (time()-strtotime($row->value) > $interval*60*60) {return true;}
		else {return false;}
	}
}

function assignMatches($movie, $found, $torrents) {
	$matches=array();
	$i=1;
	foreach($torrents as $index=>$torrent) {
		$torrent["Title"] = trim($torrent["Title"]);
		$torrent["ID"] = $movie->id;
		$torrent["Found"] = $found;
		if ($torrent["Found"] = 1) {$torrent["Rank"] = $i; $i++;}
		array_push($matches,$torrent);
	}
	//if ($matches["Found"] == 1) {elog("chose " . $matches["Title"] . "<br/><br/>");}
	//else {elog("no matches found". "<br/><br/>");}
	return $matches;
}

function newTPBSearch($q, $year, $category, $returns) {
	#7 - seeds descending
	$orderby=7;
	# all - all
	# 201 - movies
	# 207 - hd movies
	if ($category == "hd") {
		$category=207;
	}
	elseif ($category == "sd") {
		$category=201;
	}

	if (strlen($year) == 0 && strlen($q) == 0) {
		$return=curl_get('http://thepiratebay.se/browse/' . $category . '/0/' . $orderby);
		elog('http://thepiratebay.sx/browse/' . $category . '/0/' . $orderby. "<br/>");
	}
	elseif (strlen($year) == 0 && strlen($q) > 0) {
		$return=curl_get('http://thepiratebay.se/search/' . $q . '/0/' . $orderby . '/' . $category);
		elog('http://thepiratebay.sx/search/' . $q . '/0/' . $orderby . '/' . $category."<br/>");
	}
	else {
		$return=curl_get('http://thepiratebay.se/search/' . $q . '+' . $year . '/0/' . $orderby . '/' . $category);
		elog('http://thepiratebay.sx/search/' . $q . '+' . $year . '/0/' . $orderby . '/' . $category."<br/>");
	}
	
	//extract the important data from the html
	if (strpos($return, "Could not connect to caching server") !== false || empty($return)) {
		return -1;
	}
	if (strpos($return, "No hits. Try adding") !== false) {	//if no match was found
		return 0;
	}
	else {
		$return= substr($return, strpos($return, '<div class="detName">'));
		$parts = explode('<div align="center">',$return);
		$return = $parts[0];
		$parts = explode('<div class="detName">',$return);
		if (count($parts) == 0) {return 0;}
		
		for ($i=1;$i<=$returns;$i++) {
			$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
			if (empty($parts[$i])) {continue;}
			preg_match_all("/$regexp/siU", $parts[$i], $matches);
			$torrents[$i]["Link"] = $matches[2][0];
			$torrents[$i]["MagnetLink"] = getMagnetLink($matches[2][1]);
			$torrents[$i]["MagnetLinkEnd"] = substr($matches[2][1],strpos($matches[2][1],$torrents[$i]["MagnetLink"])+strlen($torrents[$i]["MagnetLink"]));
			
			#echo $torrents[$i]["Link"]. '<br/>';
			#echo $torrents[$i]["MagnetLink"]. '<br/>';
						 
			$parts[$i]=strip_tags($parts[$i]);
			$parts[$i]=substr($parts[$i], 3);
			
			$torrents[$i]["Title"] = trim(strstr($parts[$i], 'Uploaded ', 1));
			#echo $torrents[$i]["Title"] . "<br/>";
			$parts[$i]=str_replace($torrents[$i]["Title"], "", $parts[$i]);
			$parts[$i]=str_replace('Uploaded ', "", $parts[$i]);
			$parts[$i]=substr($parts[$i], 6);
			$torrents[$i]["Title"] = preg_replace("/[^a-zA-Z0-9]/", " ", strtolower($torrents[$i]["Title"]));
			$torrents[$i]["Title"] = substr_replace($torrents[$i]["Title"], " ", 0, 0);
			$torrents[$i]["Title"] = substr_replace($torrents[$i]["Title"], " ", -0, 0);
			#echo $torrents[$i]["Title"]. '<br/>';
			#echo $parts[$i] . "<br/>";
			$torrents[$i]["DateUploaded"] = trim(strstr($parts[$i], ', Size ', 1));
			#$parts[$i]=substr($parts[$i], 17);
			$parts[$i]=str_replace($torrents[$i]["DateUploaded"], "", $parts[$i]);
			$parts[$i]=str_replace(', Size ', "", $parts[$i]);
			#echo $parts[$i] . "<br/>";
			
			if (stristr($torrents[$i]["DateUploaded"], "day") > -1) {
				$torrents[$i]["DateUploaded"] = date("Y-m-d");
			}
			else if (stristr($torrents[$i]["DateUploaded"], ":") > -1) {
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], '', -10, 6);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], date("Y"), -5);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], ' ', -4, 0);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], ' ', 2, 1);
				$torrents[$i]["DateUploaded"] = date("Y-m-d", mktime(0, 0, 0, floatval(substr($torrents[$i]['DateUploaded'],0,2)), floatval(substr($torrents[$i]['DateUploaded'],3,2)), floatval(substr($torrents[$i]['DateUploaded'],-4))));
			}
			else {
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], '', -10, 6);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], ' ', -4, 0);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], ' ', 2, 1);
				$torrents[$i]["DateUploaded"] = date("Y-m-d", mktime(0, 0, 0, floatval(substr($torrents[$i]['DateUploaded'],0,2)), floatval(substr($torrents[$i]['DateUploaded'],3,2)), floatval(substr($torrents[$i]['DateUploaded'],-4))));
			}
			
			if (strpos($torrents[$i]["DateUploaded"], "& ") !== false) {
				$torrents[$i]["DateUploaded"] = strstr($torrents[$i]["DateUploaded"], "& ", true) . " " . date("Y");
			}
			#echo $torrents[$i]["DateUploaded"]. '<br/>';
			#echo $parts[$i] . "<br/>";
			
			$torrents[$i]["Size"] = trim(strstr($parts[$i], ', ULed', 1));
			if (substr($torrents[$i]["Size"], 0, 1) == ",") {
				$torrents[$i]["Size"] = substr_replace($torrents[$i]["Size"], '', 0, 2);
			}
				
			$torrents[$i]["Size"] = str_replace('iB', '', $torrents[$i]["Size"]);
			$torrents[$i]["Size"] = substr_replace($torrents[$i]["Size"], '', -7, 6);
			$parts[$i]=substr($parts[$i], 24);
			#echo "'" . $torrents[$i]["Size"] . "'" . '<br/>';
			
			preg_match_all('/([\d]+)/', $parts[$i], $match);
			$torrents[$i]["Seeds"] = $match[0][count($match[0])-2];
			$torrents[$i]["Leeches"] = $match[0][count($match[0])-1];
			if ($torrents[$i]["Seeds"]>0) {$torrents[$i]["SeedRatio"] = round($torrents[$i]["Leeches"]/$torrents[$i]["Seeds"],3);}
			#echo '"' . $torrent[$i]["Seeds"] .'"'. '<br/>';
			#echo $torrent[$i]["Leeches"]. '<br/>';
			#echo '<br/>';
			$torrents[$i]["Score"] = 0;
			
			if ($category == 207) {
				$torrents[$i]["SearchMethod"] = "HD";
			}
			else {
				$torrents[$i]["SearchMethod"] = "SD";
			}
		}
		
		return $torrents;
	}
	//end extraction of data
}

function badTorrent($magnet) {
	global $db;
	$magnet = getMagnetLink($magnet);
	$umagnet = strtoupper($magnet);
	if (strlen($magnet) < 40) {return true;}
	$result = $db->query("SELECT 1 FROM `badtorrents` WHERE id='{$magnet}' OR id='{$umagnet}'");
	if (mysqli_num_rows($result) > 0) {return true;}
	return false;
}

function fetchTorrent($magnet,$end) {
	if (strpos($magnet,'magnet:?xt=urn:btih:') === false) {
		$magnet='magnet:?xt=urn:btih:'.$magnet;
	}

	$aria2 = new aria2();
	//elog($magnet);
	$gid=$aria2->addUri(array($magnet.$end),array('dir'=>'.'));
}

function explodeMovie($q) {
	$mparts = explode(' ', $q);
	foreach($mparts as $val) {
		$val = preg_replace("[^a-zA-Z0-9_\s]", '', strtolower($mparts[$y]));
		$val = preg_replace("/(?![.=$'€%-])\p{P}/u", '', $mparts[$y]);
	}
	
	return $mparts;
}

function normalize($str,$replace_numerals=true) {
	$str = preg_replace('/[^a-z\d ]/i', '', $str);		//remove non alpha numeric characters
	$str = preg_replace("/\s+/", ' ', $str); //remove extra spaces;
	$str = strtolower(trim($str));
	$str = preg_replace('/part.([0-9])|p.([0-9])|part([0-9])|p([0-9])|pt.([0-9])|pt([0-9])/i', "part $1$2$3$4$5$6", $str);		//handle parts
	$str = str_replace(' s ', '\'s ', $str);	//put apostrophes back in
	if (substr($str, -2, 2) == " s") {$str=substr_replace($str, '\'s', -2, 2);}
	if (substr($str, -2, 2) == "3d") {$str=substr_replace($str, '', -3, 3);}	//remove '3d' from end of string
	if (substr($str, -8, 8) == "extended") {$str=substr_replace($str, '', -8, 8);}	//remove 'extended' from end of string
	if (substr($str, -7, 7) == "unrated") {$str=substr_replace($str, '', -7, 7);}	//remove 'unrated' from end of string
	if ($replace_numerals) {if (hasNumeral($str)) {$str = numeralToInt($str);}} //convert numerals to integer
	return trim($str);
}

function checkSize($target, $size) {
	$temp = substr($target, -1);
	$target = substr_replace($target, "", strlen($target)-1, 1);
	#echo $target. $temp;
	return ($temp == "M" && $target >= $size) || $temp == "G" || $temp == "T";
}

function checkTitle($needle, $stack) {
	if (containsAllOfR($needle, $stack)) {
		if (stripos($needle, $stack) > 0) {
			//elog($needle." contains words before title, skipping<br/>");
			return false;
		}
	}
	else {
		if (substr_count($stack, "the") > 1) {
			$explode = explode("the", $stack);
			foreach($explode as $index=>$i) {
				//elog($i."<br/>");
				if (strpos($needle,(trim($i))) === false && trim($i) != "") {
					//elog($needle." does not contain ".$stack."</br>");
					return false;
				}
			}
		}
	}
	
	return true;
}

function getSizeMB($size) {
	$temp = substr($size, -1);
	$size = intval(substr_replace($size, "", strlen($size)-1, 1));
	#echo $size. $temp;
	if ($temp == "G") {$size*=1000;}
	elseif ($temp=="K") {$size/=1000;}
	elseif ($temp=="T") {$size*=1000000;}
	return $size;
}

function getSizeGB($size) {
	$temp = substr($size, -1);
	$size = floatval(substr_replace($size, "", strlen($size)-1, 1));
	#echo $size. $temp;
	if ($temp == "M") {$size/=1000;}
	elseif ($temp=="K") {$size/=1000000;}
	elseif ($temp=="T") {$size*=1000;}
	return $size;
}

function getMagnetLink($link) {
	if (strlen($link) < 40) {return false;}
	$explode = explode(':',$link);
	foreach($explode as $ind=>$val) {
		if(strlen($val) >= 40) {return substr($val,0,40);}
	}
	return false;
}

function containsOneOf($list, $str) {
	if (strlen($str) == 0) {return 0;}
	if (!is_array($list)) {$list = explode(' ', $list);}
	foreach ($list as $val) {
		//elog("comparing ".$val." to ".$str);
		if (strlen($val) > 0) {
			if (strpos(strtolower($str), strtolower($val))) {
				//elog (", matched<br/>");
				return 1;
			}
		}
		//elog("<br/>");
	}
	return 0;
}

function standard_deviation($sample){
	if(is_array($sample)){
		$mean = array_sum($sample) / count($sample);
		foreach($sample as $key => $num) $devs[$key] = pow($num - $mean, 2);
		return sqrt(array_sum($devs) / (count($devs) - 1));
	}
}

function containsAllOf($str, $list) {	//strings can occur in any order
	if (strlen($list)>strlen($str)) {return 0;}
	
	$list=explode(' ', $list);
	foreach ($list as $val) {
		$val = $val . " ";
		if (strpos($str, $val) === false) {
			#echo $val . "::" . $str . " false<br/>";
			return 0;
		}
	}
	return 1;
}

function containsAllOfR($str, $list) {	//strings must occur in order that they appear in source
	if (strpos($str, $list." ") === false) {return 0;}
	return 1;
}

function isNumeral($numeral) {
	if (strlen($numeral) > 4) {return false;}
	$temp=str_split($numeral);
	$needle=array('i','v','x','l','c','d','m');
	foreach ($temp as $val) {
		if (!in_array($val, $needle)) {return false;}
	}
	return true;
}

function hasNumeral($numeral) {
	return preg_match('/ cm| cd| d| c| xc| xl| l| x| ix| iv| v| ii| iii| vi| vii| viii| xi| xii| xiii/i', $numeral) > 0;
}

function numeralToInt($in) {
	$in = explode(' ', $in);
	foreach ($in as &$val) {
		if (isNumeral($val)) {
			$romans = array(
				'm' => 1000,
				'cm' => 900,
				'd' => 500,
				'cd' => 400,
				'c' => 100,
				'xc' => 90,
				'l' => 50,
				'xl' => 40,
				'x' => 10,
				'ix' => 9,
				'v' => 5,
				'iv' => 4,
				'i' => 1,
			);
		
			$result=0;
			foreach ($romans as $key => $value) {
				while (strpos($val, $key) === 0) {
					$result += $value;
					$val = substr($val, strlen($key));
				}
			}
			$val=$result;
		}
	}
	return trim(implode(' ', $in));;
}

function elog($string) {
	//echo ($string."<br/>");
	error_log(date("Y-m-d H:i:s").": ".$string."\r\n", 3, dirname(dirname(__FILE__)).'\logs\GetTorrentData.log');
}

?>

