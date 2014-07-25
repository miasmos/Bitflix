#!/usr/bin/php
<?php
	echo $_POST['l'];
	// Include RPC class
	require_once( 'TransmissionRPC.class.php' );
	$rpc = new TransmissionRPC('http://media.hirepoole.com:9091/transmission/rpc', 'nnehls', 'lightning');
	 $target = "test";	//folder to put it in
	
	 try
	 {
		$result = $rpc->add( (string) $_POST['l'] ); // Magic happens here :)
	 } catch (Exception $e)
	 {
		die('Caught exception: ' . $e->getMessage() . PHP_EOL);
	 }
?>