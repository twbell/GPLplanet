<?php

/**
 * Methods for importing Yahoo GeoPlanet Data. Run import.php file via cmdln to import.
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009,2010 - Tyler Bell
 * @license GNU General Public License
 */ 

require_once ('../class.geoengine.php');
class geoimport extends geoengine {

	//tablenames (for import process only)
	const RAWPLACES = "raw_places";
	const RAWALIASES = "raw_aliases";
	const RAWADJACENCIES = "raw_adjacencies";
	const TEMPTABLEDESC = "temp_descendants";


	//=============== METHODS ==================

	/**
	* Populate Ancestors
	* @return Bool
	*/
	public function populateAncestors() {
		echo "Populating ancestors...";
		$SQL1 = "SELECT woeid FROM ".self::TABLEPLACES. " WHERE woeid NOT IN (SELECT woeid FROM ".self::TABLEANCESTORS.") AND woeid != 1";  //earth is an orphan
		$result1 = $this->queryDB($SQL1,true);
		echo "found ".$result1->num_rows." unprocessed ancestors; processing..."; 
		while ($row1 = $result1->fetch_array(MYSQLI_ASSOC)) {
			$temp = $row1['woeid'];
			$aParents = array();					//initialize
			while ($p = $this->getParent($temp)){  	//iterate through parents
				$ancestors = $this->getAncestors($p); //ancestors for this parent already calculated? Return.
				if (!empty($ancestors)){
					$aParents = array_merge($aParents,$ancestors);
					$aParents = array_unique($aParents);
					break;
				} else {
					if (in_array($p,$aParents)){ 	//check for recursiveness, just in case
						$this->logMsg (__METHOD__ . " Recursive Error ".$p." is ancestor of itself\n"); 
						exit;
					}
					$aParents[] = $p;				//add to array
					$temp = $p;			
					unset($p);
				}
			}	
			if (count($aParents) == 0){continue;}
			$SQL2  = "INSERT INTO ".self::TABLEANCESTORS." (woeid, ancestors) VALUES (" . $row1['woeid'] . ",\"" . implode(",", $aParents) . "\")";
			$result2 = $this->queryDB($SQL2);
			unset($aParents);	
		}
		echo " complete\n";
		return true;
	}

	/**
	* Populate Sibling Places -- those of same type having same parent
	* Processes only those not processed
	* @return Bool
	*/
	public function populateSiblings() {
		echo "Populating siblings...";
		$SQL1 = "SELECT woeid, placetype FROM ". self::TABLEPLACES." WHERE woeid NOT IN (SELECT woeid FROM ".self::TABLESIBLINGS.")";
		$result1 = $this->queryDB($SQL1,true);
		echo "Found ".$result1->num_rows." unprocessed siblings; processing..."; 
		while ($row1 = $result1->fetch_array(MYSQLI_ASSOC)) {
			$parentID =  $this->getParent($row1['woeid']);
			if (!$parentID){continue;}
			//get children of parent
			$aChildren = $this->getChildren($parentID);
			if (empty($aChildren)){continue;}
			//get those entities of only that type
			$SQL4 = "SELECT woeid FROM ".self::TABLEPLACES." WHERE woeid IN (".implode(",",$aChildren).") AND placetype=".$row1['placetype']." AND woeid !=".$row1['woeid'];
			$result4 = $this->queryDB($SQL4);
			if ($result4->num_rows === 0){
				continue;
			}
			//process and insert siblings
			while ($row4 = $result4->fetch_array(MYSQLI_ASSOC)) {
				$aSiblings[] = $row4['woeid'];
			}
			$sSiblings = implode(",",$aSiblings);
			unset($aSiblings);
			$SQL5 = "INSERT INTO ".self::TABLESIBLINGS." (woeid, siblings) VALUES (".$row1['woeid'].",\"".$sSiblings."\")"; 
			$result5 = $this->queryDB($SQL5);	
		}
		echo " complete\n";
		return true;
	}

