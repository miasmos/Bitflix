<!doctype html>
<html>
<head>

<!--
	       _                _        _            _          _              _     _      _      
          / /\             /\ \     /\ \         /\ \       _\ \           /\ \ /_/\    /\ \    
         / /  \            \ \ \    \_\ \       /  \ \     /\__ \          \ \ \\ \ \   \ \_\   
        / / /\ \           /\ \_\   /\__ \     / /\ \ \   / /_ \_\         /\ \_\\ \ \__/ / /   
       / / /\ \ \         / /\/_/  / /_ \ \   / / /\ \_\ / / /\/_/        / /\/_/ \ \__ \/_/    
      / / /\ \_\ \       / / /    / / /\ \ \ / /_/_ \/_// / /            / / /     \/_/\__/\    
     / / /\ \ \___\     / / /    / / /  \/_// /____/\  / / /            / / /       _/\/__\ \   
    / / /  \ \ \__/    / / /    / / /      / /\____\/ / / / ____       / / /       / _/_/\ \ \  
   / / /____\_\ \  ___/ / /__  / / /      / / /      / /_/_/ ___/\ ___/ / /__     / / /   \ \ \ 
  / / /__________\/\__\/_/___\/_/ /      / / /      /_______/\__\//\__\/_/___\   / / /    /_/ / 
  \/_____________/\/_________/\_\/       \/_/       \_______\/    \/_________/   \/_/     \_\/  
                                                                                                
									I'm lazy. So I made this.
-->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Bitflix</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="js/youTubeEmbed/youTubeEmbed-jquery-1.0.css">
<link rel="shortcut icon" href="favicon.ico" />
<script src="js/pace.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/youTubeEmbed/youTubeEmbed-jquery-1.0.js"></script>
<script src="js/jquery.swfobject.1-1-1.min.js"></script>
<script src="js/main.js"></script>
</head>

<body>
<div id="header">
	<div id="logo">
		BITFLIX
		<div id="irony">&#8482;</div>
	</div>
	<div method="get" id="search-form">
		<button type="submit" id="search-icon"><i class="icon-search"></i></button>
	    <input autocomplete="off" type="text" id="searchfield" placeholder="movies, actors, genres...">
	</div>
	<ul id="menu">
		<!--li class="menu-browse">BROWSE</li>-->
	</ul>
</div>
<div id="content">
	<?php include 'php/main.php' ?>
	<?php echo "<script>var basePosterURL = '{$imgurl}';</script>"; ?>
	<div id='spacer'></div>
</div>
</body>
</html>
