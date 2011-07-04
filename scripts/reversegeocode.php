<?php


/**
 * Web Service Wrapper for Reverse Geocoding (demonstration)
 * 'lon' (first) and 'lat' (second) parameters take coordinate values (default format is JSON; use 'serialized' for php)
 * @example php reversegeocode.php -122.042482 37.370415 [serialized]
 * @return more-or-less native geoplanet webservice return
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2010 - Tyler Bell
 */

//error_reporting(0);

require_once ('../class.geoengine.php');
$engine = geoengine :: getInstance();
$res = array ();
if (!empty($argv[1]) && !empty($argv[2])) {
	$res = $engine->reversegeocode($argv[1], $argv[2]);
} else {
	$engine->logMsg(__METHOD__. " parameter missing");
	$res = null;
}

//Format, return
if ($argv[3] == "serialized") {
	$res = serialize($res);
} else {
	foreach ($res as $key => $value){
		$res[$key] = (int)$value; //cast
	}
	$res = json_encode($res);
}
echo $res."\n";
?>
