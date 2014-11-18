
<?php
include 'includes/dbConnect.php';

$db = dbConnect();
db->query("UPDATE movie SET rid = (UNIX_TIMESTAMP() + (RAND() * 86400))");
$db->close();
?>