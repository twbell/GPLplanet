<?php


/**
* geo - Named place object, the instatiation of a woeid
* @package gplplanet
* @author Tyler Bell tylerwbell[at]gmail[dot]com
* @copyright 2009-2011 - Tyler Bell
* @license GNU General Public License
*/

class geo {

	//properties
	private $woeid;
	private $name;
	private $contextName;
	private $placeTypeName;
	private $placeType;
	private $centroid;
	private $bBox;
	private $country;

	//object cache
	private $aliases;
	private $children;
	private $siblings;
	private $parent;
	private $belongTo;
	private $consistOf;
	private $adjacencies;

	//===================================
	/**
	* Constructor
	* @param array row assoc. array from places table
	* @param obj geo Geo object
	* @return obj Place Object
	*/
	function __construct($row,$fetch=false) {
		$this->woeid = (int) $row['woeid'];
		$this->name = $row['name'];
		$this->contextName = $row['contextname'];
		$this->placeTypeName = $row['placetypename'];
		$this->placeType = (int) $row['placetype'];
		$this->country = $row['country'];
		//fetch coords from webservice on instantiation if not exist
		if ($fetch && is_null($row['centroid_lon'])){
			if (!$serviceGeo = $this->getEngine()->getAndRefreshCoords($this->woeid)){ //updates database with coords from web service
				return false;
			}
			$this->updateInstanceCoords($serviceGeo); //update class properties with new coords
		} else {
			//null if no extant coords, cast as float otherwise
			if (is_null($this->centroid['lon']) && is_null($row['bbox_ne_lon'])){
				$this->bBox['ne_lat'] = null;
				$this->bBox['ne_lon'] = null;
				$this->bBox['sw_lat'] = null;
				$this->bBox['sw_lon'] = null;
				$this->centroid['lon'] = null;
				$this->centroid['lat'] = null;
			} else {
				$this->bBox['ne_lat'] = (float) $row['bbox_ne_lat'];
				$this->bBox['ne_lon'] = (float) $row['bbox_ne_lon'];
				$this->bBox['sw_lat'] = (float) $row['bbox_sw_lat'];
				$this->bBox['sw_lon'] = (float) $row['bbox_sw_lon'];
				$this->centroid['lon'] = (float) $row['centroid_lon'];
				$this->centroid['lat'] = (float) $row['centroid_lat'];			
			}
		}
	}

	/** 
	 * Get clean representation (not really an instance) for JSON representation and similar
	 * @param bool fetch get coordinates from web service if not exist
	* @return array
	*/
	public function getCleanInstance($fetch=false){
		$geo = array();
		$geo['woeid'] = $this->woeid;
		$geo['name'] = $this->name;
		$geo['placetypename'] = $this->placeTypeName;
		$geo['placetype'] = $this->placeType;
		$geo['aliases'] = $this->getAliases();
		//populate coords if not exist
		if ($fetch && is_null($this->centroid['lon'])){
			if (!$serviceGeo = $this->getEngine()->getAndRefreshCoords($this->woeid)){ //updates database with coords from web service
				return false;
			}
			$this->updateInstanceCoords($serviceGeo); //update class properties with new coords
		}
		$geo['bbox']['ne_lat'] = $this->bBox['ne_lat'];
		$geo['bbox']['ne_lon'] = $this->bBox['ne_lon'];
		$geo['bbox']['sw_lat'] = $this->bBox['sw_lat'];
		$geo['bbox']['sw_lon'] = $this->bBox['sw_lon']; 
		$geo['centroid']['lon'] = $this->centroid['lon'];
		$geo['centroid']['lat'] = $this->centroid['lat'];
		
		return $geo;
	}


	/** Get Bounding Box
	* @return array
	*/
	public function getBbox() {
		if (!is_null($this->bBox['sw_lon'])) {
			return $this->bBox;
		} else {
			$geo = $this->getEngine()->getAndRefreshCoords($this->woeid); //updates database with coords from web service
			if (!$geo) {
				return false;
			}
			$this->updateInstanceCoords($geo); //update class properties with new coords
		}
		return $this->bBox;
	}

