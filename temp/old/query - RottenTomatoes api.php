<?php
include 'includes/aria2.class.php';
include 'includes/dbConnect.php';
//include 'includes/dbInterface.php';

$debug=1;
$starttime = microtime(true);
//apiToTorrent($_GET['s'], "toprentals");
//apiToTorrent($_GET['s'], "newreleases");
$db = dbConnect();
if (isset($_GET['s'])) {
	apiToTorrent($_GET['s'], "query");
	echo("<br/><br/>");
	
	//echo "<br/>Top Rentals<br/>";
	// apiToTorrent($_GET['s'], "toprentals");
	
	//echo "<br/>New Releases<br/>";
	//apiToTorrent($_GET['s'], "newreleases");
	
	//new Search -- torrents search determine api query
	//echo "<br/>Popular On The Pirate Bay<br/>";
	//torrentToApi(0, "popular");
	
	
	/*foreach ($movies as $index=>$movie) {
		echo "<br/><br/>";
		$similar=queryMovieAPIPrivate($movie->links->similar);
		$displayList=array();
		$mname=array();
		
		foreach ($movies as $movie) {	//normalize list of movie titles
			array_push($mname, normalize($movie->title));
		}
		
		foreach ($mname as $index=>$movie) {	//ignore duplicate api returns
			foreach ($mname as $index2=>$in) {
				if ($movie == $in && $index != $index2) {
					$mname[$index]="skipthisone";
				}
			}
		}
		
		foreach ($movies as $index=>$movie) {	//fetch torrents, choose best one for each movie, and append to display list
			if ($mname[$index] != "skipthisone") {
				array_push($displayList, findBestTorrent($movie, $mname, $index, 0, 0));
			}
		}
		
		foreach ($displayList as $index=>$movie) {
			if ($movie["Found"]) {
				echo '<a href="http://www.thepiratebay.se' . $movie["Link"] . '"><img src="' . $movie["Poster"] . '"/></a>';
			}
			else {
				echo '<img src="' . $movie["Poster"] . '"/>';
			}
		}
	}
	
	*/
	#print_r($displayList);
}
$db->close();
$endtime = microtime(true);
$timediff = $endtime - $starttime;
echo("execution time: ".$timediff."s");

function torrentToApi($query, $type) {
	switch ($type) {
		case "popular":
			$torResult = newTPBSearch("", "", "hd", 30);
			break;
		default:
			return 0;
	}
	
	$blackList = array("3d", "trilogy", "duology", "spanish", "french", "brazilian", "russian", "portugese", "dutch", "german", "pack");
	$displayList = array();
	$years=array();
	foreach($torResult as $index=>&$movie) {	//normalize
		if (!containsOneOf($blackList, $movie["Title"])) {	//filter out the bad apples
			#echo $movie["Title"]."<br/>";
			$movie["Title"] = normalize($movie["Title"]);	//build a new array of normalized torrent titles
			preg_match('/(19[0-9][0-9]|2[0-9][0-9][0-9])/', $movie["Title"], $matches);
			$years[$index] = $matches[1];
			if (!$matches[1] == "") {
				$movie["Title"] = trim(strstr($movie["Title"], $matches[1], true));//if we find a date, truncate after the date
				$movie["Title"] = normalize($movie["Title"]);	//take out 3d and extended
			}
			else {
				$movie["Title"] = "skipthisone";
			}
		}
		else {
			$movie["Title"] = "skipthisone";
		}
		debug($movie["Title"]."<br/>");
	}
	
	foreach ($torResult as $in1=>&$movie1) {	//ignore duplicate movies
		foreach ($torResult as $in2=>&$movie2) {	//ignore duplicate movies
			if ($movie2["Title"] == $movie1["Title"] && $in1 != $in2 && $movie1["Title"] != "skipthisone") {
				$movie2["Title"]="skipthisone";
				break;
			}
		}
	}

	$displayList = array();
	foreach ($torResult as $index=>$movie) {
		if ($movie["Title"] != "skipthisone") {
			$movies = queryMovieAPIPrivate($movie["Title"]);
			
			$mname=array();
			foreach ($movies as $in=>$movieReturn) {	//normalize list of movie titles
				$temp = normalize($movieReturn->title);
				debug($temp. ":::" .  $years[$index] . " " . $movie["Title"] . "::" . $movieReturn->year . " " . ($temp == $movie["Title"]&& $years[$index] == $movieReturn->year) . "<br/>");
				if ($temp == $movie["Title"] && $years[$index] == $movieReturn->year) {
					$displayList[$index] = $movieReturn;
					break;
				}
			}
		}
	}
	
	end($displayList);
	$temp=key($displayList);
	foreach ($displayList as $index=>$movie) {
		if ($index == $temp) {break;}
		echo '<a href="'.$torResult[$index]["MagnetLink"].'"><img class="link" data-magnet="' . $torResult[$index]["MagnetLink"] . '" src="' . $movie->posters->detailed . '"/></a>';
	}
}

