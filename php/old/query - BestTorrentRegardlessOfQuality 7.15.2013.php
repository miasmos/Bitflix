<?php
//

if (isset($_GET['s'])) {
	$debug=0;
	echo "<br/>Results<br/>";
	apiToTorrent($_GET['s'], "query");
	
	
	echo "<br/>Top Rentals<br/>";
	apiToTorrent($_GET['s'], "toprentals");
	
	echo "<br/>New Releases<br/>";
	apiToTorrent($_GET['s'], "newreleases");
	
	//new Search -- torrents search determine api query
	echo "<br/>Popular On The Pirate Bay<br/>";
	torrentToApi(0, "popular");
	
	
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
	echo("done");
}

function torrentToApi($query, $type) {
	switch ($type) {
		case "popular":
			$torResult = newTPBSearch("", "", "hd", 30);
			break;
		default:
			return 0;
	}
	
	$blackList = array("3d", "trilogy", "duology", "spanish", "french", "brazilian", "russian", "portugese", "pack");
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
			$displayList[$index] =  findBestTorrent($movie, $mname, $index, 0);
		}
	}
	
	foreach ($displayList as $index=>$movie) {
		if ($movie["Found"]) {
			echo '<a href="'.$movie["MagnetLink"].'"><img class="tlink" data-magnet="'. $movie["MagnetLink"] . '" src="' . $movie["Poster"] . '"/></a>';
		}
		else {
			#echo '<img src="' . $movie["Poster"] . '"/>';
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

function findBestTorrent($movie, $mname, $dex, $layer) {
	$maxlayer=3;
	switch ($layer) {
		case 0:
			$torResult = newTPBSearch(urlencode($mname[$dex]), urlencode($movie->year), "hd", 10);  //returns array of results based on $movies->movie->title
			break;
		case 1:
			$torResult = newTPBSearch(urlencode($mname[$dex]), urlencode($movie->year), "sd", 10);
			break;
		case 2:
			$torResult = newTPBSearch(urlencode($mname[$dex]), "", "hd", 10);  //returns array of results based on $movies->movie->title
			break;
		case 3:
			$torResult = newTPBSearch(urlencode($mname[$dex]), "", "sd", 10);
			break;
	}

	if ($torResult) {
		$blackList = array("3d", "trilogy", "duology", "spanish", "french", "brazilian", "russian", "portugese", "pack");
		$hiScore=0;
		$tname=array();
		$movieTitle=normalize($movie->title);
		
		foreach ($torResult as $index=>$curTor) {	//normalize the titles
			$tname[$index]=normalize($curTor["Title"]);
		}
		
		foreach ($torResult as $index=>&$curTor) {
			if (trim($curTor["Title"]) == "") {break;}
			if (checkSize($curTor["Size"]) && $curTor["Seeds"] > 0 && containsAllOf($tname[$index], $movieTitle)) {
				$curTor["Score"]+=1;
				$temp = str_replace($movieTitle, "", $tname[$index]);
	
				preg_match('/\d+/', $temp, $match);
				if ($match[0] > 1 && $match[0] < 12) {$curTor["Score"]=0; continue;}	//check if it's a sequel
				if (containsOneOf($blackList, $temp)) {$curTor["Score"]=0; continue;}	//contain no blacklist words
				if (strpos($tname[$index], $movieTitle) > 0) {$curTor["Score"]=0; continue;}	//check if there's excess words before the title position within the current torrent
				
				$con=0;
				foreach ($mname as $key) {	//if a longer movie title is contained within the current torrent, skip it

				#debug($tname[$index] . " contains " . $key . ":::" . containsAllOfR($tname[$index], $key) . "<br/>"); 
					if (containsAllOfR($tname[$index], $key) && strlen($key)>strlen($movieTitle)) {
						debug($tname[$index] . " contains " . $key . ":::" . containsAllOfR($tname[$index], $key) . "<br/>"); 
						debug($key . ":::" . $movieTitle . "------" . strlen($key) . " > " . strlen($movieTitle) . "<br/>"); 
						$con=1; 
						break;
					}
				}
				if ($con) {$curTor["Score"]=0; continue;}
				
				$match=array();
				$ttemp=0;
				preg_match_all('/([\d]+)/', $tname[$index], $match);
				foreach ($match as $key) {		//explode the current tor string
					foreach ($key as $val) {
						#echo $val."rgr<br/>";
						$val = trim($val);
	
						if (!array_key_exists("Quality", $curTor)) {
							switch($val) {	//video quality
								case 720:
									$curTor["Score"]+=5;
									$curTor["Quality"]=$val;
									debug("720:5, ");
									break;
								case 1080:
									$curTor["Score"]+=8;
									$curTor["Quality"]=$val;
									debug("1080:8, ");
									break;
								case 1280:
									$curTor["Score"]+=10;
									$curTor["Quality"]=$val;
									debug("1280:10, ");
									break;
							}
						}
						
						if ($val >= 1880) {$ttemp+=1;}	//track number of years in title
						
						if ($val == $movie->year) { 	//if year matches
							$curTor["Score"]+=5;
							debug("year:5, ");
						}
					}
					break;
				}
			}
			
			if ($ttemp > 1) {$curTor["Score"]=0; continue;}
			
			if (!array_key_exists("Quality", $curTor)) {
				if ((strpos($temp, "brrip") || strpos($temp, "bdrip")) && $curTor["SearchMethod"] == "SD") {
					$curTor["Score"]+=5;
					$curTor["Quality"]=720;
					debug("brrip:5, ");
				}
			}
			
			$ttemp=array();
			preg_match('/part.([0-9])/', $tname[$index], $ttemp);
			if (strpos($ttemp[0], "part")) {
				$curTor["Score"]+=2;
				debug("part:2, ");
			}
	
			if ($curTor["Score"] > $hiScore) {$hiScore = $curTor["Score"];}
			debug($curTor["Title"] . ":::" . $curTor["Score"] . ":::" . $curTor["Seeds"] . "<br/>");
			
			print_r($curTor);
		}
	
		if ($hiScore == 0) {
			if ($layer <= $maxlayer) {
				return findBestTorrent($movie, $mname, $dex, $layer+1);
			}
			else {
				debug("<br/>original: " . $movie->title . "<br/>normalized: " . $mname[$dex] . "<br/>" . $curTor["Title"] . ":::" . $curTor["Score"] . ":::" . $curTor["Seeds"] . "hiscore=".$hiScore."<br/>");
				return assignMatches($movie, 0, $curTor);
			}
		}
		
		foreach ($torResult as $curTor) {
			if ($curTor["Score"] == $hiScore) {
				if ($curTor["Seeds"] == 0) {
					if ($layer <= $maxlayer) {
						return findBestTorrent($movie, $mname, $dex, $layer+1);
					}
					else {
						return assignMatches($movie, 0, $curTor);
					}
					break;
				}
				if ($torResult[0]["Seeds"]/$curTor["Seeds"] > 0.4 && $curTor["Seeds"] < 15) { //if there's a huge deficit in seeds, ignore scoring
					return assignMatches($movie, 1, $torResult[0]);
				}
				else {
					debug("<br/>original: " . $movie->title . "<br/>normalized: " . $mname[$dex] . "<br/>" . $curTor["Title"] . ":::" . $curTor["Score"] . ":::" . $curTor["Seeds"] . ":::ahiscore=".$hiScore."<br/>");
					return assignMatches($movie, 1, $curTor);
				}
			}
		}
	}
	else {
		if ($layer <= $maxlayer) {
			return findBestTorrent($movie, $mname, $dex, $layer+1);
		}
		else {
			return assignMatches($movie, 0, $curTor);
		}
	}
}

function assignMatches($movie, $found, $curTor) {
	$matches=array();
	$matches = $curTor;
	$matches["ID"] = $movie->id;
	$matches["Poster"] = $movie->posters->detailed;
	$matches["Found"] = $found;
	if ($matches["Found"] == 1) {debug("chose " . $matches["Title"] . "<br/><br/>");}
	else {debug("no matches found");}
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
		$data = curl_get('http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey=' . $apikey . '&q=' . $q . '&page_limit=10');
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
	$data = curl_get('http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey=' . $apikey . '&q=' . $q . '&page_limit=10');
	
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
	debug($year.":::".$q);
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
			else if (stristr($torrent[$i]["DateUploaded"], ":") > -1) {
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

function checkSize($size) {
	$temp = substr($size, -1);
	$size = substr_replace($size, "", strlen($size)-1, 1);
	#echo $size. $temp;
	return ($temp == "M" && $size >= 500) || $temp == "G" || $temp == "T";
}

function containsOneOf($list, $str) {
	if (!is_array($list)) {$list = explode(' ', $list);}
	foreach ($list as $val) {
		if (strpos($str, $val)) {
			return 1;
		}
	}
	return 0;
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

function out($text) {
	echo '<script type="text/javascript">console.log("'.$text.'")</script>';
}
?>
