<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Bitflix : No archives, no viruses, no bullshit</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="js/youTubeEmbed/youTubeEmbed-jquery-1.0.css">
<link rel="shortcut icon" href="favicon.ico" />
</head>

<body>
<div id="header">
	<div id="logo">
		BITFLIX
		<div id="irony">&#8482;</div>
	</div>
	<form method="get" id="search-form">
		<input type="submit" id="search-icon" value="f">
	    <input autocomplete="off" type="text" name="s" id="searchfield" placeholder="movies, actors, genres">
	</form>
</div>
<div id="content">
	<?php include 'php/main.php' ?>
	<div id='spacer'></div>
</div>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="js/youTubeEmbed/youTubeEmbed-jquery-1.0.js"></script>
<script src="js/jquery.swfobject.1-1-1.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
