<?php

/**
 * Core methods for Accessing Yahoo Geoplanet local instance + Webservice
 * Run import.php file via cmdln to import geoplanet data in advance of use
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009-2011 - Tyler Bell
 * @todo support geoplanet belongTos()
 * @license GNU General Public License
 */

class geoengine {

	//defaults
	public $logFile = "/tmp/geoplanet.log"; //set by config file; set to null in config if logging not required

	//cache
 	protected $db = null;

	//table names
	const TABLEPLACES = "geo_places";
	const TABLEPLACETYPES = "geo_placetypes";
	const TABLEPLACENAMES = "geo_placenames";
	const TABLEADJACENCIES = "geo_adjacencies";
	const TABLEPARENTS = "geo_parents";
	const TABLECHILDREN = "geo_children";
	const TABLEDESCENDANTS = "geo_descendants";
	const TABLESIBLINGS = "geo_siblings";
	const TABLEANCESTORS = "geo_ancestors";
	const TABLEDISAMBIGUATE = "cache_disambiguate";
	const TABLECOUNTRIES = "geo_countries";

	//misc
	protected static $_instance; //singleton management\
	protected $lastQuery; //timestamp of last web query, used for requlating query rate
	public $defaultFocus = 1; //woeid of geography used to bias probabilities during disambiguation (example: 1 = world (no bias), 23424977 = USA, etc.) 

	//======================== Methods =======================================	

	/**
	 * Singleton retreival
	 * @return instance of self
	 */
	public static function getInstance() {
		if (!(self :: $_instance instanceof self)) {
			self :: $_instance = new self();
		}
		return self :: $_instance;
	}


	/**
	 * Gets geo object from alpha-2 country code
	 * @param string alpha2 two-letter country code
	 * @return geo object
	 */
	public function getGeoByAlpha2($alpha2){
		$SQL = "SELECT woeid FROM " . self :: TABLECOUNTRIES . " WHERE alpha2=\"" . $alpha2 . "\"";	
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return false;
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return $this->getGeo($row['woeid']);
	}

	/**
	 * Gets geo object from alpha-3 country code
	 * @param string alpha3 three-letter country code
	 * @return geo object
	 */
	public function getGeoByAlpha3($alpha3){
		$SQL = "SELECT woeid FROM " . self :: TABLECOUNTRIES . " WHERE alpha3=\"" . $alpha3 . "\"";	
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return false;
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return $this->getGeo($row['woeid']);
	}

	/**
	* Gets all nodes without children
	* @return array
	*/
	public function getLeafNodes(){
		$SQL = "SELECT woeid from ".self::TABLEPLACES." WHERE woeid NOT IN (SELECT woeid FROM ".self::TABLECHILDREN.")";
		$result = $this->query($SQL);
		while ($row = $result->fetch_array(MYSQLI_ASSOC)){
			$res[] = $row['woeid'];
		}
		return $res;
	}

