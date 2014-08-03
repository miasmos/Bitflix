<?php
function printCategory($query,$title="",$featured=0) {
	global $db,$imgurl;

	$select = $db->query($query);
	if (mysqli_num_rows($select) > 0) {
		if (!$featured) {
			echo "<div class='category'><div class='movie-wrapper'>";
		} else {
			echo "<div class='category'><div class='featured-movie-wrapper'>";
		}

		$select = mysqli_fetch_all($select,MYSQLI_ASSOC);
		$select = collapseQualities($select);

		for($i=0;$i<count($select);$i++) {
			$row = $select[$i];
			$multipleQualities = array_key_exists('other_qualities',$row) ? true : false;

			if (empty($title)) {if (!empty($row['genre'])) {$title=$row['genre'];}}
			$rating=strval(104*(floatval($row['vote_average'])/10));
			if ($row['overview'] == null || $row['overview'] == "") {$row['overview'] = "An overview is not available.";}
			if (!$featured) {
				echo "<div class='movie'><div class='poster'>";
				if (!$multipleQualities) {echo "<a href='magnet:?xt=urn:btih:{$row['magnet']}{$row['magnetend']}'>";}
				if ($row['poster_image'] != '') {
					echo "<img src='{$imgurl}w154/{$row['poster_image']}' />";
				} else {
					echo "<img src='images/default-154.jpg' />";
				}
				if (!$multipleQualities) {echo "</a>";}
			}
			else {
				echo "<div class='featured-movie'>
				<div class='featured-poster'>";
				echo "<img src='{$imgurl}w300/{$row['backdrop_image']}' />";
			}

			echo "<div class='poster-backer'></div>
				</div>";

			$singleQuality = $multipleQualities ? '' : 'quality-top-only';
			if ($multipleQualities) {
				echo "<div class='quality'>
						<div class='quality-top {$singleQuality} whole'><a href='magnet:?xt=urn:btih:{$row['magnet']}{$row['magnetend']}'>
							<p>
								{$row['quality']}
							</p>
							<p>
								{$row['size']}
							</p>
						</a></div>";

				switch (count($row['other_qualities'])) {
					case 1:
						$bottomWidthClass = 'whole';
						break;
					case 2:
						$bottomWidthClass = 'half';
						break;
					case 3:
						$bottomWidthClass = 'third';
						break;
				}
				
				for($j=0; $j<count($row['other_qualities']); $j++) {
					$other_qualities = $row['other_qualities'][$j];
					echo "<div class='quality-bottom {$bottomWidthClass}'><a href='magnet:?xt=urn:btih:{$select[$other_qualities]['magnet']}{$select[$other_qualities]['magnetend']}'>
							<p>
								{$select[$other_qualities]['quality']}
							</p>
							<p>
								{$select[$other_qualities]['size']}
							</p>
						</a></div>";
				}
				$i += count($row['other_qualities']);
				echo "</div>";
			}
			echo "<div class='info'>
					<div class='info-overview'>{$row['overview']}</div>
					<div class='info-inner'>
						<div class='info-rating'>";
							if ($row['vote_average']>0) {echo "<div class='info-rating-front'><img src='images/stars.png'/></div>";
													   echo "<div class='info-rating-backer' style='width:".$rating."px'></div>";}
				  echo "</div>
						<div class='info-title'>
							<div class='info-title-info'>";
								if ($row['runtime'] != '0') {echo "<span style='float:right;'>{$row['runtime']}MIN</span><br/>";}
								if ($row['year'] != '0') {echo "<span style='float:right;'>{$row['year']}</span>";}
					  echo "</div>
							<div class='info-title-title'><span>{$row['title']}</span></div>
						</div>
						<ul class='info-menu'>";

					  if (!$multipleQualities) {echo "<a href='magnet:?xt=urn:btih:{$row['magnet']}{$row['magnetend']}'>";}
						echo "<li class='info-menu-icon download'>w</li>";
					  if (!$multipleQualities) {echo "</a>";}
					  if (!empty($row['trailer']) && $row['trailer'] != null) {echo "<li class='info-menu-icon'>
								<a class='trailer-link' href='' data-href='{$row['trailer']}'>5</a>
							</li>";}
				  echo "<ul>
					</div>
				</div>
			</div>";
		}
		echo "</div><div class='category-title'>{$title}</div>";
		echo "<div class='move-left movie-nav hidden'></div><div class='move-right movie-nav'></div></div>";
		return 1;
	} else {
		return -1;
	}
}

function collapseQualities($select) {
	$temp = [];
	for($i=1;$i<count($select);$i++) {
		$curRow = $select[$i];
		$prevRow = $select[$i-1];
		if ($curRow['movieid'] == $prevRow['movieid']) {
			array_push($temp,$i);
		} else {
			if ($temp != []) {
				$select[$i-count($temp)-1]['other_qualities'] = $temp;
				$temp = [];
			}
		}
		$select[$i-1]['quality'] = beautifyQuality($prevRow['quality']);
		$select[$i-1]['size'] = beautifySize($prevRow['size']);
	}

	$select[count($select)-1]['quality'] = beautifyQuality($select[count($select)-1]['quality']);
	$select[count($select)-1]['size'] = beautifySize($select[count($select)-1]['size']);
	
	if ($temp != []) {
		$select[count($select)-count($temp)-1]['other_qualities'] = $temp;
		$temp = [];
	}
	//print_r($select);
	return $select;
}

function beautifyQuality($quality) {
	switch($quality) {
		case "1080":
			return "1080p";
			break;
		case "720":
			return "720p";
			break;
		case "dvdrip":
			return "DVDRIP";
			break;
		case "cam":
			return "CAM";
			break;
	}
	return $quality;
}

function beautifySize($size) {
	if ($size < 1) {
		return round($size*1000)."MB";
	} else {
		return round($size,2)."GB";
	}
}
?>