	/**
	* Populate Descendants
	* This process is slightly unusual in that inserts occur in the get() rather than populate() method. 
	* The idea is to ensure that descendants are written even when assessed as part of a recursive calculation
	* @return Bool
	*/
	public function populateDescendants() {
		echo "Populating descendants... ";
		$SQL1 = "SELECT woeid FROM ".self::TABLEPLACES;
		$SQL1 .= " WHERE placetype NOT IN (19,29) AND woeid != 0";						//filter by type and do not need earth
		$SQL1 .= " AND woeid IN (SELECT parent from ".self::RAWPLACES.")";				//parents only
		$SQL1 .= " AND woeid NOT IN (SELECT woeid FROM ".self::TABLEDESCENDANTS.")";    //as-yet unprocessed
		$result1 = $this->queryDB($SQL1);
		echo $result1->num_rows." unprocessed; processing... ";
		while ($row1 = $result1->fetch_array(MYSQLI_ASSOC)) {
			$this->getDescendants($row1['woeid']);										//this calculates _and_ writes to table 	
		}
		echo " complete\n";
		return true;
	}

	/**
	* Insert Descendants into Descendants cache
	* Ignores if already cached
	* @param int woeid woeid
	* @param array aDescentants descendants
	* @return Bool
	*/
	protected function insertDescendants($woeid,$aDescendants){
	if (empty($aDescendants)){
		echo "woeid ".$woeid." attempted write with no descendants\n";
		return false;
	}
	$SQL  = "INSERT INTO ".self::TABLEDESCENDANTS." (woeid, descendants) VALUES (" . $woeid . ",\"" . implode(",", $aDescendants) . "\") ON DUPLICATE KEY UPDATE woeid=woeid";					
	$result = $this->queryDB($SQL);
	if (!$result) {
			$this->logMsg(__METHOD__."error on woeid " . $woeid);
			return false;
		} else {
			return true;
		}
	}
	
	/**
	* Recursively gets all children of a place to create optimized descendants table, and write descendants to table
	* Uses an intermediate table while iterating; enure enough space in the MySQL temp drive
	* @param int woeid
	* @return array
	*/
	public function getDescendants($woeid){
		//first check to see whether this has been computed and cached already; if so, use it
		$desc = parent::getDescendants($woeid);
		if ($desc){
			return $desc;
		}
		unset($desc);
		//create temp table if not exist to accommodate big iterative queries and not kill memory
		if (!$this->createTempDescTable()){
			$this->logMsg(__METHOD__ ."error creating temporary table");
			return false;
		}
		//get children of woeid		
		$SQL = "SELECT children FROM ".self::TABLECHILDREN." WHERE woeid=".$woeid;
		$result = $this->queryDB($SQL);
		if (!$result) {
			$this->logMsg(__METHOD__."error on woeid " . $woeid);
			return false;
		}		
		if ($result->num_rows === 0){return array();}
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$aChildren = explode(",",$row['children']);
		//iterate and recurse
		foreach ($aChildren as $child){
			if ($child){	
				if ($child === 1){											//skip the world; all woeids are its descendants
					continue;
				}	
				//merge child with its descendents						
				$tempDesc = $this->getDescendants($child);
				$tempDesc[] = $child;
				//add child and its descendants into temp table as interim step (avoids working with massive, mem-hogging arrays)
				$SQL1 = "INSERT INTO ".self::TEMPTABLEDESC." (woeid,descendant) VALUES ";
				foreach ($tempDesc as $desc){
					$SQL1Array[] = "(".$woeid.",".$desc.")";
				}
				$SQL1 .= implode(",",$SQL1Array);
				$result1 = $this->queryDB($SQL1);
				if (!$result1) {
					$this->logMsg(__METHOD__."error inserting descendants into temp table on woeid " . $woeid);
					return false;
				}	
			} 
		}
		//all descendants are now in a big, vertical temp table; extract, write, delete from temp table, and return
		$SQL2 = "SELECT DISTINCT descendant FROM ".self::TEMPTABLEDESC." WHERE woeid=".$woeid;
		$result2 = $this->queryDB($SQL2);
		if (!$result2) {
			$this->logMsg(__METHOD__."error obtaining descendants from temp table for woeid " . $woeid);
			return false;
		}
		if ($result2->num_rows === 0){return array();}
		//cache descendant (write to table)
		while ($row2 = $result2->fetch_array(MYSQLI_ASSOC)) {   				//create array of children for serializing
			$tempArray[] = $row2['descendant'];
		} 	
		$this->insertDescendants($woeid,$tempArray);
		//delete records from temp table
		$SQL3 = "DELETE FROM ".self::TEMPTABLEDESC." WHERE woeid=".$woeid;
		$result3 = $this->queryDB($SQL3);
		//return
		return $tempArray;
	}	