function apiToTorrent($query, $type) {
	//new search -- api search determines torrent query
	switch ($type) {
		case "query":
			$movies = queryMovieAPI($query);	//returns an array of arrays of matching movies
			break;
		case "toprentals":
			$movies = queryMovieAPIPrivate("http://api.rottentomatoes.com/api/public/v1.0/lists/dvds/top_rentals.json");	//returns an array of arrays of matching movies
			break;
		case "newreleases":
			$movies = queryMovieAPIPrivate("http://api.rottentomatoes.com/api/public/v1.0/lists/dvds/new_releases.json");	//returns an array of arrays of matching movies
			break;
		default:
			return 0;
	}
	
	$displayList = array();
	$mname=array();
	foreach ($movies as $movie) {	//normalize list of movie titles
		array_push($mname, normalize($movie->title));
	}
	
	#echo "<br/><br/>";
	foreach ($movies as $index=>$movie) {	//fetch torrents, choose best one for each movie, and append to display list
		if ($mname[$index] != "skipthisone") {
			debug("looking for ".$mname[$index]."<br/>");
			print_r($movie);
			addMovie($movie);
			$displayList[$index] = findBestTorrent($movie, $mname, $index, 0);
		}
	}
	
	foreach ($displayList as $index=>$movie) {
		foreach ($movie as $index1=>$quality) {
			if ($quality["Found"]) {
				echo '<a href="'.$quality["MagnetLink"].'"><img class="tlink" data-magnet="'. $quality["MagnetLink"] . '" src="' . $quality["Poster"] . '" title="'.$index1.'"/></a>';
			}
			else {
				//echo $index1.' not found.';
			}
		}
	}
}

function debug($in) {
	global $debug;
	if ($debug) {echo $in;}
}

function normalize($str) {
	$str = preg_replace('/[^a-z\d ]/i', '', $str);		//remove non alpha numeric characters
	$str = preg_replace("/\s+/", ' ', $str); //remove extra spaces;
	$str = strtolower(trim($str));
	$str = preg_replace('/part.([0-9])|p.([0-9])|part([0-9])|p([0-9])|pt.([0-9])|pt([0-9])/i', "part $1$2$3$4$5$6", $str);		//handle parts
	$str = str_replace(' s ', '\'s ', $str);	//put apostrophes back in
	if (substr($str, -2, 2) == " s") {$str=substr_replace($str, '\'s', -2, 2);}
	if (substr($str, -2, 2) == "3d") {$str=substr_replace($str, '', -3, 3);}	//remove '3d' from end of string
	if (substr($str, -8, 8) == "extended") {$str=substr_replace($str, '', -8, 8);}	//remove 'extended' from end of string
	if (substr($str, -7, 7) == "unrated") {$str=substr_replace($str, '', -7, 7);}	//remove 'unrated' from end of string
	if (hasNumeral($str)) {$str = numeralToInt($str);} //convert numerals to integer
	return trim($str);
}

