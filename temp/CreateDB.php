<?php

include 'includes/dbConnect.php';

$conn = dbConnect();
createDB($conn);
echo("done");
$conn->close();

function createDB($db) {
	createMovieTable($db);
	createActorTable($db);
	createTorrentTable($db);
	createMovieActorTable($db);
	createMovieSimilarTable($db);
	createGenreTable($db);
	createMovieGenreTable($db);
	createLanguageTable($db);
	createMovieLanguageTable($db);
	createCompanyTable($db);
	createMovieCompanyTable($db);
	createKeywordTable($db);
	createMovieKeywordTable($db);
	createQualityTable($db);
	createListTable($db);
}

function createGenreTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS genre (
		`id` smallint unsigned NOT NULL,
		`genre` varchar( 30 ) NOT NULL ,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMovieGenreTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS movie_genre (
		`movieid` bigint( 20 ) unsigned NOT NULL,
		`genreid` smallint NOT NULL ,
		UNIQUE key ( `movieid` , `genreid` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createLanguageTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS language (
		`id` smallint unsigned auto_increment NOT NULL,
		`language` varchar( 30 ) NOT NULL ,
		PRIMARY key ( `language` ) ,
		UNIQUE key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMovieLanguageTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS movie_language (
		`movieid` bigint( 20 ) unsigned NOT NULL,
		`languageid` varchar( 30 ) NOT NULL ,
		UNIQUE key ( `movieid` , `languageid` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createCompanyTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS company (
		`id` smallint unsigned NOT NULL,
		`company` varchar( 30 ) NOT NULL ,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMovieCompanyTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS movie_company (
		`movieid` bigint( 20 ) unsigned NOT NULL,
		`companyid` smallint NOT NULL ,
		UNIQUE key ( `movieid` , `companyid` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createKeywordTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS keyword (
		`id` bigint unsigned NOT NULL,
		`keyword` varchar( 200 ) NOT NULL ,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMovieKeywordTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS movie_keyword (
		`movieid` bigint( 20 ) unsigned NOT NULL,
		`keywordid` bigint NOT NULL ,
		UNIQUE key ( `movieid` , `keywordid` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMovieTable($db) {	//holds all movies and their various information
	$db->query("CREATE TABLE IF NOT EXISTS movie (
		`id` bigint( 20 ) unsigned NOT NULL,
		`title` varchar( 213 ) NOT NULL ,
		`vote_average` float NOT NULL ,
		`vote_count` mediumint unsigned NOT NULL ,
		`overview` varchar( 1200 ) NOT NULL ,
		`runtime` smallint unsigned NOT NULL ,
		`release_date` datetime NOT NULL ,
		`year` smallint unsigned NOT NULL ,
		`poster_image` varchar( 32 ) NOT NULL ,
		`backdrop_image` varchar( 32 ) NOT NULL ,
		`imdb` varchar( 8 ) NOT NULL ,
		`budget` bigint NOT NULL ,
		`revenue` bigint NOT NULL ,
		`trailer` varchar( 11 ) NOT NULL ,
		`popularity` float NOT NULL ,
		`lastupdate` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL ,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createListTable($db) {
	$db->query("CREATE TABLE IF NOT EXISTS list (
		`listname` varchar( 50 ) NOT NULL,
		`name` varchar( 50 ) NOT NULL,
		`value` varchar( 50 ) NOT NULL,
		`rank` tinyint unsigned NOT NULL,
		UNIQUE key (`listname`,`value`)
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createActorTable($db) {	//holds all actors
	$db->query("CREATE TABLE IF NOT EXISTS actor (
		`id` bigint( 20 ) unsigned NOT NULL,
		`name` varchar( 81 ) NOT NULL ,
		`picture` varchar ( 32 ) NOT NULL ,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createBadTorrentTable($db) {	//stores all blacklisted torrents due to not containing a video file
	$db->query ("CREATE TABLE IF NOT EXISTS badtorrents (
		`id` varchar( 40 ) NOT NULL,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createTorrentTable($db) {
	$db->query("CREATE TABLE IF NOT EXISTS torrent (
		`movieid` bigint( 20 ) unsigned NOT NULL,
		`title` varchar( 65 ) NOT NULL ,
		`link` varchar( 100 ) NOT NULL ,
		`magnet` varchar( 40 ) NOT NULL ,
		`magnetend` varchar( 1000 ) NOT NULL ,
		`uploaddate` datetime NOT NULL ,
		`size` float( 10,3 ) NOT NULL ,
		`seeds` mediumint unsigned NOT NULL ,
		`leeches` mediumint unsigned NOT NULL ,
		`ratio` float unsigned NOT NULL ,
		`score` float( 10,3 ) NOT NULL ,
		`rank` tinyint unsigned NOT NULL ,
		`quality` varchar( 3 ) unsigned NOT NULL ,
		`confirmed` smalling( 1 ) NOT NULL ,
		`lastupdate` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL ,
		UNIQUE key (`movieid`,`magnet`)
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createQualityTable($db) {	//holds all actors
	$db->query("CREATE TABLE IF NOT EXISTS quality (
		`id` tinyint unsigned auto_increment NOT NULL ,
		`quality` varchar( 10 ) NOT NULL ,
		`rank` tinyint unsigned NOT NULL ,
		PRIMARY key ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMovieActorTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS movie_actor (
		`movieid` bigint( 20 ) unsigned NOT NULL,
		`actorid` bigint( 20 ) NOT NULL ,
		`character` varchar( 80 ) NOT NULL ,
		UNIQUE key ( `movieid` , `actorid` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

function createMovieSimilarTable($db) {	//specific to movie, holds actors in the movie
	$db->query("CREATE TABLE IF NOT EXISTS moviesimilar (
		`movieid` bigint( 20 ) unsigned NOT NULL,
		`movieid_similar` bigint( 20 ) unsigned NOT NULL ,
		UNIQUE key ( `movieid` , `movieid_similar` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8"
	);
}

?>