	/**
	* Create Temp Table for processing progress of descendants processing
	* @param string name table name being populated
	* @return Bool
	*/		
	protected function createTempDescTable(){
		$SQL = "CREATE TEMPORARY TABLE IF NOT EXISTS `".$this->dbName."`.`".self::TEMPTABLEDESC."` (
			  `woeid` int(10) unsigned NOT NULL,
			  `descendant` int(10) unsigned NOT NULL,
			  KEY `woeid_idx` (`woeid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Temporary Descendants lookup'";
		$result = $this->queryDB($SQL);	
		return true;	
	}
	
	
	/**
	* Create Temp Table for processing progress of time-intensive scripts
	* @param string name table name being populated
	* @return Bool
	*/	
	protected function createTrackerTable($name){;		
		$SQL = "CREATE TABLE IF NOT EXISTS `".$this->dbName."`.`temp_".$name."` (
				  `woeid` INT UNSIGNED NOT NULL,
				  `timestamp` TIMESTAMP,
				  PRIMARY KEY (`woeid`)
				)
				ENGINE = MyISAM
				COMMENT = 'Temporary Table for tracking progress population'";
		$result = $this->queryDB($SQL);			
	}


	/**
	* Removes Temp Table
	* @param string $name Table Name
	* @return Bool
	*/	
	protected function dropTrackerTable($name){
		$SQL = "DROP TABLE IF EXISTS tracker_".$name;
		$result = $this->queryDB($SQL);
		return true;
	}

	/**
	* Adds woeid into tracker table
	* @param int woeid
	* @param string name Name of tracker table
	* @return Bool
	*/	
	protected function addTracker($woeid,$name){
		$SQL = "INSERT INTO tracker_".$name." (woeid) VALUES (".$woeid.")";
		$result = $this->queryDB($SQL);
		return true;
	}
	

	
	/**
	* Populate Child Places
	* @return Bool
	*/
	public function populateChildren() {
		echo "Populating children...";
		$SQL1 = "SELECT DISTINCT parent FROM ".self::RAWPLACES." WHERE parent > 0 AND parent NOT IN (SELECT woeid FROM ".self::TABLECHILDREN.")";	//earth has no parent
		$SQL2 = "SELECT woeid FROM " . self :: RAWPLACES . " WHERE parent="; 		//get children for each place
		$SQL3 = "INSERT INTO " . self::TABLECHILDREN . "(woeid,children) VALUES "; 	//insert bambini for each
		$result1 = $this->queryDB($SQL1);
		echo " found ".$result1->num_rows." unprocessed places with children; processing...";
		while ($row1 = $result1->fetch_array(MYSQLI_ASSOC)) {
			$result2 = $this->queryDB($SQL2 . $row1['parent']);
			while ($row2 = $result2->fetch_array(MYSQLI_ASSOC)) {   				//create array of children for serializing
				$tempArray[] = $row2['woeid'];
			}
			$result3 = $this->queryDB($SQL3 . "(" . $row1['parent'] . ",\"" . implode(",", $tempArray) . "\")"); //insert child record
			unset($tempArray);
		}
		echo " complete\n";
		return true;
	}

	/**
	* Populate Parent Lookup
	* @return Bool
	*/
	public function populateParents() {
		echo "Populating parents...";
		$this->disableKeys(self :: TABLEPARENTS);
		$SQL = "INSERT INTO " . self::TABLEPARENTS . " (woeid,parent_id)
				SELECT woeid, parent 
				FROM  " . self :: RAWPLACES;
		if ($this->queryDB($SQL)) {
			$this->enableKeys(self :: TABLEPARENTS);											
			echo " complete\n";
			return true;
		} else {
			return false;
		}
	}

	/**
	* Populate Adjacent Places Table
	* @return Bool
	*/
	public function populateAdjacencies() {
		echo "Populating adjacencies...";
		$SQL1 = "SELECT woeid FROM ".self :: TABLEPLACES;
		$SQL2 = "SELECT neighbor FROM " . self::RAWADJACENCIES . " WHERE woeid="; //get adjacencies for once place at time
		$SQL3 = "INSERT INTO " . self::TABLEADJACENCIES . "(woeid,adjacencies) VALUES "; //insert
		$result1 = $this->queryDB($SQL1);
		$this->disableKeys(self :: TABLEADJACENCIES);
		while ($row1 = $result1->fetch_array(MYSQLI_ASSOC)) {
			$result2 = $this->queryDB($SQL2 . $row1['woeid']);
			if ($result2->num_rows > 0) {
				while ($row2 = $result2->fetch_array(MYSQLI_ASSOC)) { //create array of neighbors for insertion
					$tempArray[] = $row2['neighbor'];
				}
				$result3 = $this->queryDB($SQL3 . "(" . $row1['woeid'] . ",\"" . implode(",", $tempArray) . "\")"); //insert adjacent record
				unset($tempArray);
			}
		}
		$this->enableKeys(self :: TABLEADJACENCIES);
		echo " complete\n";
		return true;
	}
	
	/**
	 * Populates Placenames
	 * @return array
	 */
	public function populatePlaceNames() {
		$this->populatePreferredNames(); 				//Populate preferred placenames from Places table
		$this->populateNonPreferredNames(); 			//Populate alternative aliases from Aliases table
		$this->typePlaceNames();						//add numeric placetypes to aliases for efficiency
		return true;
	}
	
	/**
	 * Populates Preferred Placenames from Places
	 * @return Bool
	 */
	protected function populatePreferredNames() {
		echo "Populating preferred placenames...";
		$this->disableKeys(self :: TABLEPLACENAMES);											
		$SQL = "INSERT INTO " . self::TABLEPLACENAMES . "(woeid,pref,name,nametype)
				SELECT woeid, 1, name, NULL
				FROM " . self :: TABLEPLACES;
		if ($this->queryDB($SQL)) {
			$this->enableKeys(self :: TABLEPLACENAMES);											
			echo " complete\n";
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Populates non-preferred Placenames from aliases
	 * @return Bool
	 */
	protected function populateNonPreferredNames() {
		echo "Populating alternative placenames...";
		$this->disableKeys(self :: TABLEPLACENAMES);		
		$SQL = "INSERT INTO " . self::TABLEPLACENAMES . "(woeid,pref,name,nametype,lang)
			SELECT " . self :: RAWALIASES . ".woeid, 0, " . self :: RAWALIASES . ".name, " . self :: RAWALIASES . ".nametype,". self :: RAWALIASES . ".lang
			FROM " . self :: RAWALIASES . "," . self :: TABLEPLACES . "
			WHERE " . self :: RAWALIASES . ".woeid=" . self :: TABLEPLACES . ".woeid";
		if ($this->queryDB($SQL)) {
			$this->enableKeys(self :: TABLEPLACENAMES);											
			echo " complete\n";
			return true;
		} else {
			return false;
		}
	}
	
	/**
	* Adds numeric placetype codes to populated placenames table
	* @return Bool
	*/
	protected function typePlaceNames() {
		echo "Updating placenames table with placetype codes...";
		$SQL = "UPDATE " . self :: TABLEPLACENAMES . "," . self :: TABLEPLACES . "
				SET " . self :: TABLEPLACENAMES . ".placetype=" . self :: TABLEPLACES . ".placetype 
				WHERE " . self :: TABLEPLACENAMES . ".woeid=" . self :: TABLEPLACES . ".woeid";
		if ($this->queryDB($SQL)) {											
			echo " complete\n";
			return true;
		} else {
			return false;
		}
	}	
	
	/**
	* Disables table keys for bulk loading
	* @param string tableName table name
	* @return Bool
	*/
	protected function disableKeys($tableName) {
		$SQL = "ALTER TABLE " . $tableName . " DISABLE KEYS";
		return $this->queryDB($SQL);
	}

	/**
	* Enables table keys
	* @param string tableName table name
	* @return Bool
	*/
	protected function enableKeys($tableName) {
		$SQL = "ALTER TABLE " . $tableName . " ENABLE KEYS";
		echo " building keys...";
		return $this->queryDB($SQL);
	}

	/**
	* Populate optimized places from Geoplanet raw data
	* @return Bool
	*/
	public function populatePlaces() {
		echo "Populating places...";	
		$this->disableKeys(self :: TABLEPLACES);											//Disable keys
		//Optimise places
		$SQL = "INSERT INTO " . self :: TABLEPLACES . " (woeid,name,placetypename,country)
					SELECT woeid, name, placetype, iso 
					FROM  " . self :: RAWPLACES . " 
					WHERE placetype != \"sport\""; //sportsteams not included
		if ($this->queryDB($SQL)) {
			//Add place type codes
			$SQL = "UPDATE " . self :: TABLEPLACES . "," . self :: TABLEPLACETYPES . "
					 SET " . self :: TABLEPLACES . ".placetype=" . self :: TABLEPLACETYPES . ".id 
					 WHERE " . self :: TABLEPLACES . ".placetypename=" . self :: TABLEPLACETYPES . ".shortname";
			if ($this->queryDB($SQL)) {
				//rebuild keys
				$this->enableKeys(self :: TABLEPLACES);
				echo " complete\n";
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	* Populates raw adjacencies table with file contents
	* @param string $file File to import
	* @return Bool
	*/
	public function importAdjacencies($file) {
		echo "Importing adjacencies data from " . $file . "...";
		$SQL = "LOAD DATA INFILE '" . $file . "'
						INTO TABLE " . self :: RAWADJACENCIES . "
						FIELDS TERMINATED BY '\t'  ENCLOSED BY '\"' 
						IGNORE 1 LINES";
		if ($this->queryDB($SQL)) {
			echo " complete\n";
			return true;
		} else {
			return false;
		}
	}

	/**
	* Populates raw aliases table with file contents
	* @param string $file File to import
	* @return Bool
	*/
	public function importAliases($file) {
		echo "Importing alias data from " . $file . "...";
		$SQL = "LOAD DATA INFILE '" . $file . "' 
						INTO TABLE " . self :: RAWALIASES . "
						FIELDS TERMINATED BY '\t'  ENCLOSED BY '\"'
						IGNORE 1 LINES";
		if ($this->queryDB($SQL)) {
			echo " complete\n";
			return true;
		} else {
			return false;
		}
	}

	/**
	* Populates raw places table with file contents
	* @param string $file File to import
	* @return Bool
	*/
	public function importPlaces($file) {
		echo "Importing place data from " . $file . "...";
		$SQL = "LOAD DATA INFILE '" . $file . "' 
						INTO TABLE " . self :: RAWPLACES . "
						FIELDS TERMINATED BY '\t'  ENCLOSED BY '\"'
						IGNORE 1 LINES";
		if ($this->queryDB($SQL)) {
			echo " complete\n";
			return true;
		} else {
			echo " failed\n";
			return false;
		}
	}

}