<?php

include 'includes/dbConnect.php';
$db = dbConnect();
include 'includes/printCategory.php';

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
	$ret=0;
	$ret = printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id WHERE torrent.confirmed = 1 AND torrent.rank = 1 AND movie.title LIKE '%".$q."%' OR movie.year LIKE '%".$q."%' GROUP BY movie.id LIMIT 50",$q);
	if ($ret == -1) {echo $ret;}
}
else {
	echo -1;
}

?>