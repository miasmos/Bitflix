<?php
error_reporting(E_ALL);
include 'includes/dbConnect.php';
$db = dbConnect();
include 'includes/printCategory.php';

$select = $db->query("SELECT value FROM `settings` WHERE name='baseURL'");
$imgurl = mysqli_fetch_array($select);
$imgurl = $imgurl['value'];
//released on this day
//SELECT * FROM `movie` WHERE MONTH(release_date) = MONTH(NOW()) AND DAY(release_date) = DAY(NOW());

//UPDATE movie SET rid = (UNIX_TIMESTAMP() + (RAND() * 86400))
//list based stuff
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `list` on movie.id = list.value WHERE list.listname='now_playing' AND torrent.confirmed=1 AND torrent.rank=1 ORDER BY rid DESC LIMIT 30","Now Playing");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `list` on movie.id = list.value WHERE list.listname='popular_actors' AND torrent.confirmed=1 AND torrent.rank=1 ORDER BY rid DESC LIMIT 120","Popular Actors");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `list` on movie.id = list.value WHERE list.listname='top_rated' AND torrent.confirmed=1 AND torrent.rank=1 ORDER BY rid DESC LIMIT 120","Top Rated");

//made by {insert production company here}
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id WHERE movie.year = '2007' AND torrent.confirmed=1 AND torrent.rank=1 ORDER BY rid DESC LIMIT 30","2007");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE genre.genre = 'Animation' AND torrent.confirmed=1 AND torrent.rank=1 ORDER BY rid DESC LIMIT 30");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE genre.genre = 'Drama' AND torrent.confirmed=1 AND torrent.rank=1 ORDER BY rid DESC LIMIT 30");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE genre.genre = 'Comedy' AND torrent.confirmed=1 AND torrent.rank=1 ORDER BY rid DESC LIMIT 30");
printCategory("SELECT * FROM `torrent` INNER JOIN `movie` on torrent.movieid=movie.id INNER JOIN `movie_genre` on movie_genre.movieid=movie.id INNER JOIN `genre` on movie_genre.genreid = genre.id WHERE genre.genre = 'Thriller' AND torrent.confirmed=1 AND torrent.rank=1 ORDER BY rid DESC LIMIT 30");
$db->close();


//quality based (1080p etc)

//fast download (excellent seed ratio)

//unpopular

//low budget
?>