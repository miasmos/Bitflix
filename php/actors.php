<?php

include 'includes/dbConnect.php';
$db = dbConnect();
include 'includes/printCategory.php';

if (empty($_POST['query'])) {echo -1; exit;}
$q = preg_replace("/[^A-Za-z0-9]/", " ", $_POST['query']);
$q = $db->real_escape_string($q);

if (strlen($q) >= 0 && trim($q) !== '') {
	$ret = @printActors("SELECT * FROM `actor` INNER JOIN `movie_actor` on actor.id=movie_actor.actorid WHERE movieid = {$q} LIMIT 2");
	if ($ret == -1) {echo $ret;}
}
else {
	echo -1;
}

?>