function findBestTorrent($movie, $mname, $dex, $_quality) {
	debug($_quality);
	$minSeeds=1;	//minimum seeds required to be included
	$minSeedRatio=0.7;
	$seedThreshold=50;	//threshold at which to apply seed ratio filtering
	$minSize=600;	//minimum size movie torrents can be
	$maxSDSize=1000;	//maximum size in MB of the dvdrip quality, also preferred that HD size is above this
	$minCamSize=400;	//minimum size cams can be
	$hiSeed=1000;	//trivial number of seeds
	
	if ($_quality == 0) {$_quality = "";}
	if (strlen($_quality) == 0) {$year = urlencode($movie->year);}
	else {$year = "";}
	
	$torResultSD = newTPBSearch(urlencode($mname[$dex]), $year, "sd", 15);  //returns array of results based on $movies->movie->title
	$torResultHD = newTPBSearch(urlencode($mname[$dex]), $year, "hd", 15);  //returns array of results based on $movies->movie->title
	
	if (strlen($_quality) == 0) {$qualities = array("1080", "720", "dvdrip", "cam");}	//if passed $_quality of 0, compute all qualities
	else {$qualities = array($_quality);}	//if passed nonzero $_quality, compute only that quality
	$keywords = array("1080"=> array ("bdrip", "brrip", "blu-ray", "bluray", "bdr", "bdscr"), "720"=> array ("bdrip", "brrip", "blu-ray", "bluray", "bdr", "bdscr"), "dvdrip"=> array ("480", "dvd-rip", "dvdrip", "dvdr", "dvd", "screener", "scr", "dvdscr", "dvdscreener", "r5", "telecine", "tc", "ddc"), "Cam"=> array ("telesync", "camrip", "cam", "pdvd", "predvd", "pre-dvd"));
	$blackList = array("3d", "trilogy", "duology", "quadrilogy", "quintrilogy", "spanish", "french", "brazilian", "russian", "portugese", "dutch", "german", "swedish", "saga", "anthology");
	
	$return = array();
	$movieTitle=normalize($movie->title);
	
	foreach ($qualities as $ind=>$quality) {
		if (strlen($_quality) == 0) {debug("<div><br/><br/><br/>looking for ".$quality." using year search<br/>");}
		else {debug("<br/>looking for ".$quality." using non-year search<br/>");}
		
		if ($quality == "1080" || $quality == "720") {$torResult = $torResultHD;}
		else {$torResult = $torResultSD;}
		
		if ($torResult) {
			$hiScore=0;	//highest score among all accepted torrents
			$hiSeedCount=0;	//number of torrents having more than $hiSeed, used to derive more granular results given absolute results
			$tname=array();
			
			foreach ($torResult as $index=>&$curTor) {	//normalize the titles and search for blacklisted words
				$tname[$index]=normalize($curTor["Title"]);
				$titleCheck = strtolower(str_replace($movieTitle, "", $tname[$index]));
				if (containsOneOf($blackList, $titleCheck)) {
					debug($titleCheck." contains blacklisted word, skipping<br/>");
					unset($torResult[$index]);
				}
				elseif (trim($curTor["Title"]) == "") {unset($curTor);}
			}
			
			//print_r($torResult); debug("<br/>");
			foreach ($torResult as $index=>&$curTor) {
				if (strlen($tname[$index]) == 0) {$curTor["Score"]=0; continue;}
				if (!checkTitle($tname[$index], $movieTitle)) {$curTor["Score"]=0; continue;}
				if (!checkSize($curTor["Size"], $minSize)) {debug($tname[$index]." failed size check (".$curTor["Size"]."/".$minSize."MB</br>"); $curTor["Score"]=0; continue;}
				if ($curTor["Seeds"] < $minSeeds) {debug($tname[$index]." seeds less than minimum (".$curTor["Seeds"]."/".$minSeeds.")</br>"); $curTor["Score"]=0; continue;}
				$curTor["Score"]+=1;
				$titleCheck = strtolower(str_replace($movieTitle, "", $tname[$index]));
				
				$qualityCheck=false;
				if (stripos($titleCheck, $quality)) {$qualityCheck=true; $curTor["Score"]+=20;}
				elseif (!stripos($titleCheck, $quality) && $hiScore >= 20 && $quality != "cam") {debug($tname[$index]." quality is ensured elsewhere and not here, skipping<br/>"); $curTor["Score"]=0; continue;}	//if quality is ensured in other torrents, then skip those we're unsure of
				elseif (!stripos($titleCheck, $quality) && $hiScore < 20 && ($quality == "1080" || $quality == "720")) {debug($tname[$index]." is hd and does not contain correct quality, skipping<br/>"); $curTor["Score"]=0; continue;}
				
				$con=0;
				foreach ($qualities as $t=>$qua) {
					if ($qua != $quality) {	//if year isn't included in search, discard torrent if it contains keywords from other qualities. Also discard torrent if it contains keywords that are absolutely of another quality
						if ((containsOneOf($keywords[$qua], $titleCheck) && strlen($_quality) > 0) || stripos($titleCheck,$qua)) {	
							$curTor["Score"]=0; $con=1;
							debug($titleCheck." contains blacklisted quality word, skipping<br/>"); 
						}
					}
				}
				if ($con) {$curTor["Score"]=0; continue;}
				
				$match = explode(' ', $titleCheck);
				if ((trim($match[0]) > 1 && trim($match[0]) < 6) || isNumeral(trim($match[0]))) {debug($tname[$index]." is a sequel, skipping<br/>"); $curTor["Score"]=0; continue;}	//check if it's a sequel
				if ($curTor["Seeds"] <= $seedThreshold && $curTor["SeedRatio"] > $minSeedRatio) {$curTor["Score"]-=4;} //if there's a low amount of seeds and the ratio is high, penalize score
				if ($quality == "dvdrip" && getSizeMB($curTor["Size"] > $maxSDSize)) {debug($tname[$index]." is dvdrip quality and is more than ".$maxSDSize."MB."); $curTor["Score"] = 0; continue;}	//if quality is 480, restrict size to < maxSDSize
				
				$con=0;
				foreach ($mname as $key) {	//if a longer movie title is contained within the current torrent, skip it
					if (containsAllOfR($tname[$index], $key) && $key != $movieTitle && strlen($tname[$index]) < strlen($key) && substr($tname[$index],0,4) != "the ") {
						$con=1;
						debug($tname[$index]." contains ".$key.", skipping<br/>"); 
						break;
					}
				}
				if ($con) {$curTor["Score"]=0; continue;}
				preg_match_all('@(19[8,9][0-9]|2[0,1][0-9][0-9])@', $tname[$index], $match);	//check for 1800 < year < 2100
				if (count($match[0]) == 0 && strlen($_quality) > 0) {debug($tname[$index]." doesn't contain a year, skipping<br/>"); $curTor["Score"]=0; continue;}	//if year included in search and year wasn't found ignore torrent
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
				
				if ($qualityCheck == false) {debug($tname[$index]." failed quality check, skipping<br/>"); $curTor["Score"]=0; continue;}
				
				if ($curTor["Seeds"]>$hiSeed) {$hiSeedCount+=1;}	//track number of torrents having a trivial number of seeds
				$curTor["Score"] += .01/$curTor["SeedRatio"];
				if ($curTor["Seeds"] > 0) {$curTor["Score"] += $curTor["Seeds"]/100000;}
				$curTor["Quality"]=$quality;
				$curTor["Score"]=round($curTor["Score"],3);
				debug($tname[$index]." passed filters with a score of ".$curTor["Score"]."<br/>");
				if ($curTor["Score"] > $hiScore) {$hiScore = $curTor["Score"];}
			}
			
			debug(strlen($_quality) == 0);
			/*if (($hiScore <= 1 || count($torResult) == 0) && strlen($_quality) == 0) {	//outer search failed, try without year stipulated
				$noYearSearch = findBestTorrent($movie, $mname, $dex, $quality);
				
				if ($noYearSearch != null) {
					$return[$quality]=assignMatches($noYearSearch[0], 1, array());
				}
				else {
					debug("failed to find a match<br/>");
					$return[$quality]=assignMatches($movie, 0, array());
				}
				continue;
			}
			elseif (($hiScore <= 1 || count($torResult) == 0) && strlen($_quality) > 0) {return null;}	//inner search failed, no matches possible*/
			debug("hiscore: ".$hiScore."<br/>");
			if ($hiScore <= 1 || count($torResult) == 0) {$return[$quality]=assignMatches($movie, 0, array()); continue;}
			
			$tempKeys = array();
			if ($hiScore > 20) {	//if matched quality in title of any torrent, we can be sure of authenticity. Ignore scoring and sort by seeds.
				debug("sorting by seeds<br/>");
				foreach($torResult as $index=>$curTor) {
					if ($curTor["Score"] < 21) {unset($torResult[$index]);}	//Exclude torrents of questionable quality
					else {$tempKeys[$index] = $curTor["Seeds"];}
				}
			}
			else {
				debug("sorting by score<br/>");
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
				
				debug("calculated stddev to be ".standard_deviation($tempSeeds)."<br/>");
				if (standard_deviation($tempSeeds) < $hiSeed*0.8) {	//if there's a large deficit in seeds among torrents with trivial seed counts, prefer more seeds to larger file
					debug("sorting by size<br/>");
					array_multisort($tempKeys, SORT_DESC, $tempSize);	//sort the tempSize array by size

					foreach($tempResult as $index=>$curTor) {	//push the tempResult array onto the tempSize array
						array_push($tempSize, $curTor);
					}
					$torResult = $tempSize;	//make it so
				}
			}	
			print_r($torResult);
			
			if (strlen($_quality == 0)) {	//if outer search, set match found
				if ($torResult[0]["Score"]>0 && $torResult[0]["Seeds"]>$minSeeds) {
					$return[$quality]=assignMatches($movie, 1, $torResult[0]);	//make it so
					
					/////////////////////////////////////////////////////////////////////////////////////////
					fetchTorrent($torResult[0]["MagnetLink"]);		//get the torrent file to detect contents
					/////////////////////////////////////////////////////////////////////////////////////////
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
					debug("failed to find a match<br/>");
					$return[$quality]=assignMatches($movie, 0, array());
				}
				continue;
			}
			elseif (($hiScore <= 1 || count($torResult) == 0) && strlen($_quality) > 0) {return null;}	//inner search failed, no matches possible*/
			$return[$quality]=assignMatches($movie, 0, array());
		}
		debug("</div>");
	}
	return $return;
}

