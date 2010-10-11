<?php


/**
 * Web Service Wrapper for Geoplanet heirarchy navigation (demonstration)
 * Add woeid to 'woeid'parameter; nav parameter is either 'children','descendants','parents', or 'ancestors' (default format is json; use 'serialize for php)
 * @example http://example.com/gplplanet/webservice/navigate.php?woeid=12345&nav=parents
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2010 - Tyler Bell
 */

error_reporting(0);

require_once ('../class.geoengine.php');
$engine = geoengine :: getInstance();
$res = array ();
if ($_REQUEST['woeid'] && $_REQUEST['nav']) {
	$methodName = "get" . $_REQUEST['nav'];
	$res = $engine-> $methodName ($_REQUEST['lon'], $_REQUEST['lat']);
}

//Format, return
if ($_REQUEST['format'] == "serialized") {
	$res = serialize($res);
	print_r($res);
} else {
	$res = json_encode($res);
	print_r($res);
}
?>
