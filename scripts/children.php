<?php


/**
 * CmdLn Wrapper for Geoplanet getChildren
 * Add woeid to 'woeid' parameter (default format is JSON; use 'serialized' as second var for serialized php)
 * @example  php children.php 12345 [serialized]
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009-2011 - Tyler Bell
 */

error_reporting(0);

require_once ('../class.geoengine.php');
$engine = geoengine :: getInstance();
$res = array ();
if ($argv[1]) {
	$res = $engine->getChildren($argv[1]);
} else {
	$engine->logMsg(__METHOD__. " WOEID not provided");
	$res = null;
}

//Format, return
if ($argv[2] == "serialized") {
	$res = serialize($res);
} else {
	foreach ($res as $key => $value){
		$res[$key] = (int)$value; //cast
	}
	$res = json_encode($res);
}
echo $res."\n";

?>