function fetchTorrent($magnet) {
	$aria2 = new aria2();
	debug($magnet);
	$gid=$aria2->addUri(array($magnet),array('dir'=>'.'));
	debug("<br/>".$gid["result"]."<br/>");
}

function assignMatches($movie, $found, $curTor) {
	$matches=array();
	$matches = $curTor;
	$matches["ID"] = $movie->id;
	$matches["Poster"] = $movie->posters->detailed;
	$matches["Found"] = $found;
	if ($matches["Found"] == 1) {debug("chose " . $matches["Title"] . "<br/><br/>");}
	else {debug("no matches found". "<br/><br/>");}
	return $matches;
}
	
function queryMovieAPIPrivate($search) {
	$apikey = 'ey7hpddwdeucy54c7rznbzjv';
	
	if (strpos($search, "http://") > -1) {
		$q = str_replace(' ', '%20', $search);
		$data=curl_get($q . "?apikey=" . $apikey);
	}
	else {
		$q = urlencode($search); // make sure to url encode an query parameters
		$data = curl_get('http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey=' . $apikey . '&q=' . $q . '&page_limit=50');
	}
	
	// decode the json data to make it easier to parse the php
	$search_results = json_decode($data);
	if ($search_results === NULL) die('Error parsing json');
	$movies = $search_results->movies;

	foreach ($movies as $movie) {
	  if ( $movie->posters->detailed == "http://images.rottentomatoescdn.com/images/redesign/poster_default.gif") {
			$movie->posters->detailed = "images/poster_default.gif";
	  }
	}

 return $movies;
}

