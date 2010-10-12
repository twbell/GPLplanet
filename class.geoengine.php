<?php

/**
 * Core methods for Accessing Yahoo Geoplanet local instance + Webservice
 * Run import.php file via cmdln to import geoplanet data in advance of use
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2010 - Tyler Bell
 * @todo support geoplanet belongTos()
 * @license GNU General Public License
 */

class geoengine {

	//config files and paths
	const CONFIGFILE = 'config.ini'; //script and db configuration
	public $logFile = ''; //set by config file; set to null in config if logging not required

	//database connection, vars imported at runtime from config file
	protected $dbHost;
	protected $dbUser;
	protected $dbPassword;
	protected $dbName;
	protected $db; //db object created and retained on connection

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

	//misc
	protected static $_instance; //singleton management
	public $defaultFocus = 1; //woeid of default focus geography

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
	* Gets places with corresponding placename (exact match) 
	* Use only placenames ("Nortfield"), not contextual names ("Northfield, MN")
	* @param string q
	* @return array of woeids
	*/
	public function getByName($q) {
		$q = $this->escapeString($q);
		$SQL = "SELECT woeid FROM " . self :: TABLEPLACENAMES . " WHERE name=\"" . $q . "\"";
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error searching on placename " . $q);
			return false;
		}
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
		$result = $this->queryDB($SQL);
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
	 * @return obj geo object
	 */
	public function getGeo($woeid) {
		$SQL = "SELECT * FROM " . self :: TABLEPLACES . " WHERE woeid=" . $woeid;
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error on WOEID " . $woeid);
			return false;
		}
		if ($result->num_rows === 0) {
			return false;
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		require_once ('class.geo.php');
		return new geo($row);
	}

	/**
	* Filters an array of woeids by one or more placetypes
	* Use in combination with descendants and ancestors to get the zips in a state, the state for a county, the county of a city, etc.
	* @param array woeid array of woeids
	* @param int type numeric place type (int) or an array thereof
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
		$result = $this->queryDB($SQL);
		if ($result->num_rows === 0) {
			return array ();
		}
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$aTemp[] = $row['woeid'];
		}
		return $aTemp;
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
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error");
			return false;
		}
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
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error on WOEID " . $woeid);
			return false;
		}
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
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error on WOEID " . $woeid);
			return false;
		}
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
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error on WOEID " . $woeid);
			return false;
		}
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
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " Error on WOEID " . $woeid);
			return false;
		}
		if ($result->num_rows === 0) {
			return false;
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return $row['parent_id'];
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
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error on WOEID " . $woeid);
			return false;
		}
		if ($result->num_rows === 0 || $woeid === 1) { //no result / querying the earth
			return array ();
		}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		return explode(",", $row['descendants']);
	}

	public function __construct() {
		$this->applyConfig();
	}

	/**
	* Applies settings in configuration file to class vars
	* @return bool
	*/
	protected function applyConfig() {
		$thisDir = dirname(__FILE__);
		$configFile = $thisDir."/".self :: CONFIGFILE;		//config file assumed to be in same directory as this file
		if (is_readable($configFile)) {
			$aConfigs = parse_ini_file($configFile);
			if (!empty ($aConfigs)) {
				foreach ($aConfigs as $key => $value) {
					$this-> $key = $value;
				}
			}
			return true;
		} else {
			if (is_file($configFile)){
				$msg = "unreadable config file " . $configFile . "; check file permissions\n";	
			} else {
				$msg = "missing config file " . $configFile . "\n";	
			}
			echo $msg;
			exit;		//can't log or operate without configs, so bail
		}
	}

	/**
	* Connect to database
	* @param Bool force new connection
	* @return mixed
	*/
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

	/**
	* Query database
	* @param string SQL SQL statement
	* @return mysqli result object
	*/
	protected function queryDB($SQL) {
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
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error writing disambiguate cache " . $woeid);
			return false;
		}
		return true;
	}

	/**
	* Caches woeid return for a placename/string query
	* @param string q 
	* @param int focus woeid focus
	* @return array
	*/
	protected function readDisambiguateCache($q, $focus) {
		$SQL = "SELECT woeid FROM " . self :: TABLEDISAMBIGUATE . " WHERE q=\"" . $q . "\" AND focus=" . $focus;
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__ . " error reading disambiguate cache for place " . $q);
			return false;
		}
		if ($result->num_rows == 1) {
			$row = $result->fetch_array(MYSQLI_ASSOC);
			return $row['woeid'];
		} else {
			return false;
		}
	}

	/**
	* Escapes strings for insert
	* @param string $str 
	* @return string
	*/
	public function escapeString($str) {
		if (!$this->db) {
			$this->connectDB();
		}
		return $this->db->real_escape_string($str);
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