<?php

// TO DO
//
// optimize db queries
// fix star/title alignment
// add more db query combinations
// implement randomization
// implement search and filtering features
// help menus of some kind
// genre and actor menus

// program 'featured' row..  info revealed by default, wider, full art

// implement user account system
//		email notifications for movie additions
//		RPC capability
//		categories based on downloaded or liked movies
//		taste preferences
include 'includes/dbConnect.php';
$db = dbConnect();

$select = $db->query("SELECT value FROM `settings` WHERE name='baseURL'");
$imgurl = mysqli_fetch_array($select);
$imgurl = $imgurl['value'];
//released on this day
//SELECT * FROM `movie` WHERE MONTH(release_date) = MONTH(NOW()) AND DAY(release_date) = DAY(NOW());

//list based stuff
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `list` on movie.id = list.value WHERE list.listname='now_playing' AND torrent.confirmed=1 AND torrent.rank=1 GROUP BY torrent.movieid LIMIT 30","Now Playing");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `list` on movie.id = list.value WHERE list.listname='popular_actors' AND torrent.confirmed=1 AND torrent.rank=1 GROUP BY torrent.movieid LIMIT 30","Popular Actors");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `list` on movie.id = list.value WHERE list.listname='top_rated' AND torrent.confirmed=1 AND torrent.rank=1 GROUP BY torrent.movieid LIMIT 30","Top Rated");

//made by {insert production company here}
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE movie.year = '2007' AND torrent.confirmed=1 AND torrent.rank=1 GROUP BY torrent.movieid LIMIT 30","2007");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE genre.genre = 'Animation' AND torrent.confirmed=1 AND torrent.rank=1 GROUP BY torrent.movieid LIMIT 30");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE genre.genre = 'Drama' AND torrent.confirmed=1 AND torrent.rank=1 GROUP BY torrent.movieid LIMIT 30");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE genre.genre = 'Comedy' AND torrent.confirmed=1 AND torrent.rank=1 GROUP BY torrent.movieid LIMIT 30");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE genre.genre = 'Thriller' AND torrent.confirmed=1 AND torrent.rank=1 GROUP BY torrent.movieid LIMIT 30");
$db->close();

function printCategory($query,$title="") {
	global $db,$imgurl;
	echo "<div class='category'><div class='movie-wrapper'>";
	$select = $db->query($query);
	if (empty($title)) {
		$row=mysqli_fetch_object($select);
		if (!empty($row->genre)) {$title=$row->genre;}
	}
	while($row = mysqli_fetch_object($select)) {
		$rating=strval(104*(floatval($row->vote_average)/10));
		if ($row->overview == null || $row->overview == "") {$row->overview = "An overview is not available.";}
		echo "<div class='movie'>
			<div class='poster'>
				<img src='{$imgurl}w154/{$row->poster_image}' />
				<div class='poster-backer'></div>
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
}
//quality based (1080p etc)

//fast download (excellent seed ratio)

//unpopular

//low budget
?>