function queryMovieAPI($search) {
	//username=normalpeople, password=lightning9206
	$apikey = 'ey7hpddwdeucy54c7rznbzjv';
	$q = urlencode($search); // make sure to url encode an query parameters
	$data = curl_get('http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey=' . $apikey . '&q=' . $q . '&page_limit=50');
	
	// decode the json data to make it easier to parse the php
	$search_results = json_decode($data);
	if ($search_results === NULL) die('Error parsing json');
	$movies = $search_results->movies;
	
	foreach ($movies as $movie) {
	  if ( $movie->posters->detailed == "http://images.rottentomatoescdn.com/images/redesign/poster_default.gif") {
			$movie->posters->detailed = "images/poster_default.gif";
	  }
	}
 return $movies;
}

function explodeMovie($q) {
	$mparts = explode(' ', $q);
	foreach($mparts as $val) {
		$val = preg_replace("[^a-zA-Z0-9_\s]", '', strtolower($mparts[$y]));
		$val = preg_replace("/(?![.=$'€%-])\p{P}/u", '', $mparts[$y]);
	}
	
	return $mparts;
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
		debug('http://thepiratebay.sx/browse/' . $category . '/0/' . $orderby. "<br/>");
	}
	elseif (strlen($year) == 0 && strlen($q) > 0) {
		$return=curl_get('http://thepiratebay.se/search/' . $q . '/0/' . $orderby . '/' . $category);
		debug('http://thepiratebay.sx/search/' . $q . '/0/' . $orderby . '/' . $category."<br/>");
	}
	else {
		$return=curl_get('http://thepiratebay.se/search/' . $q . '+' . $year . '/0/' . $orderby . '/' . $category);
		debug('http://thepiratebay.sx/search/' . $q . '+' . $year . '/0/' . $orderby . '/' . $category."<br/>");
	}

	//extract the important data from the html
	
	if (strpos($return, "No hits. Try adding")) {	//if no match was found
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
			preg_match_all("/$regexp/siU", $parts[$i], $matches);
			$torrents[$i]["Link"] = $matches[2][0];
			$torrents[$i]["MagnetLink"] = $matches[2][1];
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
				$torrents[$i]["DateUploaded"] = date("m d Y");
			}
			else if (stristr($torrents[$i]["DateUploaded"], ":") > -1) {
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], '', -10, 6);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], date("Y"), -5);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], ' ', -4, 0);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], ' ', 2, 1);
			}
			else {
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], '', -10, 6);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], ' ', -4, 0);
				$torrents[$i]["DateUploaded"] = substr_replace($torrents[$i]["DateUploaded"], ' ', 2, 1);
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

