<?php
/**
 * Instructions:
 * (1) Configure database vars in config.ini
 * (2) Download geoplanet data from http://developer.yahoo.com/geo/geoplanet/data/
 * (3) Add file names to the file variables below
 * (4) Run this script from the command line, i.e. "php import.php"
 * 
 * @package gplplanet
 * @author Tyler Bell tylerwbell[at]gmail[dot]com
 * @copyright (C) 2009-2011 - Tyler Bell
 * @license GNU General Public License
 * Ensure php.ini memory_limit is set to 1GB for handling large arrays
 * Temp tables require 50 GB HDD space
 */

//Full path to raw geoplanet files (change to suit your own path)
$files['aliases'] = "/tmp/geoplanet_aliases_7.5.2.tsv";
$files['places'] = "/tmp/geoplanet_places_7.5.2.tsv";
$files['adjacencies'] = "/tmp/geoplanet_adjacencies_7.5.2.tsv";


//==================== Usually no need to edit below this line =================
set_time_limit(0);		  //no timeout
error_reporting(E_ERROR); //runtime error reporting
require_once ('class.geoimport.php');
$importEngine = new geoimport;
$importProgress = "import";		//table name for tracking import progress					
//check files
foreach ($files as $file){
	if (!is_readable($file)){
		echo "Cannot read file ".$file;
		exit;
	}
}
echo "Files Verified\n";
//create database
echo "Creating Data Structure\n";
if (!$importEngine->createDatabase()){exit;}
//create table to track import progress (if not exist)
if (!$importEngine->createTrackerTable($importProgress)){exit;}
//get last stage of import completed
$lastStage = $importEngine->getMaxTracker($importProgress);
//import files
echo "Importing Yahoo Geoplanet Data\n";

//Switch statement ensures script picks up where it left off and does not attempt re-inserts, etc.
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
	//populate ancestors 12
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



