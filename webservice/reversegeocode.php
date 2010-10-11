<?php


/**
 * Web Service Wrapper for Reverse Geocoding (demonstration)
 * Add coordinates to 'lon' and 'lat'parameters (default format is json; use 'serialize for php)
 * @example http://example.com/gplplanet/webservice/reversegeocode.php?lon=-122.042482&lat=37.370415
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2010 - Tyler Bell
 */

error_reporting(0);

require_once ('../class.geoengine.php');
$engine = geoengine :: getInstance();
$res = array ();
if (!empty ($_REQUEST['lon']) && !empty ($_REQUEST['lat'])) {
	$res = $engine->reversegeocode($_REQUEST['lon'], $_REQUEST['lat']);
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