	/** Get coordinates of centroid
	* @return array
	*/
	public function getCentroid() {
		if (!is_null($this->centroid['lon'])) {
			return $this->centroid;
		} else {
			$geo = $this->getEngine()->getAndRefreshCoords($this->woeid); //updates database with coords from web service
			if (!$geo) {
				return false;
			}
			$this->updateInstanceCoords($geo); //update class properties with new coords
		}
		return $this->centroid;
	}

	/**
	* Updates coordinates of this instance with coordinates from another geo object
	* @return bool
	*/
	protected function updateInstanceCoords($geo) {
		$centroid = $geo->getCentroid();
		$bBox = $geo->getBbox();
		unset ($geo);
		//assign coordinates to this instance of object
		$this->centroid['lon'] = $centroid['lon'];
		$this->centroid['lat'] = $centroid['lat'];
		$this->bBox['ne_lat'] = $bBox['ne_lat'];
		$this->bBox['ne_lon'] = $bBox['ne_lon'];
		$this->bBox['sw_lat'] = $bBox['sw_lat'];
		$this->bBox['sw_lon'] = $bBox['sw_lon'];
		return true;
	}

	/** Get woeid
	* @return int woeid
	*/
	public function getWoeid() {
		return $this->woeid;
	}

	/** Get placename
	* @return string placename
	*/
	public function getName() {
		return $this->name;
	}

	/** Get placename with geographic context or qualifier
	* Falls back to normal name if not context name available
	* @param Bool nameType return string label of place type
	* @return string
	*/
	public function getContextName() {
		if ($this->contextName) {
			return $this->contextName;
		} else {
			return $this->name;
		}
	}

	/**
	 * Gets longitude
	 * @return real
	 */
	public function getLon(){
		$coords = $this->getCentroid();
		return $coords['lon'];
	}

	/**
	 * Gets latitude
	 * @return real
	 */
	public function getLon(){
		$coords = $this->getCentroid();
		return $coords['lat'];
	}

	/** Get type of this place
	* @param Bool nameType return string label instead of place code
	* @return mixed interger or string placetype as requested
	*/
	public function getPlaceType($nameType = false) {
		if ($nameType) {
			return $this->placeTypeName;
		} else {
			return $this->placeType;
		}
	}

	/** Get children of this place
	* @return array Array of woeids
	*/
	public function getChildren() {
		if ($this->children) {
			return $this->children;
		}
		$this->children = $this->getEngine()->getChildren($this->woeid);
		return $this->children;
	}

	/**
	* Is this a leaf node (no children)
	* @param int woeid
	* @return array
	*/
	public function isLeafNode(){
		return $this->getEngine()->isLeafNode($this->woeid);
	}

	/**
	* Gets geo engine singleton
	* @return obj geo engine object
	*/
	public function getEngine() {
		if (!class_exists("geoengine")) {
			require_once ('class.geoengine.php');
		}
		return geoengine :: getInstance();
	}

	/** Get siblings of this place (places with same parent of same type)
	* @return array Array of woeids
	*/
	public function getSiblings() {
		if ($this->siblings) {
			return $this->siblings;
		}
		$this->siblings = $this->getEngine()->getSiblings($this->woeid);
		return $this->siblings;
	}

	/** Get adjacencies (neighbors) of this place
	* @return array Array of woeids
	*/
	public function getAdjacencies() {
		if ($this->adjacencies) {
			return $this->adjacencies;
		}
		$this->adjacencies = $this->getEngine()->getAdjacencies($this->woeid);
		return $this->adjacencies;
	}

	/** Get alternative names for this place
	* @return array Array of placenames
	*/
	public function getAliases() {
		if ($this->aliases) {
			return $this->aliases;
		}
		$this->aliases = $this->getEngine()->getAliases($this->woeid);
		return $this->aliases;
	}

}
?>
