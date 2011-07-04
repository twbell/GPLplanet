<?php


/**
 * Web Service Wrapper for Placename disambiguation (demonstration)
 * Add placename to 'q'parameters (default format is json; use 'format=serialized for php); use 'focus' parameter to bias results
 * @example http://example.com/gplplanet/webservice/disambiguate.php?q="soma, san francisco"
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2010 - Tyler Bell
 */

error_reporting(0);

require_once ('../class.geoengine.php');
$engine = geoengine :: getInstance();
$res = array ();
if ($_REQUEST['q']) {
	if ($_REQUEST['focus']) {
		$res = $engine->disambiguate($_REQUEST['q'], $_REQUEST['focus']);
	} else {
		$res = $engine->disambiguate($_REQUEST['q']);
	}
}
//Format, return
if ($_REQUEST['format'] == "serialized") {
	$res = serialize($res);
} else {
	$res = json_encode($res);
}
echo $res."\n";
?>
