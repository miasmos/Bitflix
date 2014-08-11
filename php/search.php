<?php

include 'includes/dbConnect.php';
$db = dbConnect();
include 'includes/printCategory.php';

if (empty($_POST['query'])) {echo -1; exit;}
$q = preg_replace("/[^A-Za-z0-9]/", " ", $_POST['query']);
$q = $db->real_escape_string($q);

if (strlen($q) >= 2 && trim($q) !== '') {
	//TODO
	//movie, actor, genre, keywords, companies

	//movie.title DONE
	//movie_actor -> movie.movieid
	//genre->movie_genre->movie
	//keyword->movie_keyword->movie
	//company->movie_company->movie
	//movie.year DONE
	//$ret = printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id WHERE torrent.confirmed = 1 AND torrent.rank = 1 AND movie.title LIKE '%".$q."%' OR movie.year LIKE '%".$q."%' GROUP BY movie.id LIMIT 50",$q);
	//types: 0 movie search, 1 actor search
	$ret = printCategory("SELECT * FROM `torrent` INNER JOIN `movie` ON torrent.movieid = movie.id INNER JOIN `movie_actor` ON movie.id = movie_actor.movieid INNER JOIN `actor` ON actor.id = movie_actor.actorid WHERE actor.name = '{$q}' LIMIT 50",$q);
	if ($ret == -1) {echo $ret;}
}
else {
	echo -1;
}

?>