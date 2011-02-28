<?php


/**
 * geoservice - wraps geo web services. Use as standalone or more powerfully with gplplanet::geoengine
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2011 - Tyler Bell
 * @todo Authenticated YQL
 * @license GNU General Public License
 */

class geoservice {

	public $yqlEndPoint = 'http://query.yahooapis.com/v1/public/yql'; //public query endpoint														//where we store boss api keys and config
	private static $_instance; //singleton management
	public $flags = "G"; 	//geocoder flags

	//cache control
	public $cache = true;		//cache geocoder calls
	
	//web service timings
	protected $lastQuery; //timestamp of last web query, used to control calls-per-second 	 
 	protected $webServiceWait = 500000;  //webservice wait between calls in microseconds (0 = no wait)

	//table definitions
	const TABLEGEOCODECACHE = "cache_geocode";  

	//============================= Methods =================================
	
	 /**
	 * Pauses script appropriate time before calling webservice again
	 * @param timestamp unix timestamp
	 * @return array
	 */  
	 protected function webserviceWait(){
	 	if (!$this->lastQuery){return true;} //no previous query
	 	$microTime = microtime(true);
	 	$timeSince = $microTime-$this->lastQuery;
	 	if ($timeSince > $this->webServiceWait){
	 		return true;
	 	} else {
	 		usleep($timeSince);
	 		return true;
	 	}
	 }

	/**
	 * Caches geocode call
	 * @param string query
	 * @param string response
	 * $return bool
	 */
	protected function writeGeoCodeCache($query,$response){
		if ($this->cache){			
			$md5 = md5($query);
			$query = $this->getEngine()->escapeString(serialize($query));
			$response = $this->getEngine()->escapeString(serialize($response));
			$SQL = "INSERT INTO ".self::TABLEGEOCODECACHE." (md5,query,response) VALUES (\"".$md5."\",\"".$query."\",\"".$response."\")";
			$res = $this->getEngine()->queryDB($SQL);
			if (!$res) {
				$this->logMsg(__METHOD__." failed");
				return false;
			}	
		}
		return true;
	}
	
	/**
	 * Caches geocode call
	 * @param string query
	 * $return bool
	 */
	protected function readGeoCodeCache($query){
		if ($this->cache){		
			$md5 = md5($query);
			$SQL = "SELECT response FROM ".self::TABLEGEOCODECACHE." WHERE md5=\"".$md5."\"";
			$res = $this->getEngine()->queryDB($SQL);
			if (!$res) {
				$this->logMsg(__METHOD__." failed");
				return false;
			}	
			if ($res->num_rows == 1) {
				$row = $res->fetch_array(MYSQLI_ASSOC);
				return unserialize($row['response']);
			} else {
				return false;
			}
		}
		return false;
	}	
	
	/**
	 * Singleton retreival
	 */
	public static function getInstance() {
		if (!(self :: $_instance instanceof self)) {
			self :: $_instance = new self();
		}
		return self :: $_instance;
	}

	/**
	 * Gets geo engine
	 * @return obj geoengine object
	 */
	public function getEngine() {
		if (!class_exists("geoengine")) {
			require_once ('class.geoengine.php');
		}
		return geoengine :: getInstance();
	}

	/**
	* Geocodes address string or placename
	* @param string q 
	* @return array
	*/
	public function geocode($q) {
		$q = "SELECT * FROM geo.placefinder WHERE text=\"" . $q . "\"";
		if ($this->flags){
			$q .= " AND flags=\"".$this->flags."\"";
		}
		  
		//check cache
		if ($res = $this->readGeoCodeCache($q)){
			return $res;
		} 
		
		//hit geocode service
		$res = $this->query($q);
		if (!$res) {
			return false;
		}
		if ($res->query->count > 0) {
			if (is_object($res->query->results->Result)){			
				$returnVals = get_object_vars($res->query->results->Result);
				$this->writeGeoCodeCache($q,$returnVals); //write cache
				return $returnVals;
			} else {
				return array (); //empty result
			}
		} else {
			return array (); //no result
		}
	}