function curl_get($url){
    $useragent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch,CURLOPT_COOKIE,"language=nl_EN; c[thepiratebay.se][/][language]=nl_NL");
    $data=curl_exec($ch);
    curl_close($ch);
	//print_r($data);
    return $data;
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
			debug($needle." contains words before title, skipping<br/>");
			return false;
		}
	}
	else {
		if (substr_count($stack, "the") > 1) {
			$explode = explode("the", $stack);
			foreach($explode as $index=>$i) {
				debug($i."<br/>");
				if (strpos($needle,(trim($i))) === false && trim($i) != "") {
					debug($needle." does not contain ".$stack."</br>");
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

function containsOneOf($list, $str) {
	if (!is_array($list)) {$list = explode(' ', $list);}
	foreach ($list as $val) {
		//debug("comparing ".$val." to ".$str);
		if (strpos(strtolower($str), strtolower($val))) {
			//debug (", matched<br/>");
			return 1;
		}
		//debug("<br/>");
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

function consoleOut($text) {
	echo '<script type="text/javascript">console.log("'.$text.'")</script>';
}

function addMovie($movie) {
	if ($movie->year != "") {
		global $db;
		createMovieTable($db,$movie->id);
		$poster=substr($movie->posters->thumbnail,strrpos($movie->posters->thumbnail,"/")+1);
		$poster=substr($poster,0,-8);
		if ($poster == "poster_d") {$poster = "0";}
		
		if ($movie->ratings->audience_score == "100" && $movie->ratings->critics_score == "-1") {$audience_score = "-1"; $critics_score = "-1";}
		else {$audience_score = $movie->ratings->audience_score; $critics_score = $movie->ratings->critics_score;}
		if ($movie->ratings->audience_score == "0" || $movie->ratings->audience_score == "") {$audience_score = "-1";}
		if ($movie->ratings->critics_score == "") {$critics_score = "-1";}
		
		$db->query("INSERT INTO `movies` (`id`,`title`,`critic_score`,`audience_score`,`synopsis`,`critic_consensus`,`year`,`mpaa_rating`,`runtime`,`theatre_release`,`dvd_release`,`poster`,`imdb`,`lastupdate`) VALUES
		('".$movie->id."','".$movie->title."','".$critics_score."','".$audience_score."','".str_replace('\'','%27',$movie->synopsis)."','".str_replace('\'','%27',$movie->critics_consensus)."','".$movie->year."','".$movie->mpaa_rating."','".
		$movie->runtime."','".$movie->release_dates->theater."','".$movie->release_dates->dvd."','".$poster."','".$movie->alternate_ids->imdb."',Now())");
		echo("<br/>".$db->error."<br/>");
		foreach($movie->abridged_cast as $index=>$actor) {	//"abridged_cast":{"name","id","characters"{}}
			createActorTable($db,$actor->id);
			$db->query("INSERT INTO `".$actor->id."_actor` (`id`) VALUES ('".$movie->id."')");
			$db->query("INSERT INTO `actors` (`id`,`name`) VALUES ('".$actor->id."','".$actor->name."')");
			$db->query("INSERT INTO `".$movie->id."_movie` (`id`,`actor`) VALUES ('".$actor->id."','".$actor->name."')");
		}
		
		$send=$movie->title;
		if ($movie->year != "") {$send.="+".$movie->year;}
		getTrailer($movie->id,$send);
	}
}

function getTrailer($id,$name) {
	global $db;
	$name = htmlspecialchars(str_replace(' ', '+', $name));
	echo "safe name:".$name."<br/>";
	$return = curl_get("http://youtube.com/results?search_query=".$name."+trailer");
	$return= substr($return, strpos($return, 'data-context-item-time="'));
	$return= substr($return, strpos($return, 'data-context-item-id="'));
	$return= substr($return,strpos($return,'"')+1);
	$return=substr($return,0,strpos($return,'"')-1);
	echo "trailer:".$return."<br/>";
	if ($return != "__video_id_" && strlen($return) == 11){$db->query("UPDATE `movies` SET trailer='".$return."' WHERE id='".$id."'");}
}

function createDB($db) {
	createMainMovieTable($db);
	createMainActorTable($db);
}

function createActorTable($db,$name) {	//specific to actor, holds movies they're in
	$db->query("CREATE TABLE IF NOT EXISTS ".$name."_actor (
		`id` bigint( 20 ) unsigned NOT NULL ,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMovieTable($db,$name) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS ".$name."_movie (
		`id` bigint( 20 ) unsigned NOT NULL,
		`actor` varchar( 50 ) NOT NULL ,
		PRIMARY key ( `id` ) ,
		key `actor` ( `actor` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMainMovieTable($db) {	//holds all movies and their various information
	$db->query("CREATE TABLE IF NOT EXISTS movies (
		`id` bigint( 20 ) unsigned NOT NULL,
		`title` varchar( 213 ) NOT NULL ,
		`critic_score` varchar( 3 ) NOT NULL ,
		`audience_score` varchar( 3 ) NOT NULL ,
		`synopsis` varchar( 1200 ) NOT NULL ,
		`critic_consensus` varchar( 500 ) NOT NULL ,
		`year` char( 4 ) NOT NULL ,
		`mpaa_rating` varchar( 5 ) NOT NULL ,
		`runtime` varchar( 3 ) NOT NULL ,
		`theatre_release` datetime NOT NULL ,
		`dvd_release` datetime NOT NULL ,
		`poster` varchar( 8 ) NOT NULL ,
		`imdb` varchar( 8 ) NOT NULL ,
		`trailer` varchar( 11 ) NOT NULL ,
		`lastupdate` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL ,
		PRIMARY key ( `id` ) ,
		key `title` ( `title` ) ,
		key `year` ( `year` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMainActorTable($db) {	//holds all actor names and maps them to an id using their index
	$db->query("CREATE TABLE IF NOT EXISTS actors (
		`id` bigint( 20 ) unsigned NOT NULL,
		`name` varchar( 81 ) NOT NULL ,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}
?>

