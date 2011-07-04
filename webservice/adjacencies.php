<?php


/**
 * Web Service Wrapper for Geoplanet getAdjacencies (demonstration)
 * Add woeid to 'woeid' parameter (default format is json; use 'format=serialized' for php)
 * @example http://example.com/gplplanet/webservice/adjacencies.php?woeid=12345
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009-2011 - Tyler Bell
 */

error_reporting(0);

require_once ('../class.geoengine.php');
$engine = geoengine :: getInstance();
$res = array ();
if ($_REQUEST['woeid']) {
	$res = $engine->getAdjacencies();
} else {
	$engine->logMsg(__METHOD__. " WOEID not provided");
	$res = array();
}

//Format, return
if ($_REQUEST['format'] == "serialized") {
	$res = serialize($res);
} else {
	foreach ($res as $key => $value){
		$res[$key] = (int)$value; //cast
	}
	$res = json_encode($res);
}
echo $res."\n";
?>
