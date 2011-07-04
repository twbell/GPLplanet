<?php


/**
 * Web Service Wrapper for Geoplanet getGeo (demonstration)
 * Add woeid to 'woeid'parameter (default format is json; use 'format=serialized for php)
 * @example http://example.com/gplplanet/webservice/get.php?woeid=12345
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009-2011 - Tyler Bell
 */

error_reporting(0);

require_once ('../class.geoengine.php');
$engine = geoengine :: getInstance();
$res = array ();
if ($_REQUEST['woeid']) {
	if ($geo = $engine->getGeo($argv[1])){
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
if ($argv[2] == "serialized") {
	$res = serialize($res);
} else {
	$res = json_encode($res);
}
echo $res."\n";

?>