	/**
	* Gets most probable woeid corresponding to a placename
	* Caches result locally
	* @param string q 
	* @param int focus woeid focus 
	* @return gplplanet::geo object
	*/
	public function disambiguate($q, $focus = false) {
		$q = trim($q);
		if (!$focus) {
			$focus = $this->getEngine()->defaultFocus;
		}
		if (!is_numeric($focus)) { //confirm placename not passed
			$this->logMsg(__METHOD__ . " geographic focus must be a woeid, not placename");
			return false;
		}
		$q = "select * from geo.places where text=\"" . $q . "\" AND focus=" . $focus . " LIMIT 1";
		$res = $this->query($q);
		if (!$res) {
			return false;
		}
		if ($res->query->count == 1) {
			$place = $this->serviceToGeo($res->query->results->place);
			return $place;
		} else {
			return false;
		}
	}

	/**
	 * Converts webservice place result (json converted) into an array for easier handling
	 * http://www.wait-till-i.com/2010/09/22/the-annoying-thing-about-yqls-json-output-and-the-reason-for-it/
	 * @param array place json object returned by geoplanet
	 * @return array
	 */
	protected function serviceToArray($place) {
		$place = get_object_vars($place);
		foreach ($place as $key => $value) {
			if (is_object($place[$key])) {
				$place[$key] = get_object_vars($value);
			}
		}
		foreach ($place['boundingBox'] as $key => $value) { //nested one further layer down
			if (is_object($place['boundingBox'][$key])) {
				$place['boundingBox'][$key] = get_object_vars($value);
			}
		}
		return $place;
	}

	/**
	 * Converts webservice place result (json converted) into a gplplanet::geo object
	 * http://www.wait-till-i.com/2010/09/22/the-annoying-thing-about-yqls-json-output-and-the-reason-for-it/
	 * @param array place json object returned by geoplanet
	 * @return array
	 */
	protected function serviceToGeo($place) {
		//simplify geo attributes from json object
		$aGeo['woeid'] = $place->woeid;
		$aGeo['name'] = $place->name;
		$aGeo['placetype'] = $place->placeTypeName->code;
		$aGeo['placetypename'] = $place->placeTypeName->content;
		$aGeo['centroid_lat'] = $place->centroid->latitude;
		$aGeo['centroid_lon'] = $place->centroid->longitude;
		$aGeo['bbox_sw_lat'] = $place->boundingBox->southWest->latitude;
		$aGeo['bbox_sw_lon'] = $place->boundingBox->southWest->longitude;
		$aGeo['bbox_ne_lat'] = $place->boundingBox->northEast->latitude;
		$aGeo['bbox_ne_lon'] = $place->boundingBox->northEast->longitude;
		$aGeo['country'] = $place->country->code;
		//add array in constructor
		require_once ('class.geo.php');
		$geo = new geo($aGeo);
		return $geo;
	}

	/**
	* Geocodes placename using geographic bias
	* @param string q  Placename (not street address)
	* @param int woeid of focus (disambiguation is geographically biased towards the focus)
	* @return array
	*/
	public function geocodeWithFocus($q, $focus) {
		if (!is_numeric($focus)) {
			$this->logMsg(__METHOD__ . " geographic focus must be a woeid");
			return false;
		}
		$q = "select * from geo.places where text=\"" . $q . "\" AND focus=" . $focus;
		if ($this->flags){
			$q .= " AND flags=\"".$this->flags."\"";
		}
		//check cache
		if ($res = $this->readGeoCodeCache($q)){
			return $res;
		}
		//hit geocode service
		$res = $this->query($q);
		if (!$res) {
			return false;
		}
		if ($res->query->count > 0) {
			if (is_object($res->query->results->Result)){			
				$returnVals = get_object_vars($res->query->results->Result);
				$this->writeGeoCodeCache($q,$returnVals); //write cache
				return $returnVals;
			} else {
				return array (); //empty result
			}
		} else {
			return array (); //no result
		}
	}

