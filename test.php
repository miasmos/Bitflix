<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<?php
	include 'php/includes/dbConnect.php';
	$c = dbConnect();
	$return = $c->query("SELECT * FROM actor");
	//print_r(mysqli_result($return,0));
	while($row = $return->fetch_assoc()) {
		print_r($row);
	}
	$c->close();
?>
</body>
</html>