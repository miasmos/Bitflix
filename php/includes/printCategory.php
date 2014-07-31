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
		if (empty($title)) {
			$row=mysqli_fetch_object($select);
			if (!empty($row->genre)) {$title=$row->genre;}
		}
		while($row = mysqli_fetch_object($select)) {
			$rating=strval(104*(floatval($row->vote_average)/10));
			if ($row->overview == null || $row->overview == "") {$row->overview = "An overview is not available.";}
			if (!$featured) {
				echo "<div class='movie'>
				<div class='poster'>";
				echo "<a href='magnet:?xt=urn:btih:{$row->magnet}{$row->magnetend}'><img src='{$imgurl}w154/{$row->poster_image}' /></a>";
			}
			else {
				echo "<div class='featured-movie'>
				<div class='featured-poster'>";
				echo "<a href='magnet:?xt=urn:btih:{$row->magnet}{$row->magnetend}'><img src='{$imgurl}w300/{$row->backdrop_image}' /></a>";
			}
			echo "<div class='poster-backer'></div>
				</div>
				<div class='info'>
					<div class='info-overview'>{$row->overview}</div>
					<div class='info-inner'>
						<div class='info-rating'>";
							if ($row->vote_average>0) {echo "<div class='info-rating-front'><img src='images/stars.png'/></div>";
													   echo "<div class='info-rating-backer' style='width:".$rating."px'></div>";}
				  echo "</div>
						<div class='info-title'>
							<div class='info-title-info'>";
								if ($row->runtime != '0') {echo "<span style='float:right;'>{$row->runtime}MIN</span><br/>";}
								if ($row->year != '0') {echo "<span style='float:right;'>{$row->year}</span>";}
					  echo "</div>
							<div class='info-title-title'>{$row->title}</div>
						</div>
						<ul class='info-menu'>
							<li class='info-menu-icon'>
								<a href='magnet:?xt=urn:btih:{$row->magnet}{$row->magnetend}'>w</a>
								<!--<a href='magnet:howdotheywork?'>w</a>-->
							</li>";
					  if (!empty($row->trailer) && $row->trailer != null) {echo "<li class='info-menu-icon'>
								<a class='trailer-link' href='' data-href='{$row->trailer}'>5</a>
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
?>