	/**
	* Reverse geocodes long/lat to the smallest bounding WOEID
	* @param real long Decimal Longitude
	* @param real lat Decimal Latitude
	* @return array single result
	*/
	public function reverseGeocode($lon, $lat) {
		$q = "SELECT * from geo.placefinder WHERE text=\"" . $lat . "," . $lon . "\" AND gflags=\"R\"";
		if ($this->flags){
			$q .= " AND flags=\"".$this->flags."\"";
		}	
		//check cache
		if ($res = $this->readGeoCodeCache($q)){
			return $res;
		}
		//hit geocode service
		$res = $this->query($q);
		if (!$res) {
			return false;
		}
		if ($res->query->count > 0) {
			$returnVals = get_object_vars($res->query->results->Result);
			if ($returnVals['quality'] == 99){
				$returnVals['line1'] = ""; 			//strip out coordinate pair as line1
			}
			$this->writeGeoCodeCache($q,$returnVals); //write cache
			return $returnVals;
		} else {
			return array ();
		}
	}

	/**
	 * Gets elevation from woeid (uses geonames)
	 * @param int woeid
	 * @return int meters above sea level
	 * 
	 */
	public function getElevationbyWoeid($woeid) {
		$centroid = $this->getEngine()->getCentroid($woeid);
		return $this->getElevationbyCoordinate($centroid['lon'], $centroid['lat']);
	}

	/**
	 * Gets elevation from lat/lon (uses geonames - thanks to Dan Pett for this one)
	 * @param real long Decimal Longitude
	 * @param real lat Decimal Latitude
	 * @return int meters above sea level
	 * 
	 */
	public function getElevationbyCoordinate($lon, $lat) {
		if (!$lon || !$lat) {
			$this->logMsg(__METHOD__ . " no lon/lat passed");
			return false;
		}
		$q = "SELECT astergdem FROM json WHERE url=\"http://ws.geonames.org/astergdemJSON?lat=" . $lat . "&lng=" . $lon . "\"";
		$res = $this->query($q);
		if ($res) {
			return $res->query->results->json->astergdem;
		} else {
			return false;
		}
	}

	/**
	 * Instantiate geo object directly from Geoplanet web service
	 * @param int woeid
	 * @return obj geo object
	 */
	public function getGeo($woeid) {
		$q = "SELECT * FROM geo.places WHERE woeid=" . $woeid;
		$res = $this->query($q);
		if ($res) {
			return $this->serviceToGeo($res->query->results->place);
		} else {
			return false;
		}
	}

	/**
	 * Assembles and runs YQL Query
	 * @param string qString Query string
	 * @param array aUserVars user-defined key/value parameters to be taked onto end of URL
	 * @return object json object
	 */
	public function query($qString, $aUserVars = "") {
		if (!$qString) {
			$this->logMsg(__METHOD__ . " No query string passed to YQL");
			return false;
		}

		//build variable array
		$aVars = array (
			'format' => 'json'
		);

		//add variables from parameter
		if ($aUserVars && is_array($aUserVars)) {
			$aVars = array_merge($aVars, $aUserVars);
		}

		//combine keys and values
		foreach ($aVars as $key => $value) {
			if ($value) {
				$aVarComb[] = $key . "=" . urlencode($value);
			}
		}
		unset ($aVars);
		//create data string
		$sData = implode("&", $aVarComb);
		unset ($aVarComb);
		$endPoint = $this->yqlEndPoint . "?q=" . urlencode($qString) . "&" . $sData;
		$this->webserviceWait();		//pause if required before calling webservice again
		@ $result = file_get_contents($endPoint);

		if (!$result) {
			$this->logMsg(__METHOD__ . " YQL Error on " . $qString . ": " . $http_response_header[0] . "\n");
			return false;
		}

		$result = json_decode($result);

		return $result;

	}

	/**
	 * Simple recording of last error - logs to engine log if exists
	 * @param string msg message
	 */
	public function logMsg($msg) {
		$this->getEngine()->logMsg($msg);
		return true;
	}

}
?>
