<?php

function elog($string,$path) {
	//echo ($string."<br/>");
	error_log(date("Y-m-d H:i:s").": ".$string."\r\n", 3, dirname(dirname(__FILE__)).$path);
}

?>