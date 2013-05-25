<?php


/**
 * CmdLn Wrapper for Geoplanet getGeo -- representation of WOEID
 * Add woeid to 'woeid' parameter, true/false to use allow use of web service to retrieve co-ordinates. 
 * Default format is JSON; use 'serialized' as third var for serialized php
 * @example  php get.php 12345 true [serialized]
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009-2011 - Tyler Bell
 */

error_reporting(0);

require_once ('../class.geoengine.php');
$engine = geoengine :: getInstance();
$res = array ();
if ($argv[1]) {
	if ($geo = $engine->getGeo($argv[1],$argv[2])){
		$res = $geo->getCleanInstance();
	} else {
		$engine->logMsg(__METHOD__. " failed creating geo for WOEID ".$argv[1]);
		$res = null;
	}
} else {
	$engine->logMsg(__METHOD__. " WOEID not provided");
	$res = null;
}

//Format, return
if ($argv[3] == "serialized") {
	$res = serialize($res);
} else {
	$res = json_encode($res);
}
echo $res."\n";

?>