	/**
	* Is the woeid a leaf node (no children)
	* @param int woeid
	* @return array
	*/
	public function isLeafNode($woeid){
		$SQL = "SELECT woeid from ".self::TABLECHILDREN." WHERE woeid=".$woeid;
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return true;
		} else {
			return false;
		}
	}


	/**
	* Gets places with corresponding placename (exact match) 
	* Use only placenames ("Nortfield"), not contextual names ("Northfield, MN")
	* @param string q
	* @return array of woeids
	*/
	public function getByName($q) {
		$q = $this->escapeString($q);
		$SQL = "SELECT woeid FROM " . self :: TABLEPLACENAMES . " WHERE name=\"" . $q . "\"";
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return array ();
		}
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$aTemp[] = $row['woeid'];
		}
		return $aTemp;
	}

	/**
	* Gets places a specific type in a specific country 
	* @param int typecode
	* @param string two-letter country code
	* @return array of woeids
	*/
	public function getByTypeCountry($typeCode,$countryCode) {
		$SQL = "SELECT woeid FROM " . self :: TABLEPLACES . " WHERE placetype=".$typeCode." AND country=\"" . $countryCode . "\"";
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return array ();
		}
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$aTemp[] = $row['woeid'];
		}
		return $aTemp;
	}


	/**
	* Gets most probable woeid return for a placename/string query
	* @param string q 
	* @param int focus woeid focus
	* @return array
	*/
	public function disambiguate($q, $focus = false) {
		$q = trim($q);
		if (!$focus) {
			$focus = $this->defaultFocus; //use default focus if none provided
		}
		$woeid = $this->readDisambiguateCache($q, $focus); //check cache
		if ($woeid) {
			return $this->getGeo($woeid); //instantiate locally
		} else {
			$geo = $this->getService()->disambiguate($q, $focus); //call service
			if (!empty ($geo)) { //cache results
				$this->writeDisambiguateCache($q, $geo->getWoeid(), $focus);
			}
			return $geo;
		}
	}

	/** Gets type of a place
	 * @param int woeid woeid
	 * @param Bool stringName set to True if you want the string equiv.
	 * @return int|string placetype code (default) or placetype name
	 */
	public function getPlaceType($woeid, $stringName = false) {
		if ($stringName) {
			$select = "placetypename";
		} else {
			$select = "placetype";
		}
		$SQL = "SELECT " . $select . " FROM " . self::TABLEPLACES . " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return $row[$select];
	}

	/** Gets alternate names for places
	 * @param int woeid
	 * @return array
	 */
	public function getAliases($woeid){
		$SQL = "SELECT name, nametype, pref, lang FROM " . self::TABLEPLACENAMES . " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		$res = array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)){
			$temp = array(
				'name' => $row['name'],
				'lang' => $row['lang'],
				'pref' => (bool) $row['pref'],
				'nametype' => (int) $row['nametype']
			);
			$res[$row['name']] = $temp;
		}
		return $res;
	}



	/** Gets Placetype string (name) from placetype code
	 * @param int placeTypeCode
	 * @return string
	 */
	public function placeTypeLookup($placeTypeCode){
		$SQL = "SELECT name FROM " . self::TABLEPLACETYPES . " WHERE id=" . $placeTypeCode;
		$result = $this->query($SQL);
		if ($result->num_rows === 0){return false;}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return $row['name'];
	}

	/**
	 * Gets elevation from woeid
	 * @param int woeid
	 * @return int meters above sea level
	 * 
	 */
	public function getElevationbyWoeid($woeid) {
		return $this->getService()->getElevationbyWoeid($woeid);
	}

	/**
	 * Gets elevation from lat/lon
	 * @param real long Decimal Longitude
	 * @param real lat Decimal Latitude
	 * @return int meters above sea level
	 * 
	 */
	public function getElevationbyCoordinate($lon, $lat) {
		return $this->getService()->getElevationbyCoordinate($lon, $lat);
	}

	/**
	* Geocodes address string or placename
	* @param string q 
	* @return array
	*/
	public function geocode($q) {
		return $this->getService()->geocode($q);
	}

	/**
	* Geocodes placename using geographic bias
	* @param string q 
	* @param int woeid of focus (disambiguation is geographically biased towards the focus)
	* @return array
	*/
	public function geocodeWithFocus($q, $focus) {
		return $this->getService()->geocodeWithFocus($q, $focus);
	}

	/**
	 * Reverse geocodes long/lat to the smallest bounding WOEID
	 * @param real long Decimal Longitude
	 * @param real lat Decimal Latitude
	 * @return array single result
	 */
	public function reverseGeocode($lon, $lat) {
		return $this->getService()->reverseGeocode($lon, $lat);
	}

	/** 
	 * Gets geoservice object
	 * @return singleton
	 */
	public function getService() {
		if (!class_exists("geoservice")) {
			require_once ('class.geoservice.php');
		}
		return geoservice :: getInstance();
	}

	/**
	 * Gets bounding box of woeid
	 * Falls back to web service if not available locally
	 * Stores results of web service call locally
	 * @param int woeid woeid
	 */
	public function getBbox($woeid) {
		$geo = $this->getGeo($woeid);
		if ($geo) {
			return $geo->getBbox();
		} else {
			return false;
		}
	}

	/**
	 * Gets centroid of woeid
	 * Falls back to web service if not available locally
	 * Stores results of web service call locally
	 * @param int woeid woeid
	 */
	public function getCentroid($woeid) {
		$geo = $this->getGeo($woeid);
		if ($geo) {
			return $geo->getCentroid();
		} else {
			return false;
		}
	}

	/**
	 * Refreshes coordinate data in local store with that from web service
	 * Here (and not in geo class) in the event you might want to iterate through programatically
	 * @param int woeid woeid
	 * @return obj geo object with coordinates from web service
	 */
	public function getAndRefreshCoords($woeid) {
		$geo = $this->getGeoFromService($woeid);
		if (!$geo) {
			return false;
		}
		//extract centroid and bbox
		$centroid = $geo->getCentroid();
		$bbox = $geo->getBbox();
		//run update query
		$SQL = "UPDATE LOW_PRIORITY " . self :: TABLEPLACES . " SET ";
		$SQL .= "centroid_lat=" . $centroid['lat'] . ",";
		$SQL .= "centroid_lon=" . $centroid['lon'] . ",";
		$SQL .= "bbox_sw_lat=" . $bbox['sw_lat'] . ",";
		$SQL .= "bbox_sw_lon=" . $bbox['sw_lon'] . ",";
		$SQL .= "bbox_ne_lat=" . $bbox['ne_lat'] . ",";
		$SQL .= "bbox_ne_lon=" . $bbox['ne_lon'];
		$SQL .= " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error updating coords on WOEID " . $woeid);
			return false;
		}
		return $geo;
	}

	/**
	 * Gets gplplanet::geo object from web service
	 * Shortcut into web service object
	 * @param int woeid woeid
	 * @return obj geo object
	 */
	public function getGeoFromService($woeid) {
		$service = $this->getService();
		return $service->getGeo($woeid);
	}

	/**
	 * Gets gplplanet::geo object
	 * @param int woeid woeid
	 * @param bool fetch Fetch coords from webservice if not extant locally
	 * @return obj geo object
	 */
	public function getGeo($woeid, $fetch=false) {
		$SQL = "SELECT * FROM " . self :: TABLEPLACES . " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return false;
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		require_once ('class.geo.php');
		return new geo($row,$fetch);
	}

	/**
	 * Gets multiple gplplanet::geo object
	 * @param array array of woeids
	 * @return obj geo object
	 */
	public function getMultiGeo(array $woeid) {
		$res = array();
		$SQL = "SELECT * FROM " . self :: TABLEPLACES . " WHERE woeid IN (" . implode(",",$woeid).")";
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return $res;
		}
		require_once ('class.geo.php');
		while ($row = $result->fetch_array(MYSQLI_ASSOC)){
			$res[] = new geo($row);
		}
		return $res;
	}

	/**
	* Filters an array of woeids by one or more placetypes
	* Use in combination with descendants and ancestors to get the zips in a state, the state for a county, the county of a city, etc.
	* @param array woeid array of woeids
	* @param mixed type numeric place type (int or array of types)
	* @return array
	*/
	public function filterByType(array $woeid, $type) {
		if (count($woeid) === 0) {
			return array ();
		}
		if (!is_array($type)) { 
			$type = array (
				$type
			);
		} //convert to array for uniform handling
		$SQL = "SELECT woeid FROM " . self :: TABLEPLACES . " WHERE woeid IN (" . implode(",", $woeid) . ") AND placetype IN (" . implode(",", $type) . ")";
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return array ();
		}
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$aTemp[] = $row['woeid'];
		}
		return $aTemp;
	}

	/**
	* Get _all_ entities of a single placetype (from a specific country optionally)
	* @param int type placetype code
	* @param string country alpha-2 country code
	* @return array
	*/
	public function getByType($type, $country=null) {
		$SQL = "SELECT woeid FROM " . self :: TABLEPLACES . " WHERE placetype = ".$type;
		if ($country){
			$SQL .= " AND country=\"".$country."\"";
		}
		$result = $this->query($SQL);
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$aTemp[] = $row['woeid'];
		}
		return $aTemp;		
	}		

	/**
	 * Gets country of WOEID
	 * @param int woeid WOEID
	 * @return string two-letter country code
	 */
	 public function getCountry($woeid){
		$SQL = "SELECT country FROM " . self :: TABLEPLACES . " WHERE woeid = ".$woeid;
		$result = $this->query($SQL);
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return $row['country'];
	 }

	/**
	 * Gets default name of WOEID
	 * @param int woeid WOEID
	 * @return string two-letter country code
	 */
	 public function getName($woeid){
		$SQL = "SELECT name FROM " . self :: TABLEPLACES . " WHERE woeid = ".$woeid;
		$result = $this->query($SQL);
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return $row['name'];
	 }

	/**
	 * Converts array of WOEIDs to array of geo objects
	 * @param array $aWoeids array of woeids
	 * @return array geo objects
	 */
	public function getGeoArray($aWoeids) {
		if (!is_array($aWoeids)) {
			$aWoeids = array (
				$aWoeids
			);
		}
		$SQL = "SELECT * FROM " . self :: TABLEPLACES . " WHERE woeid IN (" . implode(",", $aWoeids) . ")";
		unset ($aWoeids);
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return array ();
		}
		require_once ('class.geo.php');
		$aPlaceObjects = array ();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$aPlaceObjects[] = new geo($row);
		}
		return $aPlaceObjects;
	}

	/**
	* Get adjacencies of a place
	* @param int woeid Where-On-Earth Identifier
	* @return array of woeids
	*/
	public function getAdjacencies($woeid) {
		$SQL = "SELECT adjacencies FROM " . self :: TABLEADJACENCIES . " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return array ();
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return explode(",", $row['adjacencies']);
	}

	/**
	* Get all children of a place
	* @param int woeid Where-On-Earth Identifier
	* @return array of woeids
	*/
	public function getChildren($woeid) {
		$SQL = "SELECT children FROM " . self :: TABLECHILDREN . " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return array ();
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return explode(",", $row['children']);
	}

	/**
	* Get all ancestors of a place
	* @param int woeid 
	* @return array of woeids
	*/
	public function getAncestors($woeid) {
		$SQL = "SELECT ancestors FROM " . self :: TABLEANCESTORS . " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return array ();
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return explode(",", $row['ancestors']);
	}

	/**
	* Get a parent of a place
	* @param int woeid Where-On-Earth Identifier
	* @return int
	*/
	public function getParent($woeid) {
		$SQL = "SELECT parent_id FROM " . self :: TABLEPARENTS . " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		if ($result->num_rows === 0) {
			return false;
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return $row['parent_id'];
	}

	/**
	 * Gets all places with same parent (of same placetype)
	 * In sympathy with Geoplanet web service, the resultset contains the submitted woeid
	* @param int woeid Where-On-Earth Identifier
	* @return array
	*/ 
	public function getSiblings($woeid,$placetype = null){
		if (!$placetype){
		//get placetype of woeid
			$SQL1 = "SELECT placetype FROM " . self :: TABLEPLACES . " WHERE woeid=".$woeid;
			$result1 = $this->query($SQL1);
			$row1 = $result1->fetch_array(MYSQLI_ASSOC);
			$placeType = $row1['placetype'];
		}
		//get parentID
		$parentID = $this->getParent($woeid);
		if (!$parentID) { //no parent check  (unlikely)
			return false;
		}
		//get all children for this parent
		$children = $this->getChildren($parentID);
		//check if only one result (no other siblings)
		if (count($children === 1)){
			return $children;
		}
		//filter by placetype and return
		return $this->filterByType($children, $placeType);
	}

	/**
	 * Gets all descendants (childrens children etc) for a given woeid
	 * @param int woeid
	 */
	public function getDescendants($woeid) {
		if (!$woeid) {
			$this->logMsg("No woeid passed to " . __METHOD__);
			return false;
		}
		$SQL = "SELECT descendants FROM " . self :: TABLEDESCENDANTS . " WHERE woeid=" . $woeid;
		$result = $this->query($SQL);
		if ($result->num_rows === 0 || $woeid === 1) { //no result / querying the earth
			return array ();
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return explode(",", $row['descendants']);
	}

	public function __construct() {
		//$this->applyConfig();
	}

	/**
	* Applies settings in configuration file to class vars
	* @return bool
	
	protected function applyConfig() {
		
	}
	*/
	
	/**
	* Connect to database
	* @param Bool force new connection
	* @return mixed
	
	protected function connectDB($new = false) {
		if ($new) {
			$db = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
		} else {
			$this->db = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
		}
		//check connection
		if (mysqli_connect_errno()) {
			echo "MySQL Connect to " . $this->dbHost . "/" . $this->dbName . " failed: %s\n", mysqli_connect_error();
			return false;
		} else {
			if ($new) {
				return $db;
			} else {
				return true;
			}
		}
	}
	*/
	
	/**
	* Query database
	* @param string SQL SQL statement
	* @return mysqli result object
	
	public function queryDB($SQL) {
		$err = "Error: ";
		if (!$this->db) {
			$this->connectDB();
		}
		$result = $this->db->query($SQL);
		if ($this->db->error) {
			$err = $err . $this->db->error . " (" . $SQL . ")";
			echo $err;
			$this->logMsg($err);
			return false;
		}

		return $result;
	}
	*/
	
	/**
	 * Queries database (wrapper)
	 * @return resultset
	 */
	public function query($SQL){
		return $this->getDB()->query($SQL);
	} 	

	/**
	 * Returns (mysqli) database object
	 * @return resultset
	 */
	public function getDB(){
		if ($this->db === null){
			require_once("class.db.php");
			if (!$this->db = db::getInstance()){
				throw new Exception(__METHOD__." database connection failed");
				return false;
			}
		}
		return $this->db;
	} 

	/**
	* Escapes strings for db insert (wrapper)
	* @param string $str 
	* @return string
	*/
	public function escapeString($str) {
		return $this->getDB()->escapeString($str);
	} 
 
	/**
	* Caches woeid return for a placename/string query
	* @param string q 
	* @param int focus woeid focus
	* @param int woeid resolved woeid
	* @return array
	*/
	protected function writeDisambiguateCache($q, $woeid, $focus) {
		$q = $this->escapeString($q);
		$SQL = "INSERT LOW_PRIORITY INTO " . self :: TABLEDISAMBIGUATE . " (q,woeid,focus) VALUES ";
		$SQL .= "(\"$q\",$woeid,$focus) ON DUPLICATE KEY UPDATE woeid=woeid"; //ignore in event dupe key is written
		if ($this->query($SQL)){
			return true;
		} else {
			return false;
		}
	}

	/**
	* Caches woeid return for a placename/string query
	* @param string q 
	* @param int focus woeid focus
	* @return array
	*/
	protected function readDisambiguateCache($q, $focus) {
		$SQL = "SELECT woeid FROM " . self :: TABLEDISAMBIGUATE . " WHERE q=\"" . $q . "\" AND focus=" . $focus;
		$result = $this->query($SQL);
		if ($result->num_rows == 1) {
			$row = $result->fetch_array(MYSQLI_ASSOC);
			return $row['woeid'];
		} else {
			return false;
		}
	}

	/**
	* Logs messages (very simple)
	* @param string $msg 
	* @return Bool
	*/
	public function logMsg($msg) {
		$date = date("m/d/y h:i:s");
		if ($this->logFile) {
			$fp = fopen($this->logFile, 'a');
			fwrite($fp, $date . "\t" . $msg . "\n");
			fclose($fp);
		}
		return true;
	}

}