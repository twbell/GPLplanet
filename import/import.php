<?php
/**
 * Instructions:
 * (0) 	Configure PHP:
 * 			- set php.ini memory_limit to 1.5GB for handling large import arrays (memory_limit = 1500M)
 * 		Configure MySQL:
 * 			- MySQL max_allowed_packet = 50M (or greater)
 * (1) Configure database connection vars in config.ini
 * (2) Download geoplanet data from http://developer.yahoo.com/geo/geoplanet/data/
 * (3) Add tsv file loations to the import/files.ini
 * (4) cd to this dir and run this script from the command line: "php import.php"
 * 
 * Temp files are created in your system's (wait for it) temp directory, so ensure you have about 50GB 
 * avilable to be safe.
 * 
 * The import script takes a while to run as it builds indicies and pre-caches relationships.  Nearly all 
 * long-running scripts have been designed to pick-up where they left off, if interrupted .  Populating 
 * Descendants can take three days on a lower-end laptop -- query "select count(woeid) FROM geo_descendants"
 * to view progress that will not always be apparent on progress bar.
 * 
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009-2011 - Tyler Bell
 * @license GNU General Public License
 */

//runtime error reporting level
error_reporting(E_ERROR); 		
//error_reporting(E_ALL); 	

//Get file locations from config
$files = parse_ini_file('files.ini');

//==================== Usually no need to edit below this line =================
set_time_limit(0);		  		//no timeout (always the case with CLI tho)
require_once ('class.geoimport.php');
$importEngine = new geoimport; 	//uses db name from config file. Override by assigning var $importEngine->dbName = your_new_database_name
$importProgress = "import";		//table name for tracking import progress					

//check config file
$thisDir = dirname(__FILE__);
$configFile = $thisDir."/../config.ini";
if (is_readable($configFile)) {
	$cfg = parse_ini_file($configFile);
} else {
	//can't operate without db configs, so barf and bail
	if (is_file($configFile)){
		$errMsg = "unreadable config file " . $configFile . "; check file permissions\n";	
	} else {
		$errMsg = "missing config file " . $configFile . "\n";	
	}
	echo $errMsg;
	exit;
}

$bail = false;
if (!$cfg['host']){
$errMsg = "No host provided in config file " . $configFile . "\n";	
	$bail = true;
}
if (!$cfg['username']){
$errMsg = "No username provided in config file " . $configFile . "\n";	
	$bail = true;
}		
if (!$cfg['database']){
$errMsg = "No database name provided in config file " . $configFile . "\n";	
	$bail = true;
}			
if ($bail){
	exit;
}

//check files
foreach ($files as $file){
	if (!is_readable($file)){
		echo "Cannot read file ".$file." Please set file location in files.ini\n";
		exit;
	}
}

echo "\nxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\n";
echo "Import Files Verified\n";
//check db exists
if (in_array($cfg['database'],$importEngine->listDatbases())){
	echo "Database ".$cfg['database']." exists\n";
} else {
	//create database
	echo "Creating Data Structure for database '".$cfg['database']."'. Tables:\n";
	if (!$importEngine->createDatabase()){
		echo "Creating database falied\n";
		exit;
	}
	//pause (workaround for multi-query returning prematurely)
	sleep(5);
	//show tables
	$tables = $importEngine->showTables();
	foreach ($tables as $table){
		echo "\t-".$table."\n";
	}
}

//create table to track import progress (if not exist)
if (!$importEngine->createTrackerTable($importProgress)){exit;}
//get last stage of import completed
$lastStage = $importEngine->getMaxTracker($importProgress);
//cleaning old files
echo "Removing old temp files...\n";
$importEngine->cleanTempFilesDesc();
//import files
echo "Importing Yahoo Geoplanet Data\n";
//Switch statement more-or-less ensures script picks up where it left off and does not attempt re-inserts, etc.
switch ($lastStage) {
	//populate placetypes 1
    case 0:
		if (!$importEngine->populatePlaceTypes()){
			exit;
		} else {
			$importEngine->addTracker(1,$importProgress);
		}
	//import adjacencies 2
	case 1:
		if (!$importEngine->importAdjacencies($files['adjacencies'])){
			exit;
		} else {
			$importEngine->addTracker(2,$importProgress);
		}
	//import places 3
	case 2:
		if (!$importEngine->importPlaces($files['places'])){
			exit;
		} else {
			$importEngine->addTracker(3,$importProgress);
		}
	//import aliases 4
	case 3:
		if (!$importEngine->importAliases($files['aliases'])){
			exit;
		} else {
			$importEngine->addTracker(4,$importProgress);
		}	
		
	//populate places 5
	case 4:
		if (!$importEngine->populatePlaces()){
			exit;
		} else {
			$importEngine->addTracker(5,$importProgress);
		}					
	//populate place type codes 6
	case 5:
		if (!$importEngine->addPlaceTypeCodes()){
			exit;
		} else {
			$importEngine->addTracker(6,$importProgress);
		}					
	//populate place names 7
	case 6:
		//@todo: placenames table should be truncated, as this script cannot pick up where left off (efficiency)
		if (!$importEngine->populatePlaceNames()){
			exit;
		} else {
			$importEngine->addTracker(7,$importProgress);
		}					
	//populate adjacencies 8
	case 7:
		if (!$importEngine->populateAdjacencies()){
			exit;
		} else {
			$importEngine->addTracker(8,$importProgress);
		}		
	//populate parents 9
	case 8:
		if (!$importEngine->populateParents()){
			exit;
		} else {
			$importEngine->addTracker(9,$importProgress);
		}		
	//populate children 10
	case 9:
		if (!$importEngine->populateChildren()){
			exit;
		} else {
			$importEngine->addTracker(10,$importProgress);
		}	
	//populate ancestors 11
	case 10:
		if (!$importEngine->populateAncestors()){
			exit;
		} else {
			$importEngine->addTracker(11,$importProgress);
		}	
	//populate descendants 12
	case 11:
		if (!$importEngine->populateDescendants()){
			exit;
		} else {
			$importEngine->addTracker(12,$importProgress);
		}	
	//Complete import
	case 12:
		$importEngine->dropTrackerTable($importProgress);
		echo "Import complete\n";
}

